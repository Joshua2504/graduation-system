<?php
/**
 * API: System settings
 * 
 * GET  /api/settings.php       — Get current settings
 * POST /api/settings.php       — Update settings (doctor only)
 */
require_once dirname(__DIR__) . '/includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $settings = getSettings();
    jsonResponse($settings);
}

if ($method === 'POST') {
    // Admin and doctor can both update settings
    require_login(true);
    if (!is_admin_or_doctor()) {
        jsonResponse(['error' => 'غير مصرح بالوصول', 'error_en' => 'Forbidden'], 403);
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $pdo = getDB();
    
    $updates = [];
    $params = [];
    
    if (isset($input['registration_open'])) {
        $updates[] = "registration_open = ?";
        $params[] = (int)(bool)$input['registration_open'];
    }
    
    if (isset($input['email_verification_required'])) {
        $updates[] = "email_verification_required = ?";
        $params[] = (int)(bool)$input['email_verification_required'];
    }
    
    if (isset($input['min_team_size'])) {
        $min = max(1, min(10, (int)$input['min_team_size']));
        $updates[] = "min_team_size = ?";
        $params[] = $min;
    }
    
    if (isset($input['max_team_size'])) {
        $max = max(1, min(10, (int)$input['max_team_size']));
        $updates[] = "max_team_size = ?";
        $params[] = $max;
    }
    
    if (isset($input['show_reviewer_name'])) {
        $updates[] = "show_reviewer_name = ?";
        $params[] = (int)(bool)$input['show_reviewer_name'];
    }
    
    if (isset($input['leader_transfer'])) {
        $updates[] = "leader_transfer = ?";
        $params[] = (int)(bool)$input['leader_transfer'];
    }

    if (isset($input['enabled_languages'])) {
        $allLangs = ['ar', 'en', 'de'];
        $requested = is_array($input['enabled_languages']) ? $input['enabled_languages'] : explode(',', $input['enabled_languages']);
        $valid = array_filter($requested, fn($l) => in_array($l, $allLangs));
        if (empty($valid)) $valid = ['ar'];
        $updates[] = "enabled_languages = ?";
        $params[] = implode(',', $valid);
    }

    if (isset($input['login_methods'])) {
        $allowed = ['both', 'email_only', 'student_code_only'];
        $val = in_array($input['login_methods'], $allowed) ? $input['login_methods'] : 'both';
        $updates[] = "login_methods = ?";
        $params[] = $val;
    }
    
    if (empty($updates)) {
        jsonResponse(['error' => 'قيمة مطلوبة'], 400);
    }
    
    $sql = "UPDATE settings SET " . implode(', ', $updates) . " WHERE id = 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    $settings = getSettings();
    jsonResponse([
        'success' => true,
        'settings' => $settings,
        'message' => 'تم حفظ الإعدادات'
    ]);
}

jsonResponse(['error' => 'Method not allowed'], 405);
