<?php
/**
 * Stop impersonation and return to doctor session
 */
require_once __DIR__ . '/includes/bootstrap.php';

if (is_impersonating()) {
    // Determine return URL before restoring session
    $returnTo = $_SESSION['impersonator_return_to'] ?? null;
    stop_impersonation();
    $role = $_SESSION['role'] ?? 'doctor';
    $allowedReturnPaths = ['/admin/professors', '/admin/students', '/professor/students'];
    if ($returnTo && in_array($returnTo, $allowedReturnPaths)) {
        header('Location: ' . $returnTo);
    } elseif ($role === 'admin') {
        header('Location: /admin/professors');
    } else {
        header('Location: /professor/students');
    }
} else {
    header('Location: /');
}
exit;
