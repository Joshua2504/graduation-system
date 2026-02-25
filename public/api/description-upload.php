<?php
/**
 * API: Upload files for project descriptions (images)
 * 
 * POST /api/description-upload.php
 * 
 * Expects multipart form data:
 *   - file: the image file (JPG/PNG/GIF/WebP, max 5MB)
 *   - project_id: the project to attach the image to
 * 
 * Files are stored in uploads/project_{projectId}/
 * Accessible via /api/file.php?project={projectId}&file={filename}
 */
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
require_login(true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$projectId = (int)($_POST['project_id'] ?? 0);
if ($projectId === 0) {
    jsonResponse(['error' => 'معرف المشروع مطلوب'], 400);
}

$project = getProject($projectId);
if (!$project) {
    jsonResponse(['error' => 'المشروع غير موجود'], 404);
}

// Authorization: doctor can upload to any project; student must be leader + draft/rejected
$role = current_role();
if ($role === 'student') {
    $userId = current_user_id();
    if (!isProjectLeader($projectId, $userId)) {
        jsonResponse(['error' => 'فقط قائد الفريق يمكنه رفع الملفات'], 403);
    }
    if (!in_array($project['status'], ['draft', 'rejected'])) {
        jsonResponse(['error' => 'لا يمكن رفع الملفات في الحالة الحالية'], 400);
    }
}

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    $errCode = $_FILES['file']['error'] ?? -1;
    jsonResponse(['error' => "فشل رفع الملف (خطأ: $errCode)"], 400);
}

$file = $_FILES['file'];

// Validate file
if ($file['size'] > 5 * 1024 * 1024) {
    jsonResponse(['error' => 'حجم الملف يتجاوز 5 ميجابايت'], 400);
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($file['tmp_name']);
$allowedMimes = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/gif'  => 'gif',
    'image/webp' => 'webp',
];

if (!isset($allowedMimes[$mime])) {
    jsonResponse(['error' => 'نوع الملف غير مسموح - يجب أن يكون JPG أو PNG أو GIF أو WebP'], 400);
}

$ext = $allowedMimes[$mime];

// Create project upload directory
$uploadDir = dirname(__DIR__) . '/uploads/project_' . $projectId;
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0775, true);
}

// Generate unique filename
$filename = 'desc_' . bin2hex(random_bytes(8)) . '.' . $ext;
$destPath = $uploadDir . '/' . $filename;

if (!move_uploaded_file($file['tmp_name'], $destPath)) {
    jsonResponse(['error' => 'فشل في حفظ الملف'], 500);
}

chmod($destPath, 0644);

$fileUrl = '/api/file.php?project=' . $projectId . '&file=' . urlencode($filename);

jsonResponse([
    'success' => true,
    'url' => $fileUrl,
    'filename' => $filename,
    'message' => 'تم رفع الملف بنجاح'
]);
