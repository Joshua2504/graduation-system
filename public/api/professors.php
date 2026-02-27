<?php
/**
 * API: Professor account management (admin only)
 * 
 * GET    /api/professors.php         — List all professor accounts
 * POST   /api/professors.php         — Create professor / toggle enable / disable / delete / impersonate / reset password
 */
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/mailer.php';

header('Content-Type: application/json; charset=utf-8');
require_admin(true);

$method = $_SERVER['REQUEST_METHOD'];
$pdo = getDB();

// ─── GET: List professors ───
if ($method === 'GET') {
    $search = trim($_GET['search'] ?? '');
    if ($search !== '') {
        $like = "%$search%";
        $stmt = $pdo->prepare("SELECT id, name, email, gender, phone, section, profile_picture, account_enabled, created_at FROM users WHERE role = 'doctor' AND (name LIKE ? OR email LIKE ?) ORDER BY name ASC LIMIT 20");
        $stmt->execute([$like, $like]);
    } else {
        $stmt = $pdo->query("SELECT id, name, email, gender, phone, section, profile_picture, account_enabled, created_at FROM users WHERE role = 'doctor' ORDER BY created_at DESC");
    }
    $professors = $stmt->fetchAll();
    jsonResponse(['professors' => $professors]);
}

// ─── POST: Actions ───
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $userId = (int)($input['user_id'] ?? 0);
    $action = trim($input['action'] ?? '');

    // create_professor doesn't need a user_id
    if ($action !== 'create_professor' && $userId === 0) {
        jsonResponse(['error' => 'معرف المستخدم مطلوب'], 400);
    }

    // Verify it's a professor account (skip for create)
    if ($action !== 'create_professor') {
        $stmt = $pdo->prepare("SELECT id, name, email, role, account_enabled FROM users WHERE id = ? AND role = 'doctor'");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        if (!$user) {
            jsonResponse(['error' => 'المستخدم غير موجود'], 404);
        }
    }

    switch ($action) {
        case 'create_professor':
            $name = trim($input['name'] ?? '');
            $email = trim($input['email'] ?? '');
            $password = $input['password'] ?? '';
            $section = trim($input['section'] ?? '');

            if (empty($name) || empty($email) || empty($password)) {
                jsonResponse(['error' => __('name_email_password_required')], 400);
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                jsonResponse(['error' => __('invalid_email')], 400);
            }
            if (strlen($password) < 6) {
                jsonResponse(['error' => __('password_min_length')], 400);
            }

            // Check email uniqueness
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                jsonResponse(['error' => __('email_exists')], 400);
            }

            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            $sendInvite = !empty($input['send_invite']);

            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, section, role, email_verified, account_enabled) VALUES (?, ?, ?, ?, 'doctor', 1, 1)");
            $stmt->execute([$name, $email, $hashedPassword, $section ?: null]);
            $newUserId = (int)$pdo->lastInsertId();

            $emailSent = false;
            if ($sendInvite) {
                $lang = getLang();
                $emailSent = sendProfessorWelcomeEmail($email, $name, $password, $lang);
            }

            jsonResponse([
                'success' => true,
                'user_id' => $newUserId,
                'email_sent' => $emailSent,
                'message' => __('professor_created')
            ]);
            break;

        case 'enable':
            $stmt = $pdo->prepare("UPDATE users SET account_enabled = 1 WHERE id = ?");
            $stmt->execute([$userId]);
            jsonResponse(['success' => true, 'message' => __('account_enabled_msg')]);
            break;

        case 'disable':
            $stmt = $pdo->prepare("UPDATE users SET account_enabled = 0 WHERE id = ?");
            $stmt->execute([$userId]);
            jsonResponse(['success' => true, 'message' => __('account_disabled_msg')]);
            break;

        case 'delete':
            // Delete professor account
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'doctor'");
            $stmt->execute([$userId]);
            jsonResponse(['success' => true, 'message' => __('professor_deleted')]);
            break;

        case 'reset_password':
            $newPassword = trim($input['password'] ?? '');
            if (empty($newPassword) || strlen($newPassword) < 6) {
                jsonResponse(['error' => __('password_min_length')], 400);
            }
            $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashedPassword, $userId]);

            $emailSent = false;
            if (!empty($input['send_email'])) {
                $lang = getLang();
                $emailSent = sendProfessorWelcomeEmail($user['email'], $user['name'], $newPassword, $lang);
            }

            jsonResponse([
                'success' => true,
                'email_sent' => $emailSent,
                'message' => __('password_reset_success')
            ]);
            break;

        case 'impersonate':
            // Allow admin to login as a professor
            $stmt2 = $pdo->prepare("SELECT id, name, email, role FROM users WHERE id = ? AND role = 'doctor'");
            $stmt2->execute([$userId]);
            $professor = $stmt2->fetch();
            if (!$professor) {
                jsonResponse(['error' => 'الأستاذ غير موجود'], 404);
            }
            // Store original admin session
            $_SESSION['impersonator_id'] = $_SESSION['user_id'];
            $_SESSION['impersonator_name'] = $_SESSION['name'];
            $_SESSION['impersonator_email'] = $_SESSION['email'];
            $_SESSION['impersonator_role'] = $_SESSION['role'];
            // Switch to professor session
            $_SESSION['user_id'] = $professor['id'];
            $_SESSION['name'] = $professor['name'];
            $_SESSION['email'] = $professor['email'];
            $_SESSION['role'] = $professor['role'];
            jsonResponse(['success' => true, 'redirect' => '/professor/dashboard', 'message' => __('impersonating_professor')]);
            break;

        default:
            jsonResponse(['error' => 'إجراء غير صالح'], 400);
    }
}

jsonResponse(['error' => 'Method not allowed'], 405);
