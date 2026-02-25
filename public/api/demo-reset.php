<?php
/**
 * Demo reset API
 *
 * GET  — returns { demo: bool, reset_at: int } (current timer state)
 * POST — triggers an immediate demo reset (only in demo mode)
 */

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/demo.php';

header('Content-Type: application/json; charset=utf-8');

if (!isDemoMode()) {
    jsonResponse(['error' => 'Demo mode is not active'], 403);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $resetAt = getDemoResetAt();
    jsonResponse([
        'demo'     => true,
        'reset_at' => $resetAt,
        'now'      => time(),
    ]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        performDemoReset();
        jsonResponse(['success' => true, 'message' => 'Demo has been reset']);
    } catch (Exception $e) {
        jsonResponse(['error' => 'Reset failed: ' . $e->getMessage()], 500);
    }
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
