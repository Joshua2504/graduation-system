<?php
/**
 * API: System settings (registration_open toggle)
 * 
 * GET  /api/settings.php       — Get current settings
 * POST /api/settings.php       — Update settings (doctor only)
 */
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $settings = getSettings();
    jsonResponse($settings);
}

if ($method === 'POST') {
    require_role('doctor', true);
    
    $input = json_decode(file_get_contents('php://input'), true);
    $regOpen = isset($input['registration_open']) ? (int)(bool)$input['registration_open'] : null;
    
    if ($regOpen === null) {
        jsonResponse(['error' => 'قيمة مطلوبة'], 400);
    }
    
    $pdo = getDB();
    $stmt = $pdo->prepare("UPDATE settings SET registration_open = ? WHERE id = 1");
    $stmt->execute([$regOpen]);
    
    jsonResponse([
        'success' => true,
        'registration_open' => $regOpen,
        'message' => $regOpen ? 'تم فتح التسجيل' : 'تم إغلاق التسجيل'
    ]);
}

jsonResponse(['error' => 'Method not allowed'], 405);
