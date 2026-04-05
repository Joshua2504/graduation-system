<?php
/**
 * Registration page (Team Leader / Student)
 * When demo mode is off and no users exist, the first user becomes a doctor (professor).
 */
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/mailer.php';

// If already logged in, redirect
if (is_logged_in()) {
    redirect('/');
}

// Check if this is the initial setup (no users exist, demo mode off)
$isInitialSetup = false;
if (!isDemoMode()) {
    $pdo = getDB();
    $userCount = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $isInitialSetup = ($userCount === 0);
}

// Check if registration is open
$settings = getSettings();
$regOpen = (bool)$settings['registration_open'];

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($regOpen || $isInitialSetup)) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($isInitialSetup) {
        // Initial setup: create admin account (no student code/year needed)
        if (empty($name) || empty($email) || empty($password)) {
            $error = __('required_field');
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = __('invalid_email');
        } elseif (strlen($password) < 6) {
            $error = __('password_min_length');
        } else {
            $pdo = getDB();
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, email_verified) VALUES (?, ?, ?, 'admin', 1)");
            $stmt->execute([$name, $email, $hashedPassword]);

            $userId = (int)$pdo->lastInsertId();
            session_regenerate_id(true);
            $_SESSION['user_id'] = $userId;
            $_SESSION['name'] = $name;
            $_SESSION['email'] = $email;
            $_SESSION['role'] = 'admin';
            redirect('/admin/dashboard');
        }
    } else {
        // Normal student registration
        $studentCode = trim($_POST['student_code'] ?? '');
        $year = trim($_POST['year'] ?? '4th');

        if (empty($name) || empty($email) || empty($password) || empty($studentCode)) {
            $error = __('required_field');
        } elseif (!in_array($year, ['1st', '2nd', '3rd', '4th'])) {
            $error = __('required_field');
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = __('invalid_email');
        } elseif (strlen($password) < 6) {
            $error = __('password_min_length');
        } elseif (!preg_match('/^[A-Za-z0-9]{1,30}$/', $studentCode)) {
            $error = __('student_code_format');
        } else {
            $pdo = getDB();

            // Check email uniqueness
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = __('email_exists');
            } else {
                // Check student code uniqueness
                $stmt = $pdo->prepare("SELECT id FROM users WHERE student_code = ?");
                $stmt->execute([$studentCode]);
                if ($stmt->fetch()) {
                    $error = __('code_exists');
                } else {
                    // Create user (unverified)
                    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, student_code, year, role, email_verified) VALUES (?, ?, ?, ?, ?, 'student', 0)");
                    $stmt->execute([$name, $email, $hashedPassword, $studentCode, $year]);

                    $userId = (int)$pdo->lastInsertId();

                    // Check if email verification is required
                    $settings = getSettings();
                    if (!empty($settings['email_verification_required'])) {
                        // Generate token and send verification email
                        $token = generateVerificationToken($userId);
                        $sent = sendVerificationEmail($email, $name, $token, getLang());

                        if ($sent) {
                            $success = __('verification_sent');
                        } else {
                            // Email failed but account created — let them resend later
                            $success = __('verification_sent_fallback');
                        }
                    } else {
                        // Email verification disabled — auto-verify and login
                        $pdo->prepare("UPDATE users SET email_verified = 1 WHERE id = ?")->execute([$userId]);
                        session_regenerate_id(true);
                        $_SESSION['user_id'] = $userId;
                        $_SESSION['name'] = $name;
                        $_SESSION['email'] = $email;
                        $_SESSION['role'] = 'student';
                        redirect('/student/dashboard');
                    }
                }
            }
        }
    }
}

$pageTitle = $isInitialSetup ? __('initial_setup_title') : __('register_title');
require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="row justify-content-center mt-5">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-body p-4">
                    <div class="text-center mb-4">
                        <i class="bi bi-mortarboard-fill text-primary" style="font-size: 3rem;"></i>
                        <?php if ($isInitialSetup): ?>
                            <h3 class="mt-2"><?= __('initial_setup_title') ?></h3>
                            <p class="text-muted"><?= __('initial_setup_description') ?></p>
                        <?php else: ?>
                            <h3 class="mt-2"><?= __('register_title') ?></h3>
                            <p class="text-muted"><?= __('app_name') ?></p>
                        <?php endif; ?>
                    </div>

                    <?php if ($isInitialSetup): ?>
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?= sanitize($error) ?></div>
                        <?php endif; ?>

                        <form method="POST" novalidate>
                            <div class="mb-3">
                                <label for="name" class="form-label"><?= __('name') ?> <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="<?= sanitize($name ?? '') ?>" required autofocus>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label"><?= __('email') ?> <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?= sanitize($email ?? '') ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label"><?= __('password') ?> <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="password" name="password" required minlength="6">
                                <div class="form-text">
                                    <?= __('password_hint') ?>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-shield-lock me-1"></i><?= __('initial_setup_submit') ?>
                            </button>
                        </form>

                    <?php elseif (!$regOpen): ?>
                        <div class="alert alert-warning text-center">
                            <i class="bi bi-lock-fill me-2"></i>
                            <strong><?= __('registration_closed') ?></strong>
                        </div>
                    <?php else: ?>
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?= sanitize($error) ?></div>
                        <?php endif; ?>
                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                <i class="bi bi-envelope-check me-2"></i><?= sanitize($success) ?>
                            </div>
                            <div class="text-center mt-3">
                                <a href="/login" class="btn btn-primary">
                                    <i class="bi bi-box-arrow-in-right me-1"></i><?= __('login') ?>
                                </a>
                            </div>
                        <?php else: ?>

                        <form method="POST" novalidate>
                            <div class="mb-3">
                                <label for="name" class="form-label"><?= __('name') ?> <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="<?= sanitize($name ?? '') ?>" required autofocus>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label"><?= __('email') ?> <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?= sanitize($email ?? '') ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label"><?= __('password') ?> <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="password" name="password" required minlength="6">
                                <div class="form-text">
                                    <?= __('password_hint') ?>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="student_code" class="form-label"><?= __('student_code') ?> <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="student_code" name="student_code" 
                                       value="<?= sanitize($studentCode ?? '') ?>" required
                                       maxlength="30" pattern="[A-Za-z0-9]{1,30}"
                                       oninput="this.value=this.value.replace(/[^A-Za-z0-9]/g,'').slice(0,30)">
                                <div class="form-text">
                                    <?= __('student_code_hint') ?>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="year" class="form-label"><?= __('year') ?> <span class="text-danger">*</span></label>
                                <select class="form-select" id="year" name="year" required>
                                    <option value="" disabled <?= empty($year) ? 'selected' : '' ?>>--- <?= __('select_option') ?> ---</option>
                                    <option value="1st" <?= ($year ?? '') === '1st' ? 'selected' : '' ?>><?= __('first_year') ?></option>
                                    <option value="2nd" <?= ($year ?? '') === '2nd' ? 'selected' : '' ?>><?= __('second_year') ?></option>
                                    <option value="3rd" <?= ($year ?? '') === '3rd' ? 'selected' : '' ?>><?= __('third_year') ?></option>
                                    <option value="4th" <?= (($year ?? '4th') === '4th') ? 'selected' : '' ?>><?= __('fourth_year') ?></option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-person-plus me-1"></i><?= __('register') ?>
                            </button>
                        </form>
                    <?php endif; ?>
                    <?php endif; ?>

                    <?php if (!$isInitialSetup): ?>
                    <div class="text-center mt-3">
                        <span class="text-muted"><?= __('has_account') ?></span>
                        <a href="/login"><?= __('login') ?></a>
                    </div>
                    <?php endif; ?>

                    <hr>
                    <div class="text-center d-flex justify-content-center gap-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="toggleTheme()" title="Toggle dark mode">
                            <i class="bi bi-moon-fill" id="themeIcon"></i>
                        </button>
                        <?php require_once __DIR__ . '/includes/lang_switcher.php'; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
