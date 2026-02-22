<?php
/**
 * API: User account management (doctor only)
 * 
 * GET    /api/users.php            — List all student accounts
 * GET    /api/users.php?id=X       — Get single student profile
 * POST   /api/users.php            — Toggle verify / enable / disable a student
 * PUT    /api/users.php            — Update student profile fields (doctor editing)
 */
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/mailer.php';

header('Content-Type: application/json; charset=utf-8');
require_role('doctor', true);

$method = $_SERVER['REQUEST_METHOD'];
$pdo = getDB();

if ($method === 'GET') {
    $id = (int)($_GET['id'] ?? 0);
    
    // Single student profile
    if ($id > 0) {
        $stmt = $pdo->prepare("SELECT id, name, email, student_code, gender, national_id, birth_date, governorate, address, phone, year, section, card_image, national_id_image, receipt_image, profile_completed, email_verified, account_enabled, created_at FROM users WHERE id = ? AND role = 'student'");
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        if (!$user) jsonResponse(['error' => 'المستخدم غير موجود'], 404);
        jsonResponse(['user' => $user]);
    }
    
    // List all students
    $stmt = $pdo->query("SELECT id, name, email, student_code, email_verified, account_enabled, profile_completed, created_at FROM users WHERE role = 'student' ORDER BY created_at DESC");
    $students = $stmt->fetchAll();
    jsonResponse(['students' => $students]);
}

if ($method === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true);
    $userId = (int)($input['user_id'] ?? 0);

    if ($userId === 0) {
        jsonResponse(['error' => 'معرف المستخدم مطلوب'], 400);
    }

    // Verify it's a student account
    $stmt = $pdo->prepare("SELECT id, role FROM users WHERE id = ? AND role = 'student'");
    $stmt->execute([$userId]);
    if (!$stmt->fetch()) {
        jsonResponse(['error' => 'المستخدم غير موجود'], 404);
    }

    $allowedFields = ['name', 'email', 'student_code', 'gender', 'national_id', 'birth_date', 'governorate', 'address', 'phone', 'section'];
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
    if (isset($input['gender']) && !empty($input['gender']) && !in_array($input['gender'], ['male', 'female'])) {
        jsonResponse(['error' => 'الجنس غير صالح'], 400);
    }
    if (isset($input['email']) && !empty($input['email'])) {
        // Check uniqueness
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([trim($input['email']), $userId]);
        if ($stmt->fetch()) {
            jsonResponse(['error' => 'البريد الإلكتروني مستخدم بالفعل'], 400);
        }
    }
    if (isset($input['student_code']) && !empty($input['student_code'])) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE student_code = ? AND id != ?");
        $stmt->execute([trim($input['student_code']), $userId]);
        if ($stmt->fetch()) {
            jsonResponse(['error' => 'كود الطالب مستخدم بالفعل'], 400);
        }
    }

    // Update profile_completed
    $user = getUserProfile($userId);
    $tempUser = array_merge($user, array_intersect_key($input, array_flip($allowedFields)));
    $profileComplete = isProfileComplete($tempUser) ? 1 : 0;
    $updates[] = "`profile_completed` = ?";
    $params[] = $profileComplete;

    $params[] = $userId;
    $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    jsonResponse([
        'success' => true,
        'profile_completed' => $profileComplete,
        'message' => 'تم تحديث بيانات الطالب بنجاح'
    ]);
}

if ($method === 'POST') {
    // Support both JSON and form data (for file uploads)
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (strpos($contentType, 'multipart/form-data') !== false) {
        $input = $_POST;
    } else {
        $input = json_decode(file_get_contents('php://input'), true);
    }
    $userId = (int)($input['user_id'] ?? 0);
    $action = trim($input['action'] ?? '');

    // create_user doesn't need a user_id
    if ($action === 'create_user') {
        // handled in switch below
    } elseif ($userId === 0) {
        jsonResponse(['error' => 'معرف المستخدم مطلوب'], 400);
    }

    // Verify it's a student account (skip for create_user)
    if ($action !== 'create_user') {
        $stmt = $pdo->prepare("SELECT id, role, email_verified, account_enabled FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user || $user['role'] !== 'student') {
            jsonResponse(['error' => 'المستخدم غير موجود'], 404);
        }
    }

    switch ($action) {
        case 'create_user':
            // Doctor creates a new student account
            $name = trim($input['name'] ?? '');
            $email = trim($input['email'] ?? '');
            $password = $input['password'] ?? '';
            $studentCode = trim($input['student_code'] ?? '');

            if (empty($name) || empty($email) || empty($password)) {
                jsonResponse(['error' => 'الاسم والبريد وكلمة المرور مطلوبين'], 400);
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                jsonResponse(['error' => 'البريد الإلكتروني غير صالح'], 400);
            }
            if (strlen($password) < 6) {
                jsonResponse(['error' => 'كلمة المرور يجب أن تكون 6 أحرف على الأقل'], 400);
            }

            // Check email uniqueness
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                jsonResponse(['error' => 'البريد الإلكتروني مستخدم بالفعل'], 400);
            }

            // Check student code uniqueness (if provided)
            if (!empty($studentCode)) {
                $stmt = $pdo->prepare("SELECT id FROM users WHERE student_code = ?");
                $stmt->execute([$studentCode]);
                if ($stmt->fetch()) {
                    jsonResponse(['error' => 'كود الطالب مستخدم بالفعل'], 400);
                }
            }

            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            $sendInvite = !empty($input['send_invite']);

            // Create user — auto-verified and enabled
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, student_code, role, email_verified, account_enabled) VALUES (?, ?, ?, ?, 'student', 1, 1)");
            $stmt->execute([$name, $email, $hashedPassword, $studentCode ?: null]);
            $newUserId = (int)$pdo->lastInsertId();

            $emailSent = false;
            if ($sendInvite) {
                $lang = $_GET['lang'] ?? ($_COOKIE['lang'] ?? 'ar');
                $emailSent = sendWelcomeEmail($email, $name, $password, $lang);
            }

            jsonResponse([
                'success' => true,
                'user_id' => $newUserId,
                'email_sent' => $emailSent,
                'message' => 'تم إنشاء حساب الطالب بنجاح'
            ]);
            break;

        case 'verify':
            $stmt = $pdo->prepare("UPDATE users SET email_verified = 1, verification_token = NULL, token_expires_at = NULL WHERE id = ?");
            $stmt->execute([$userId]);
            jsonResponse(['success' => true, 'message' => 'تم تأكيد البريد الإلكتروني']);
            break;

        case 'unverify':
            $stmt = $pdo->prepare("UPDATE users SET email_verified = 0 WHERE id = ?");
            $stmt->execute([$userId]);
            jsonResponse(['success' => true, 'message' => 'تم إلغاء تأكيد البريد الإلكتروني']);
            break;

        case 'enable':
            $stmt = $pdo->prepare("UPDATE users SET account_enabled = 1 WHERE id = ?");
            $stmt->execute([$userId]);
            jsonResponse(['success' => true, 'message' => 'تم تفعيل الحساب']);
            break;

        case 'disable':
            $stmt = $pdo->prepare("UPDATE users SET account_enabled = 0 WHERE id = ?");
            $stmt->execute([$userId]);
            jsonResponse(['success' => true, 'message' => 'تم تعطيل الحساب']);
            break;

        case 'delete':
            // Delete user and all associated data (cascading)
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'student'");
            $stmt->execute([$userId]);
            jsonResponse(['success' => true, 'message' => 'تم حذف الحساب']);
            break;

        case 'resend_verification':
            // Resend verification email to student
            if ($user['email_verified']) {
                jsonResponse(['error' => 'البريد الإلكتروني مؤكد بالفعل'], 400);
            }
            $stmt2 = $pdo->prepare("SELECT name, email FROM users WHERE id = ?");
            $stmt2->execute([$userId]);
            $student = $stmt2->fetch();
            $token = generateVerificationToken($userId);
            $lang = $_GET['lang'] ?? ($_COOKIE['lang'] ?? 'ar');
            $sent = sendVerificationEmail($student['email'], $student['name'], $token, $lang);
            if ($sent) {
                jsonResponse(['success' => true, 'message' => 'تم إرسال رابط التأكيد بنجاح']);
            } else {
                jsonResponse(['error' => 'فشل في إرسال البريد الإلكتروني. تحقق من إعدادات SMTP.'], 500);
            }
            break;

        case 'impersonate':
            // Allow doctor to login as a student
            $stmt2 = $pdo->prepare("SELECT id, name, email, role FROM users WHERE id = ? AND role = 'student'");
            $stmt2->execute([$userId]);
            $student = $stmt2->fetch();
            if (!$student) {
                jsonResponse(['error' => 'الطالب غير موجود'], 404);
            }
            // Store original doctor session
            $_SESSION['impersonator_id'] = $_SESSION['user_id'];
            $_SESSION['impersonator_name'] = $_SESSION['name'];
            $_SESSION['impersonator_email'] = $_SESSION['email'];
            $_SESSION['impersonator_role'] = $_SESSION['role'];
            // Switch to student session
            $_SESSION['user_id'] = $student['id'];
            $_SESSION['name'] = $student['name'];
            $_SESSION['email'] = $student['email'];
            $_SESSION['role'] = $student['role'];
            jsonResponse(['success' => true, 'redirect' => '/student/dashboard.php', 'message' => 'تم الدخول كطالب']);
            break;

        case 'upload_image':
            $imgType = trim($input['image_type'] ?? '');
            if (!in_array($imgType, ['card', 'national_id', 'receipt'])) {
                jsonResponse(['error' => 'نوع الصورة غير صالح'], 400);
            }
            if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                jsonResponse(['error' => 'فشل رفع الملف'], 400);
            }
            $file = $_FILES['file'];
            $validationError = validateUploadedFile($file);
            if ($validationError) {
                jsonResponse(['error' => $validationError], 400);
            }
            $uploadDir = dirname(__DIR__) . '/uploads/user_' . $userId;
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0775, true);
            }
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($file['tmp_name']);
            $ext = $mime === 'image/png' ? 'png' : 'jpg';
            $dbField = $imgType . '_image';
            $filename = $userId . '_' . $imgType . '.' . $ext;
            $destPath = $uploadDir . '/' . $filename;
            if (!move_uploaded_file($file['tmp_name'], $destPath)) {
                jsonResponse(['error' => 'فشل في حفظ الملف'], 500);
            }
            chmod($destPath, 0644);
            $stmt = $pdo->prepare("UPDATE users SET `$dbField` = ? WHERE id = ?");
            $stmt->execute([$filename, $userId]);
            // Update profile completed
            $updUser = getUserProfile($userId);
            $pc = isProfileComplete($updUser) ? 1 : 0;
            $stmt = $pdo->prepare("UPDATE users SET profile_completed = ? WHERE id = ?");
            $stmt->execute([$pc, $userId]);
            jsonResponse([
                'success' => true,
                'filename' => $filename,
                'path' => '/uploads/user_' . $userId . '/' . $filename,
                'profile_completed' => $pc,
                'message' => 'تم رفع الصورة بنجاح'
            ]);
            break;

        default:
            jsonResponse(['error' => 'إجراء غير صالح'], 400);
    }
}

jsonResponse(['error' => 'Method not allowed'], 405);
