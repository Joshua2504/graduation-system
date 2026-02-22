<?php
/**
 * Authentication & session helpers
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if user is logged in, redirect to login if not.
 * For API calls, returns JSON 401 instead.
 */
function require_login(bool $isApi = false): void {
    if (empty($_SESSION['user_id'])) {
        if ($isApi) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'غير مصرح - يرجى تسجيل الدخول', 'error_en' => 'Unauthorized']);
            exit;
        }
        header('Location: /login.php');
        exit;
    }
}

/**
 * Check if user has the required role.
 */
function require_role(string $role, bool $isApi = false): void {
    require_login($isApi);
    if (($_SESSION['role'] ?? '') !== $role) {
        if ($isApi) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'غير مصرح بالوصول', 'error_en' => 'Forbidden']);
            exit;
        }
        // Redirect to appropriate dashboard
        $r = $_SESSION['role'] ?? 'student';
        header('Location: /' . ($r === 'doctor' ? 'professor' : 'student') . '/dashboard.php');
        exit;
    }
}

/**
 * Get current user ID from session
 */
function current_user_id(): ?int {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user role from session
 */
function current_role(): ?string {
    return $_SESSION['role'] ?? null;
}

/**
 * Check if user is logged in (without redirecting)
 */
function is_logged_in(): bool {
    return !empty($_SESSION['user_id']);
}
