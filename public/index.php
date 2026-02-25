<?php
/**
 * Entry point — redirect to appropriate dashboard or login
 */
require_once __DIR__ . '/includes/bootstrap.php';

if (is_logged_in()) {
    $role = current_role();
    if ($role === 'doctor') {
        redirect('/professor/dashboard');
    } else {
        redirect('/student/dashboard');
    }
} else {
    redirect('/login');
}
