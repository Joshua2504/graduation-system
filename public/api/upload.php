<?php
/**
 * API: File upload handler (profile images)
 * 
 * POST /api/upload.php — Upload a profile image
 * 
 * Expects multipart form data:
 *   - file: the image file
 *   - type: card | national_id | receipt
 * 
 * Images are stored per-user in uploads/user_{userId}/
 */
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
require_role('student', true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$type = trim($_POST['type'] ?? '');
$allowedTypes = ['card', 'national_id', 'receipt'];

if (!in_array($type, $allowedTypes)) {
    jsonResponse(['error' => 'نوع الصورة غير صالح'], 400);
}

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    $errCode = $_FILES['file']['error'] ?? -1;
    jsonResponse(['error' => "فشل رفع الملف (خطأ: $errCode)"], 400);
}

$file = $_FILES['file'];
$validationError = validateUploadedFile($file);
if ($validationError) {
    jsonResponse(['error' => $validationError], 400);
}

$userId = current_user_id();
$uploadDir = dirname(__DIR__) . '/uploads/user_' . $userId;
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0775, true);
}

// Determine extension from MIME
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($file['tmp_name']);
$ext = $mime === 'image/png' ? 'png' : 'jpg';

$dbField = $type . '_image';
$filename = $userId . '_' . $type . '.' . $ext;
$destPath = $uploadDir . '/' . $filename;

if (!move_uploaded_file($file['tmp_name'], $destPath)) {
    jsonResponse(['error' => 'فشل في حفظ الملف'], 500);
}

chmod($destPath, 0644);

// Update user record
$pdo = getDB();
$stmt = $pdo->prepare("UPDATE users SET `$dbField` = ? WHERE id = ?");
$stmt->execute([$filename, $userId]);

// Re-check profile completeness
$user = getUserProfile($userId);
$profileComplete = isProfileComplete($user) ? 1 : 0;
$stmt = $pdo->prepare("UPDATE users SET profile_completed = ? WHERE id = ?");
$stmt->execute([$profileComplete, $userId]);

jsonResponse([
    'success' => true,
    'filename' => $filename,
    'path' => '/uploads/user_' . $userId . '/' . $filename,
    'profile_completed' => $profileComplete,
    'message' => 'تم رفع الملف بنجاح'
]);
