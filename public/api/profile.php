<?php
/**
 * API: Student Profile management
 * 
 * GET    /api/profile.php          — Get own profile
 * PUT    /api/profile.php          — Update profile fields
 * POST   /api/profile.php          — Upload profile image
 */
require_once dirname(__DIR__) . '/includes/bootstrap.php';

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
    
    // Students can edit more fields; professors/admins have a smaller set
    $allowedFields = ($role === 'doctor' || $role === 'admin')
        ? ['gender', 'phone', 'section']
        : ['gender', 'national_id', 'birth_date', 'governorate', 'address', 'phone', 'year', 'section'];
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
        $err = validateNationalId($input['national_id']);
        if ($err) jsonResponse(['error' => $err], 400);
    }
    
    if (isset($input['phone']) && !empty($input['phone'])) {
        $err = validatePhone($input['phone']);
        if ($err) jsonResponse(['error' => $err], 400);
    }
    
    if (isset($input['gender']) && !in_array($input['gender'], ['male', 'female'])) {
        jsonResponse(['error' => 'الجنس غير صالح'], 400);
    }
    
    if (isset($input['year']) && !in_array($input['year'], ['1st', '2nd', '3rd', '4th'])) {
        jsonResponse(['error' => 'السنة الدراسية غير صالحة'], 400);
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
    // Professors/admins can only upload profile pictures; students can upload documents too
    $allowedTypes = ($role === 'doctor' || $role === 'admin')
        ? ['profile_picture']
        : ['card', 'national_id', 'receipt', 'profile_picture'];
    
    if (!in_array($type, $allowedTypes)) {
        jsonResponse(['error' => 'نوع الصورة غير صالح'], 400);
    }
    
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        $errCode = $_FILES['file']['error'] ?? -1;
        jsonResponse(['error' => "فشل رفع الملف (خطأ: $errCode)"], 400);
    }
    
    $userId = current_user_id();
    $checkProfile = ($role === 'student');
    $result = handleFileUpload($userId, $type, $_FILES['file'], $checkProfile);
    
    if (isset($result['error'])) {
        jsonResponse(['error' => $result['error']], $result['code']);
    }
    
    jsonResponse($result);
}

jsonResponse(['error' => 'Method not allowed'], 405);
