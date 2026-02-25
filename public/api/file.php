<?php
/**
 * API: Secure file serving endpoint
 * 
 * GET /api/file.php?user={userId}&file={filename}
 * 
 * Serves uploaded files only to authenticated users with proper authorization:
 * - Students can only access their own files
 * - Doctors can access any student's files
 */
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

require_login(true);

$requestedFile = $_GET['file'] ?? '';
$requestedUserId = (int) ($_GET['user'] ?? 0);
$requestedProjectId = (int) ($_GET['project'] ?? 0);

if (empty($requestedFile) || ($requestedUserId <= 0 && $requestedProjectId <= 0)) {
    http_response_code(400);
    exit('Bad request');
}

// Sanitize filename — only allow alphanumeric, underscores, hyphens, dots
if (!preg_match('/^[a-zA-Z0-9_\-]+\.(jpg|jpeg|png|gif|webp)$/i', $requestedFile)) {
    http_response_code(400);
    exit('Invalid filename');
}

// Prevent directory traversal
if (strpos($requestedFile, '..') !== false || strpos($requestedFile, '/') !== false) {
    http_response_code(400);
    exit('Invalid filename');
}

$currentUserId = current_user_id();
$currentRole = current_role();
$uploadsBase = realpath(dirname(__DIR__) . '/uploads');

if ($requestedProjectId > 0) {
    // Project description file — accessible by project members and doctors
    if ($currentRole === 'student' && !isProjectMember($requestedProjectId, $currentUserId)) {
        http_response_code(403);
        exit('Forbidden');
    }
    $filePath = dirname(__DIR__) . '/uploads/project_' . $requestedProjectId . '/' . $requestedFile;
} else {
    // User profile file — students can only access own files, doctors can access all
    if ($currentRole === 'student' && $currentUserId !== $requestedUserId) {
        http_response_code(403);
        exit('Forbidden');
    }
    $filePath = dirname(__DIR__) . '/uploads/user_' . $requestedUserId . '/' . $requestedFile;
}
$realPath = realpath($filePath);

if ($realPath === false || !is_file($realPath)) {
    http_response_code(404);
    exit('File not found');
}

// Ensure the resolved path is still within uploads directory (prevents symlink attacks)
if (strpos($realPath, $uploadsBase) !== 0) {
    http_response_code(403);
    exit('Forbidden');
}

// Determine MIME type
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($realPath);
$allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

if (!in_array($mime, $allowedMimes)) {
    http_response_code(403);
    exit('Forbidden file type');
}

// Serve the file with appropriate headers
header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($realPath));
header('Cache-Control: private, max-age=3600');
header('X-Content-Type-Options: nosniff');
header('Content-Disposition: inline; filename="' . basename($realPath) . '"');

readfile($realPath);
exit;
