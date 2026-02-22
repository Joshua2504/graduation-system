<?php
/**
 * Registration page (Team Leader / Student)
 */
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/lang.php';

// If already logged in, redirect
if (is_logged_in()) {
    redirect('/');
}

// Check if registration is open
$settings = getSettings();
$regOpen = (bool)$settings['registration_open'];

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $regOpen) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $studentCode = trim($_POST['student_code'] ?? '');

    // Validate
    if (empty($name) || empty($email) || empty($password) || empty($studentCode)) {
        $error = __('required_field');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = getLang() === 'ar' ? 'البريد الإلكتروني غير صالح' : 'Invalid email address';
    } elseif (strlen($password) < 6) {
        $error = getLang() === 'ar' ? 'كلمة المرور يجب أن تكون 6 أحرف على الأقل' : 'Password must be at least 6 characters';
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
                // Create user
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("INSERT INTO users (name, email, password, student_code, role) VALUES (?, ?, ?, ?, 'student')");
                $stmt->execute([$name, $email, $hashedPassword, $studentCode]);

                // Auto login
                $userId = $pdo->lastInsertId();
                $_SESSION['user_id'] = (int)$userId;
                $_SESSION['name'] = $name;
                $_SESSION['email'] = $email;
                $_SESSION['role'] = 'student';

                redirect('/student/dashboard.php');
            }
        }
    }
}

$pageTitle = __('register_title');
require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="row justify-content-center mt-5">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-body p-4">
                    <div class="text-center mb-4">
                        <i class="bi bi-mortarboard-fill text-primary" style="font-size: 3rem;"></i>
                        <h3 class="mt-2"><?= __('register_title') ?></h3>
                        <p class="text-muted"><?= __('app_name') ?></p>
                    </div>

                    <?php if (!$regOpen): ?>
                        <div class="alert alert-warning text-center">
                            <i class="bi bi-lock-fill me-2"></i>
                            <strong><?= __('registration_closed') ?></strong>
                        </div>
                    <?php else: ?>
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
                                    <?= getLang() === 'ar' ? '6 أحرف على الأقل' : 'At least 6 characters' ?>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="student_code" class="form-label"><?= __('student_code') ?> <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="student_code" name="student_code" 
                                       value="<?= sanitize($studentCode ?? '') ?>" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-person-plus me-1"></i><?= __('register') ?>
                            </button>
                        </form>
                    <?php endif; ?>

                    <div class="text-center mt-3">
                        <span class="text-muted"><?= __('has_account') ?></span>
                        <a href="/login.php"><?= __('login') ?></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
