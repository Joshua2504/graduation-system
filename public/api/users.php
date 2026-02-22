<?php
/**
 * API: User account management (doctor only)
 * 
 * GET    /api/users.php            — List all student accounts
 * POST   /api/users.php            — Toggle verify / enable / disable a student
 */
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
require_role('doctor', true);

$method = $_SERVER['REQUEST_METHOD'];
$pdo = getDB();

if ($method === 'GET') {
    $stmt = $pdo->query("SELECT id, name, email, student_code, email_verified, account_enabled, created_at FROM users WHERE role = 'student' ORDER BY created_at DESC");
    $students = $stmt->fetchAll();
    jsonResponse(['students' => $students]);
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $userId = (int)($input['user_id'] ?? 0);
    $action = trim($input['action'] ?? '');

    if ($userId === 0) {
        jsonResponse(['error' => 'معرف المستخدم مطلوب'], 400);
    }

    // Verify it's a student account
    $stmt = $pdo->prepare("SELECT id, role, email_verified, account_enabled FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user || $user['role'] !== 'student') {
        jsonResponse(['error' => 'المستخدم غير موجود'], 404);
    }

    switch ($action) {
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

        default:
            jsonResponse(['error' => 'إجراء غير صالح'], 400);
    }
}

jsonResponse(['error' => 'Method not allowed'], 405);
