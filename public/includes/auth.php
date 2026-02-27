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
        header('Location: /login');
        exit;
    }
}

/**
 * Check if user has the required role.
 */
function require_role(string $role, bool $isApi = false): void {
    require_login($isApi);
    $currentRole = $_SESSION['role'] ?? '';
    
    // Admin can access doctor/professor pages too
    if ($role === 'doctor' && $currentRole === 'admin') {
        return;
    }
    
    if ($currentRole !== $role) {
        if ($isApi) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'غير مصرح بالوصول', 'error_en' => 'Forbidden']);
            exit;
        }
        // Redirect to appropriate dashboard
        $r = $currentRole;
        if ($r === 'admin') {
            header('Location: /admin/dashboard');
        } elseif ($r === 'doctor') {
            header('Location: /professor/dashboard');
        } else {
            header('Location: /student/dashboard');
        }
        exit;
    }
}

/**
 * Require admin role specifically (no fallback)
 */
function require_admin(bool $isApi = false): void {
    require_login($isApi);
    if (($_SESSION['role'] ?? '') !== 'admin') {
        if ($isApi) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'غير مصرح بالوصول - مدير النظام فقط', 'error_en' => 'Forbidden - Admin only']);
            exit;
        }
        $r = $_SESSION['role'] ?? 'student';
        if ($r === 'doctor') {
            header('Location: /professor/dashboard');
        } else {
            header('Location: /student/dashboard');
        }
        exit;
    }
}

/**
 * Check if current user is admin
 */
function is_admin(): bool {
    return ($_SESSION['role'] ?? '') === 'admin';
}

/**
 * Check if current user is admin or doctor
 */
function is_admin_or_doctor(): bool {
    $role = $_SESSION['role'] ?? '';
    return $role === 'admin' || $role === 'doctor';
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

/**
 * Check if current session is an impersonation
 */
function is_impersonating(): bool {
    return !empty($_SESSION['impersonator_id']);
}

/**
 * Stop impersonation and restore original doctor session
 */
function stop_impersonation(): void {
    if (!is_impersonating()) return;
    $_SESSION['user_id'] = $_SESSION['impersonator_id'];
    $_SESSION['name'] = $_SESSION['impersonator_name'];
    $_SESSION['email'] = $_SESSION['impersonator_email'];
    $_SESSION['role'] = $_SESSION['impersonator_role'];
    unset(
        $_SESSION['impersonator_id'],
        $_SESSION['impersonator_name'],
        $_SESSION['impersonator_email'],
        $_SESSION['impersonator_role'],
        $_SESSION['impersonator_return_to']
    );
}
