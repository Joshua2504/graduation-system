<?php
/**
 * Entry point — redirect to appropriate dashboard or login
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) {
    $role = current_role();
    if ($role === 'doctor') {
        redirect('/professor/dashboard.php');
    } else {
        redirect('/student/dashboard.php');
    }
} else {
    redirect('/login.php');
}
