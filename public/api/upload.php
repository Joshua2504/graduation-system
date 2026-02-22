<?php
/**
 * API: File upload handler
 * 
 * POST /api/upload.php — Upload a student image
 * 
 * Expects multipart form data:
 *   - file: the image file
 *   - project_id: int
 *   - student_code: string
 *   - type: card | national_id | receipt
 */
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
require_role('student', true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$projectId   = (int)($_POST['project_id'] ?? 0);
$studentCode = trim($_POST['student_code'] ?? '');
$type        = trim($_POST['type'] ?? '');

// Validate inputs
if ($projectId === 0 || empty($studentCode) || empty($type)) {
    jsonResponse(['error' => 'بيانات ناقصة'], 400);
}

$allowedTypes = ['card', 'national_id', 'receipt'];
if (!in_array($type, $allowedTypes)) {
    jsonResponse(['error' => 'نوع الصورة غير صالح'], 400);
}

// Verify project ownership
$pdo = getDB();
$project = getProject($projectId);
if (!$project || (int)$project['user_id'] !== current_user_id()) {
    jsonResponse(['error' => 'غير مصرح'], 403);
}

if (!in_array($project['status'], ['draft', 'rejected'])) {
    jsonResponse(['error' => 'لا يمكن رفع ملفات في الحالة الحالية'], 400);
}

// Check file
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    $errCode = $_FILES['file']['error'] ?? -1;
    jsonResponse(['error' => "فشل رفع الملف (خطأ: $errCode)"], 400);
}

$file = $_FILES['file'];

// Validate file
$validationError = validateUploadedFile($file);
if ($validationError) {
    jsonResponse(['error' => $validationError], 400);
}

// Create project upload directory
$uploadDir = dirname(__DIR__) . '/uploads/project_' . $projectId;
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0775, true);
}

// Determine extension from MIME
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($file['tmp_name']);
$ext = $mime === 'image/png' ? 'png' : 'jpg';

// Generate filename: projectID_studentCode_type.ext
$filename = $projectId . '_' . $studentCode . '_' . $type . '.' . $ext;
$destPath = $uploadDir . '/' . $filename;

// Move uploaded file
if (!move_uploaded_file($file['tmp_name'], $destPath)) {
    jsonResponse(['error' => 'فشل في حفظ الملف'], 500);
}

// Set proper permissions
chmod($destPath, 0644);

jsonResponse([
    'success' => true,
    'filename' => $filename,
    'path' => '/uploads/project_' . $projectId . '/' . $filename,
    'message' => 'تم رفع الملف بنجاح'
]);
