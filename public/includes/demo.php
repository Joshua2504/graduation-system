<?php
/**
 * Demo mode helpers
 *
 * When DEMO_MODE=true in .env, the system runs in demo mode:
 * - Quick-login buttons on the login page (doctor / demo1)
 * - 30-minute auto-reset timer after any user logs in
 * - Countdown banner above the navbar
 */

require_once __DIR__ . '/db.php';

/** File that stores the UNIX timestamp when the next demo reset should happen */
define('DEMO_RESET_FILE', sys_get_temp_dir() . '/demo_reset_at');

/** Demo reset interval in seconds (30 minutes) */
define('DEMO_RESET_INTERVAL', 30 * 60);

/**
 * Check if demo mode is active
 */
function isDemoMode(): bool {
    $val = $_ENV['DEMO_MODE'] ?? getenv('DEMO_MODE') ?: 'false';
    return filter_var($val, FILTER_VALIDATE_BOOLEAN);
}

/**
 * Get the UNIX timestamp when the demo will reset (0 = no timer set)
 */
function getDemoResetAt(): int {
    if (!file_exists(DEMO_RESET_FILE)) return 0;
    $ts = (int) trim(file_get_contents(DEMO_RESET_FILE));
    return $ts > 0 ? $ts : 0;
}

/**
 * Start or restart the 30-minute demo reset timer.
 * Called on every successful login while demo mode is active.
 */
function scheduleDemoReset(): void {
    if (!isDemoMode()) return;
    file_put_contents(DEMO_RESET_FILE, (string)(time() + DEMO_RESET_INTERVAL));
}

/**
 * Reset all demo data back to the seed state.
 * - Deletes all non-seed users, projects, invitations, members
 * - Resets settings to defaults
 * - Clears upload directories
 * - Clears the reset timer
 */
function performDemoReset(): void {
    $pdo = getDB();

    // Seed emails that should survive the reset
    $seedEmails = [
        'doctor@treudler.net',
        'student@treudler.net',
        'student1@treudler.net',
        'student2@treudler.net',
        'student3@treudler.net',
        'student4@treudler.net',
    ];

    $placeholders = implode(',', array_fill(0, count($seedEmails), '?'));

    $pdo->beginTransaction();
    try {
        // Delete all invitations
        $pdo->exec("DELETE FROM invitations");

        // Delete all project members
        $pdo->exec("DELETE FROM project_members");

        // Delete all projects
        $pdo->exec("DELETE FROM projects");

        // Delete non-seed users
        $stmt = $pdo->prepare("DELETE FROM users WHERE email NOT IN ($placeholders)");
        $stmt->execute($seedEmails);

        // Reset seed user profiles back to defaults (clear any modifications)
        $pdo->prepare("UPDATE users SET
            gender = NULL, national_id = NULL, birth_date = NULL,
            governorate = NULL, address = NULL, phone = NULL,
            section = NULL, profile_picture = NULL,
            card_image = NULL, national_id_image = NULL, receipt_image = NULL,
            profile_completed = 0, account_enabled = 1
            WHERE email IN ($placeholders)")
            ->execute($seedEmails);

        // Reset settings to defaults
        $pdo->exec("UPDATE settings SET
            registration_open = 1,
            email_verification_required = 1,
            min_team_size = 2,
            max_team_size = 7,
            student_project_creation = 1
            WHERE id = 1");

        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }

    // Clear upload directories
    $uploadsDir = dirname(__DIR__) . '/uploads';
    if (is_dir($uploadsDir)) {
        $items = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($uploadsDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($items as $item) {
            if ($item->isDir()) {
                @rmdir($item->getRealPath());
            } else {
                @unlink($item->getRealPath());
            }
        }
    }

    // Clear all sessions except current
    $sessDir = session_save_path() ?: '/var/lib/php/sessions';
    if (is_dir($sessDir)) {
        foreach (glob($sessDir . '/sess_*') as $sessFile) {
            @unlink($sessFile);
        }
    }

    // Clear the reset timer
    if (file_exists(DEMO_RESET_FILE)) {
        @unlink(DEMO_RESET_FILE);
    }
}
