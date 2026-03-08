<?php
/**
 * Bootstrap — common includes for all pages.
 * Loads DB connection, authentication, helper functions, and i18n.
 * 
 * Usage: require_once __DIR__ . '/../includes/bootstrap.php';
 *   (or from same dir: require_once __DIR__ . '/bootstrap.php';)
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/demo.php';
require_once __DIR__ . '/lang.php';

// Ensure demo seed data exists when demo mode is active
ensureDemoSeeded();
