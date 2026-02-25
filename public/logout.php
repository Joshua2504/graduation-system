<?php
/**
 * Logout handler
 */
require_once __DIR__ . '/includes/bootstrap.php';

// If impersonating, stop impersonation instead of full logout
if (is_impersonating()) {
    stop_impersonation();
    header('Location: /professor/students');
    exit;
}

session_unset();
session_destroy();
header('Location: /login');
exit;
