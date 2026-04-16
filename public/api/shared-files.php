<?php
/**
 * API: Shared Files management
 *
 * GET    /api/shared-files          – list all files
 * POST   /api/shared-files          – upload a new file  (multipart: name, year, department, note, file)
 * POST   /api/shared-files?_method=PUT&id=X – edit metadata (name, year, department, note; optional new file)
 * POST   /api/shared-files?_method=DELETE&id=X – delete file
 * GET    /api/shared-files?download=X  – serve the file for download
 */
require_once dirname(__DIR__) . '/includes/bootstrap.php';

require_login(true);

$pdo    = getDB();
$role   = current_role();
$userId = current_user_id();
$method = $_SERVER['REQUEST_METHOD'];

// Support method override via _method query param
$overrideMethod = strtoupper($_GET['_method'] ?? '');
if ($method === 'POST' && in_array($overrideMethod, ['PUT', 'DELETE'])) {
    $method = $overrideMethod;
}

// ─── Download / serve file ───────────────────────────────────────────────────
if ($method === 'GET' && isset($_GET['download'])) {
    $fileId = (int)$_GET['download'];
    $stmt = $pdo->prepare("SELECT * FROM shared_files WHERE id = ?");
    $stmt->execute([$fileId]);
    $fileRow = $stmt->fetch();

    if (!$fileRow) {
        http_response_code(404);
        exit('File not found');
    }

    $filePath = dirname(__DIR__) . '/uploads/shared/' . basename($fileRow['filename']);
    $realPath = realpath($filePath);
    $uploadsBase = realpath(dirname(__DIR__) . '/uploads');

    if ($realPath === false || !is_file($realPath) || strpos($realPath, $uploadsBase) !== 0) {
        http_response_code(404);
        exit('File not found');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($realPath) ?: 'application/octet-stream';

    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($realPath));
    header('Cache-Control: private, max-age=3600');
    header('X-Content-Type-Options: nosniff');
    header('Content-Disposition: attachment; filename="' . rawurlencode($fileRow['original_name']) . '"');
    readfile($realPath);
    exit;
}

// ─── List files ──────────────────────────────────────────────────────────────
if ($method === 'GET') {
    $stmt = $pdo->query("
        SELECT sf.*, u.name AS uploader_name
        FROM shared_files sf
        LEFT JOIN users u ON u.id = sf.uploaded_by
        ORDER BY sf.created_at DESC
    ");
    jsonResponse(['files' => $stmt->fetchAll()]);
}

// ─── Only admins and professors may modify files ──────────────────────────────
if (!in_array($role, ['admin', 'doctor'])) {
    jsonResponse(['error' => __('unauthorized')], 403);
}

// ─── Create (upload) ─────────────────────────────────────────────────────────
if ($method === 'POST') {
    $name       = trim($_POST['name']       ?? '');
    $year       = trim($_POST['year']       ?? '');
    $department = trim($_POST['department'] ?? '');
    $note       = trim($_POST['note']       ?? '');

    if ($name === '') {
        jsonResponse(['error' => __('file_name_required')], 400);
    }

    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        jsonResponse(['error' => __('file_required')], 400);
    }

    // Check duplicate
    $stmtDup = $pdo->prepare("SELECT id FROM shared_files WHERE name = ? AND year = ? AND department = ?");
    $stmtDup->execute([$name, $year, $department]);
    if ($stmtDup->fetch()) {
        jsonResponse(['error' => __('file_duplicate')], 409);
    }

    $uploadedFile = $_FILES['file'];
    if ($uploadedFile['size'] > 20 * 1024 * 1024) {
        jsonResponse(['error' => __('file_size_exceeded')], 400);
    }

    $originalName = basename($uploadedFile['name']);
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    if (empty($ext)) {
        jsonResponse(['error' => __('invalid_file_extension')], 400);
    }

    $uploadDir = dirname(__DIR__) . '/uploads/shared';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0775, true);
    }

    $filename = 'sf_' . bin2hex(random_bytes(8)) . '.' . $ext;
    $destPath = $uploadDir . '/' . $filename;

    if (!move_uploaded_file($uploadedFile['tmp_name'], $destPath)) {
        jsonResponse(['error' => __('file_save_failed')], 500);
    }
    chmod($destPath, 0644);

    $stmt = $pdo->prepare("
        INSERT INTO shared_files (name, year, department, note, filename, original_name, uploaded_by)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$name, $year, $department, $note ?: null, $filename, $originalName, $userId]);
    $newId = (int)$pdo->lastInsertId();

    $stmt2 = $pdo->prepare("SELECT sf.*, u.name AS uploader_name FROM shared_files sf LEFT JOIN users u ON u.id = sf.uploaded_by WHERE sf.id = ?");
    $stmt2->execute([$newId]);
    jsonResponse(['success' => true, 'message' => __('file_created'), 'file' => $stmt2->fetch()]);
}

// ─── Update ───────────────────────────────────────────────────────────────────
if ($method === 'PUT') {
    $fileId     = (int)($_GET['id'] ?? ($_POST['id'] ?? 0));
    $name       = trim($_POST['name']       ?? '');
    $year       = trim($_POST['year']       ?? '');
    $department = trim($_POST['department'] ?? '');
    $note       = trim($_POST['note']       ?? '');

    if ($fileId === 0) {
        jsonResponse(['error' => __('file_id_required')], 400);
    }
    if ($name === '') {
        jsonResponse(['error' => __('file_name_required')], 400);
    }

    $stmtExist = $pdo->prepare("SELECT * FROM shared_files WHERE id = ?");
    $stmtExist->execute([$fileId]);
    $existing = $stmtExist->fetch();
    if (!$existing) {
        jsonResponse(['error' => __('file_not_found')], 404);
    }

    // Check duplicate (exclude current record)
    $stmtDup = $pdo->prepare("SELECT id FROM shared_files WHERE name = ? AND year = ? AND department = ? AND id != ?");
    $stmtDup->execute([$name, $year, $department, $fileId]);
    if ($stmtDup->fetch()) {
        jsonResponse(['error' => __('file_duplicate')], 409);
    }

    $filename     = $existing['filename'];
    $originalName = $existing['original_name'];

    // If a new file is provided, replace the stored file
    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $newFile = $_FILES['file'];
        if ($newFile['size'] > 20 * 1024 * 1024) {
            jsonResponse(['error' => __('file_size_exceeded')], 400);
        }
        $originalName = basename($newFile['name']);
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (empty($ext)) {
            jsonResponse(['error' => __('invalid_file_extension')], 400);
        }

        $uploadDir = dirname(__DIR__) . '/uploads/shared';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $filename = 'sf_' . bin2hex(random_bytes(8)) . '.' . $ext;
        $destPath = $uploadDir . '/' . $filename;

        if (!move_uploaded_file($newFile['tmp_name'], $destPath)) {
            jsonResponse(['error' => __('file_save_failed')], 500);
        }
        chmod($destPath, 0644);

        // Delete old file
        $oldPath = dirname(__DIR__) . '/uploads/shared/' . basename($existing['filename']);
        if (is_file($oldPath)) {
            @unlink($oldPath);
        }
    }

    $stmt = $pdo->prepare("
        UPDATE shared_files SET name = ?, year = ?, department = ?, note = ?, filename = ?, original_name = ?
        WHERE id = ?
    ");
    $stmt->execute([$name, $year, $department, $note ?: null, $filename, $originalName, $fileId]);

    $stmt2 = $pdo->prepare("SELECT sf.*, u.name AS uploader_name FROM shared_files sf LEFT JOIN users u ON u.id = sf.uploaded_by WHERE sf.id = ?");
    $stmt2->execute([$fileId]);
    jsonResponse(['success' => true, 'message' => __('file_updated'), 'file' => $stmt2->fetch()]);
}

// ─── Delete ───────────────────────────────────────────────────────────────────
if ($method === 'DELETE') {
    $fileId = (int)($_GET['id'] ?? 0);

    if ($fileId === 0) {
        jsonResponse(['error' => __('file_id_required')], 400);
    }

    $stmtExist = $pdo->prepare("SELECT * FROM shared_files WHERE id = ?");
    $stmtExist->execute([$fileId]);
    $existing = $stmtExist->fetch();
    if (!$existing) {
        jsonResponse(['error' => __('file_not_found')], 404);
    }

    $pdo->prepare("DELETE FROM shared_files WHERE id = ?")->execute([$fileId]);

    // Remove physical file
    $filePath = dirname(__DIR__) . '/uploads/shared/' . basename($existing['filename']);
    if (is_file($filePath)) {
        @unlink($filePath);
    }

    jsonResponse(['success' => true, 'message' => __('file_deleted')]);
}

jsonResponse(['error' => 'Method not allowed'], 405);
