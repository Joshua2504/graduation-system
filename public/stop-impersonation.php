<?php
/**
 * Stop impersonation and return to doctor session
 */
require_once __DIR__ . '/includes/bootstrap.php';

if (is_impersonating()) {
    stop_impersonation();
    $role = $_SESSION['role'] ?? 'doctor';
    if ($role === 'admin') {
        header('Location: /admin/professors');
    } else {
        header('Location: /professor/students');
    }
} else {
    header('Location: /');
}
exit;
