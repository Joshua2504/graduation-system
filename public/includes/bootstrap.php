<?php
/**
 * Bootstrap — common includes for all pages.
 * Loads DB connection, authentication, helper functions, and i18n.
 *
 * Usage: require_once __DIR__ . '/../includes/bootstrap.php';
 *   (or from same dir: require_once __DIR__ . '/bootstrap.php';)
 */

// Never display PHP errors/warnings to the browser
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL);
ini_set('log_errors', '1');

ob_start();

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/demo.php';
require_once __DIR__ . '/lang.php';

// Ensure demo seed data exists when demo mode is active
ensureDemoSeeded();

// Ensure live-mode admin is seeded from env vars (when DEMO_MODE is off)
ensureLiveAdminSeeded();
