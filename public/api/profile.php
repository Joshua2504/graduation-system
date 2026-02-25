<?php
/**
 * API: Student Profile management
 * 
 * GET    /api/profile.php          — Get own profile
 * PUT    /api/profile.php          — Update profile fields
 * POST   /api/profile.php          — Upload profile image
 */
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];

// ─── GET: Fetch own profile ───
if ($method === 'GET') {
    require_login(true);
    
    $user = getUserProfile(current_user_id());
    if (!$user) {
        jsonResponse(['error' => 'المستخدم غير موجود'], 404);
    }
    
    // Remove sensitive fields
    unset($user['password'], $user['verification_token'], $user['token_expires_at']);
    $user['profile_completed'] = isProfileComplete($user) ? 1 : 0;
    
    jsonResponse(['user' => $user]);
}

// ─── PUT: Update profile fields ───
if ($method === 'PUT') {
    require_login(true);
    
    $input = json_decode(file_get_contents('php://input'), true);
    $userId = current_user_id();
    $pdo = getDB();
    $role = $_SESSION['role'] ?? 'student';
    
    // Students can edit more fields; professors have a smaller set
    $allowedFields = $role === 'doctor'
        ? ['gender', 'phone', 'section']
        : ['gender', 'national_id', 'birth_date', 'governorate', 'address', 'phone', 'section'];
    $updates = [];
    $params = [];
    
    foreach ($allowedFields as $field) {
        if (isset($input[$field])) {
            $updates[] = "`$field` = ?";
            $params[] = trim($input[$field]);
        }
    }
    
    if (empty($updates)) {
        jsonResponse(['error' => 'لا توجد بيانات للتحديث'], 400);
    }
    
    // Validation
    if (isset($input['national_id']) && !empty($input['national_id'])) {
        if (!preg_match('/^\d{14}$/', $input['national_id'])) {
            jsonResponse(['error' => 'الرقم القومي يجب أن يكون 14 رقم'], 400);
        }
    }
    
    if (isset($input['phone']) && !empty($input['phone'])) {
        if (!preg_match('/^\d{11}$/', $input['phone'])) {
            jsonResponse(['error' => 'رقم الهاتف يجب أن يكون 11 رقم'], 400);
        }
    }
    
    if (isset($input['gender']) && !in_array($input['gender'], ['male', 'female'])) {
        jsonResponse(['error' => 'الجنس غير صالح'], 400);
    }
    
    // Check profile completion after update (students only)
    $profileComplete = 0;
    if ($role === 'student') {
        $user = getUserProfile($userId);
        $tempUser = array_merge($user, array_intersect_key($input, array_flip($allowedFields)));
        $profileComplete = isProfileComplete($tempUser) ? 1 : 0;
        
        $updates[] = "`profile_completed` = ?";
        $params[] = $profileComplete;
    }
    
    $params[] = $userId;
    $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    jsonResponse([
        'success' => true,
        'profile_completed' => $profileComplete,
        'message' => 'تم تحديث البيانات بنجاح'
    ]);
}

// ─── POST: Upload profile image ───
if ($method === 'POST') {
    require_login(true);
    
    $type = trim($_POST['type'] ?? '');
    $role = $_SESSION['role'] ?? 'student';
    // Professors can only upload profile pictures; students can upload documents too
    $allowedTypes = $role === 'doctor'
        ? ['profile_picture']
        : ['card', 'national_id', 'receipt', 'profile_picture'];
    
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
    
    // Determine extension
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $ext = $mime === 'image/png' ? 'png' : 'jpg';
    
    $dbField = $type === 'profile_picture' ? 'profile_picture' : $type . '_image';
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
    
    // Check if profile is now complete (students only)
    $profileComplete = 0;
    if ($role === 'student') {
        $user = getUserProfile($userId);
        $profileComplete = isProfileComplete($user) ? 1 : 0;
        $stmt = $pdo->prepare("UPDATE users SET profile_completed = ? WHERE id = ?");
        $stmt->execute([$profileComplete, $userId]);
    }
    
    jsonResponse([
        'success' => true,
        'filename' => $filename,
        'path' => secureFileUrl($userId, $filename),
        'profile_completed' => $profileComplete,
        'message' => 'تم رفع الملف بنجاح'
    ]);
}

jsonResponse(['error' => 'Method not allowed'], 405);
