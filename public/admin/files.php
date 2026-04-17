<?php
/**
 * Admin — Shared Files Management Page
 * Reuses the professor's files management interface with admin auth.
 */
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_admin();

// Include the professor's files management page
// (require_role('doctor') in that file passes through for admin)
require dirname(__DIR__) . '/professor/files.php';
