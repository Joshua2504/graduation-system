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
    chmod(DEMO_CREDENTIALS_FILE, 0600);
    return $credentials;
}

/**
 * Seed fully-filled profiles for all demo student accounts.
 * Creates placeholder image files on disk and updates the DB so that
 * isProfileComplete() returns true for every demo student.
 */
function seedDemoStudentProfiles(): void {
    $pdo = getDB();
    $uploadsBase = dirname(__DIR__) . '/uploads';

    // Minimal valid 1×1 white JPEG — recognised by PHP, browsers, and GD
    $placeholderJpeg = "\xff\xd8\xff\xe0\x00\x10\x4a\x46\x49\x46\x00\x01\x01\x00\x00\x01"
        . "\x00\x01\x00\x00\xff\xdb\x00\x43\x00\x08\x06\x06\x07\x06\x05\x08"
        . "\x07\x07\x07\x09\x09\x08\x0a\x0c\x14\x0d\x0c\x0b\x0b\x0c\x19\x12"
        . "\x13\x0f\x14\x1d\x1a\x1f\x1e\x1d\x1a\x1c\x1c\x20\x24\x2e\x27\x20"
        . "\x22\x2c\x23\x1c\x1c\x28\x37\x29\x2c\x30\x31\x34\x34\x34\x1f\x27"
        . "\x39\x3d\x38\x32\x3c\x2e\x33\x34\x32\xff\xc0\x00\x0b\x08\x00\x01"
        . "\x00\x01\x01\x01\x11\x00\xff\xc4\x00\x1f\x00\x00\x01\x05\x01\x01"
        . "\x01\x01\x01\x01\x00\x00\x00\x00\x00\x00\x00\x00\x01\x02\x03\x04"
        . "\x05\x06\x07\x08\x09\x0a\x0b\xff\xc4\x00\xb5\x10\x00\x02\x01\x03"
        . "\x03\x02\x04\x03\x05\x05\x04\x04\x00\x00\x01\x7d\x01\x02\x03\x00"
        . "\x04\x11\x05\x12\x21\x31\x41\x06\x13\x51\x61\x07\x22\x71\x14\x32"
        . "\x81\x91\xa1\x08\x23\x42\xb1\xc1\x15\x52\xd1\xf0\x24\x33\x62\x72"
        . "\x82\x09\x0a\x16\x17\x18\x19\x1a\x25\x26\x27\x28\x29\x2a\x34\x35"
        . "\x36\x37\x38\x39\x3a\x43\x44\x45\x46\x47\x48\x49\x4a\x53\x54\x55"
        . "\x56\x57\x58\x59\x5a\x63\x64\x65\x66\x67\x68\x69\x6a\x73\x74\x75"
        . "\x76\x77\x78\x79\x7a\x83\x84\x85\x86\x87\x88\x89\x8a\x92\x93\x94"
        . "\x95\x96\x97\x98\x99\x9a\xa2\xa3\xa4\xa5\xa6\xa7\xa8\xa9\xaa\xb2"
        . "\xb3\xb4\xb5\xb6\xb7\xb8\xb9\xba\xc2\xc3\xc4\xc5\xc6\xc7\xc8\xc9"
        . "\xca\xd2\xd3\xd4\xd5\xd6\xd7\xd8\xd9\xda\xe1\xe2\xe3\xe4\xe5\xe6"
        . "\xe7\xe8\xe9\xea\xf1\xf2\xf3\xf4\xf5\xf6\xf7\xf8\xf9\xfa\xff\xda"
        . "\x00\x08\x01\x01\x00\x00\x3f\x00\xfb\x26\x8a\x28\x03\xff\xd9";

    // Demo student profile data — indexed by email
    $profiles = [
        'student1@treudler.net' => ['gender' => 'male',   'national_id' => '30101011234567', 'birth_date' => '2001-01-01', 'governorate' => 'القاهرة',     'address' => '15 شارع الجمهورية، القاهرة',       'phone' => '01001234567', 'year' => '4th', 'section' => null],
        'student2@treudler.net' => ['gender' => 'female', 'national_id' => '30202021234568', 'birth_date' => '2002-02-02', 'governorate' => 'الإسكندرية', 'address' => '22 شارع النصر، الإسكندرية',         'phone' => '01101234568', 'year' => '4th', 'section' => null],
        'student3@treudler.net' => ['gender' => 'male',   'national_id' => '30303031234569', 'birth_date' => '2001-03-03', 'governorate' => 'الجيزة',      'address' => '8 شارع الهرم، الجيزة',             'phone' => '01201234569', 'year' => '4th', 'section' => null],
        'student4@treudler.net' => ['gender' => 'female', 'national_id' => '30404041234570', 'birth_date' => '2002-04-04', 'governorate' => 'القاهرة',     'address' => '3 شارع رمسيس، القاهرة',            'phone' => '01501234570', 'year' => '4th', 'section' => null],
        'student5@treudler.net' => ['gender' => 'male',   'national_id' => '30505051234571', 'birth_date' => '2001-05-05', 'governorate' => 'الشرقية',     'address' => '17 شارع الجيش، الزقازيق',          'phone' => '01001234571', 'year' => '4th', 'section' => null],
    ];

    // Resolve the first department name to use as default section
    $deptStmt = $pdo->query("SELECT name FROM departments ORDER BY id ASC LIMIT 1");
    $firstDept = $deptStmt ? $deptStmt->fetchColumn() : null;

    foreach ($profiles as $email => &$p) {
        $p['section'] = $firstDept ?: 'Computer Science';
    }
    unset($p);

    // Fetch student IDs
    $emails = array_keys($profiles);
    $placeholders = implode(',', array_fill(0, count($emails), '?'));
    $stmt = $pdo->prepare("SELECT id, email FROM users WHERE email IN ($placeholders)");
    $stmt->execute($emails);
    $userMap = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $userMap[$row['email']] = (int)$row['id'];
    }

    foreach ($profiles as $email => $profile) {
        if (!isset($userMap[$email])) continue;
        $userId = $userMap[$email];

        // Create upload directory
        $userDir = $uploadsBase . '/user_' . $userId;
        if (!is_dir($userDir)) {
            mkdir($userDir, 0775, true);
        }

        // Write placeholder images
        $images = ['card' => 'card_image', 'national_id' => 'national_id_image', 'receipt' => 'receipt_image'];
        $imageFiles = [];
        foreach ($images as $imgType => $dbField) {
            $filename = $userId . '_' . $imgType . '.jpg';
            $filepath = $userDir . '/' . $filename;
            if (!file_exists($filepath)) {
                file_put_contents($filepath, $placeholderJpeg);
                chmod($filepath, 0644);
            }
            $imageFiles[$dbField] = $filename;
        }

        // Update profile in DB
        $stmt = $pdo->prepare("UPDATE users SET
            gender = ?, national_id = ?, birth_date = ?, governorate = ?,
            address = ?, phone = ?, year = ?, section = ?,
            card_image = ?, national_id_image = ?, receipt_image = ?,
            profile_completed = 1, account_enabled = 1
            WHERE id = ?");
        $stmt->execute([
            $profile['gender'],
            $profile['national_id'],
            $profile['birth_date'],
            $profile['governorate'],
            $profile['address'],
            $profile['phone'],
            $profile['year'],
            $profile['section'],
            $imageFiles['card_image'],
            $imageFiles['national_id_image'],
            $imageFiles['receipt_image'],
            $userId,
        ]);
    }
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
    if ((int)$stmt->fetchColumn() > 0) {
        // Already seeded — but still ensure student profiles are complete
        // (handles the case where uploads were wiped without a full reset)
        seedDemoStudentProfiles();
        return;
    }

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
        'DEMO0001', 'accepted', 'WG01', NOW(), ?
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

    // Fill in complete profiles for all demo students
    seedDemoStudentProfiles();
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

        // Reset seed user profiles — keep name/student_code, clear customisable fields
        $pdo->prepare("UPDATE users SET
            profile_picture = NULL,
            account_enabled = 1
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
            'DEMO0001', 'accepted', 'WG01', NOW(), ?)");
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

    // Re-seed fully-filled profiles for all demo students (after uploads cleared)
    seedDemoStudentProfiles();

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
