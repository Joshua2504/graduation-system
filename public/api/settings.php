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
    require_role('doctor', true);
    
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
