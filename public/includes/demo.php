<?php
/**
 * Demo mode helpers
 *
 * When DEMO_MODE=true in .env, the system runs in demo mode:
 * - Quick-login buttons on the login page (doctor / student1)
 * - Random passwords generated on first boot and each reset
 * - 30-minute auto-reset timer after any user logs in
 * - Countdown banner above the navbar
 */

require_once __DIR__ . '/db.php';

/** File that stores the UNIX timestamp when the next demo reset should happen */
define('DEMO_RESET_FILE', sys_get_temp_dir() . '/demo_reset_at');

/** File that stores the current demo credentials as JSON */
define('DEMO_CREDENTIALS_FILE', sys_get_temp_dir() . '/demo_credentials.json');

/** Demo reset interval in seconds (30 minutes) */
define('DEMO_RESET_INTERVAL', 30 * 60);

/** Demo seed accounts — email => [name, role, student_code] */
define('DEMO_SEED_ACCOUNTS', [
    'admin@treudler.net'    => ['name' => 'Admin',       'role' => 'admin',   'student_code' => null],
    'doctor@treudler.net'   => ['name' => 'Doctor',      'role' => 'doctor',  'student_code' => null],
    'student1@treudler.net' => ['name' => 'Student 1',   'role' => 'student', 'student_code' => '001'],
    'student2@treudler.net' => ['name' => 'Student 2',   'role' => 'student', 'student_code' => '002'],
    'student3@treudler.net' => ['name' => 'Student 3',   'role' => 'student', 'student_code' => '003'],
    'student4@treudler.net' => ['name' => 'Student 4',   'role' => 'student', 'student_code' => '004'],
    'student5@treudler.net' => ['name' => 'Student 5',   'role' => 'student', 'student_code' => '005'],
]);

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
 * Generate a random 8-char alphanumeric password
 */
function generateDemoPassword(): string {
    return substr(str_shuffle('abcdefghjkmnpqrstuvwxyz23456789'), 0, 8);
}

/**
 * Get current demo credentials. Generates them on first call.
 * Returns array: [ 'doctor@treudler.net' => 'abc123', ... ]
 */
function getDemoCredentials(): array {
    if (file_exists(DEMO_CREDENTIALS_FILE)) {
        $data = json_decode(file_get_contents(DEMO_CREDENTIALS_FILE), true);
        if (is_array($data) && count($data) === count(DEMO_SEED_ACCOUNTS)) {
            return $data;
        }
    }
    // First boot — generate and apply
    return regenerateDemoPasswords();
}

/**
 * Generate new random passwords for all demo accounts, hash & store in DB,
 * and save the plaintext to the credentials file.
 */
function regenerateDemoPasswords(): array {
    $pdo = getDB();
    $credentials = [];

    foreach (DEMO_SEED_ACCOUNTS as $email => $meta) {
        $plain = generateDemoPassword();
        $hash = password_hash($plain, PASSWORD_BCRYPT);
        $credentials[$email] = $plain;

        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->execute([$hash, $email]);
    }

    file_put_contents(DEMO_CREDENTIALS_FILE, json_encode($credentials, JSON_UNESCAPED_UNICODE));
    return $credentials;
}

/**
 * Ensure demo seed accounts and content exist in the database.
 * Called on every page load when demo mode is active (idempotent).
 */
function ensureDemoSeeded(): void {
    if (!isDemoMode()) return;
    $pdo = getDB();

    // Check if the admin seed account already exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
    $stmt->execute(['admin@treudler.net']);
    if ((int)$stmt->fetchColumn() > 0) return; // already seeded

    // Seed demo user accounts with random passwords
    foreach (DEMO_SEED_ACCOUNTS as $email => $meta) {
        $plain = generateDemoPassword();
        $hash = password_hash($plain, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("INSERT IGNORE INTO users (name, email, password, student_code, role, email_verified) VALUES (?, ?, ?, ?, ?, 1)");
        $stmt->execute([$meta['name'], $email, $hash, $meta['student_code'], $meta['role']]);
    }

    // Save generated credentials
    regenerateDemoPasswords();

    // Seed demo projects
    $doctorId = $pdo->query("SELECT id FROM users WHERE email = 'doctor@treudler.net' LIMIT 1")->fetchColumn();

    $stmt = $pdo->prepare("INSERT INTO projects (title, type, description, join_code, status, group_number, submission_date, reviewed_by)
        SELECT 'Library Management System', 'Web Application',
        'A comprehensive library management system that allows registering books and members, handling borrowing and return operations, and generating periodic reports. The system includes a user-friendly interface for patrons and an advanced admin panel for librarians.',
        'DEMO0001', 'accepted', 1, NOW(), ?
        FROM dual WHERE NOT EXISTS (SELECT 1 FROM projects WHERE join_code = 'DEMO0001')");
    $stmt->execute([$doctorId ?: null]);

    $pdo->exec("INSERT INTO projects (title, type, description, join_code, status, submission_date)
        SELECT 'Fitness Tracking App', 'Mobile Application',
        'A smartphone application that helps users track their physical activity, log workouts, count calories, and monitor progress toward their health goals.',
        'DEMO0002', 'under_review', NOW()
        FROM dual WHERE NOT EXISTS (SELECT 1 FROM projects WHERE join_code = 'DEMO0002')");

    // Seed demo project members
    $memberInserts = [
        ['DEMO0001', 'student1@treudler.net', 'leader'],
        ['DEMO0001', 'student2@treudler.net', 'member'],
        ['DEMO0001', 'student3@treudler.net', 'member'],
        ['DEMO0002', 'student4@treudler.net', 'leader'],
        ['DEMO0002', 'student5@treudler.net', 'member'],
    ];
    $memberStmt = $pdo->prepare("INSERT IGNORE INTO project_members (project_id, user_id, role)
        SELECT p.id, u.id, ? FROM projects p, users u WHERE p.join_code = ? AND u.email = ?");
    foreach ($memberInserts as [$code, $email, $role]) {
        $memberStmt->execute([$role, $code, $email]);
    }

    // Set enabled_languages to all for demo mode
    $pdo->exec("UPDATE settings SET enabled_languages = 'ar,en,de' WHERE id = 1");
}

/**
 * Reset all demo data back to the seed state.
 * - Deletes all non-seed users, projects, invitations, members
 * - Resets settings to defaults
 * - Regenerates random passwords for all demo accounts
 * - Clears upload directories
 * - Clears the reset timer
 */
function performDemoReset(): void {
    $pdo = getDB();

    $seedEmails = array_keys(DEMO_SEED_ACCOUNTS);
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

        // Reset settings to defaults (demo mode enables all languages)
        $pdo->exec("UPDATE settings SET
            registration_open = 1,
            email_verification_required = 1,
            min_team_size = 2,
            max_team_size = 7,
            student_project_creation = 1,
            show_reviewer_name = 0,
            enabled_languages = 'ar,en,de'
            WHERE id = 1");

        // Get doctor ID for reviewed_by
        $doctorId = $pdo->query("SELECT id FROM users WHERE email = 'doctor@treudler.net' LIMIT 1")->fetchColumn();

        // Re-seed demo projects
        $stmt = $pdo->prepare("INSERT INTO projects (title, type, description, join_code, status, group_number, submission_date, reviewed_by)
            VALUES ('Library Management System', 'Web Application',
            'A comprehensive library management system that allows registering books and members, handling borrowing and return operations, and generating periodic reports. The system includes a user-friendly interface for patrons and an advanced admin panel for librarians.',
            'DEMO0001', 'accepted', 1, NOW(), ?)");
        $stmt->execute([$doctorId ?: null]);
        $proj1 = $pdo->lastInsertId();

        $pdo->exec("INSERT INTO projects (title, type, description, join_code, status, submission_date)
            VALUES ('Fitness Tracking App', 'Mobile Application',
            'A smartphone application that helps users track their physical activity, log workouts, count calories, and monitor progress toward their health goals.',
            'DEMO0002', 'under_review', NOW())");
        $proj2 = $pdo->lastInsertId();

        // Re-seed demo project members
        $seedUserIds = [];
        $stmt = $pdo->prepare("SELECT id, email FROM users WHERE email IN ($placeholders)");
        $stmt->execute($seedEmails);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $seedUserIds[$row['email']] = $row['id'];
        }

        // Project 1: student1 = leader, student2 & student3 = members
        $memberStmt = $pdo->prepare("INSERT INTO project_members (project_id, user_id, role) VALUES (?, ?, ?)");
        if (isset($seedUserIds['student1@treudler.net'])) {
            $memberStmt->execute([$proj1, $seedUserIds['student1@treudler.net'], 'leader']);
        }
        if (isset($seedUserIds['student2@treudler.net'])) {
            $memberStmt->execute([$proj1, $seedUserIds['student2@treudler.net'], 'member']);
        }
        if (isset($seedUserIds['student3@treudler.net'])) {
            $memberStmt->execute([$proj1, $seedUserIds['student3@treudler.net'], 'member']);
        }

        // Project 2: student4 = leader, student5 = member
        if (isset($seedUserIds['student4@treudler.net'])) {
            $memberStmt->execute([$proj2, $seedUserIds['student4@treudler.net'], 'leader']);
        }
        if (isset($seedUserIds['student5@treudler.net'])) {
            $memberStmt->execute([$proj2, $seedUserIds['student5@treudler.net'], 'member']);
        }

        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }

    // Regenerate random passwords for all demo accounts
    regenerateDemoPasswords();

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

    // Clear all sessions
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
