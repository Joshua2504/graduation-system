<?php
/**
 * Stop impersonation and return to doctor session
 */
require_once __DIR__ . '/includes/auth.php';

if (is_impersonating()) {
    stop_impersonation();
    header('Location: /professor/students.php');
} else {
    header('Location: /');
}
exit;
