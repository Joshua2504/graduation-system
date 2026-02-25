<?php
/**
 * Stop impersonation and return to doctor session
 */
require_once __DIR__ . '/includes/bootstrap.php';

if (is_impersonating()) {
    stop_impersonation();
    header('Location: /professor/students');
} else {
    header('Location: /');
}
exit;
