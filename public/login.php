<?php
/**
 * Login page
 */
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/lang.php';
require_once __DIR__ . '/includes/mailer.php';

// If already logged in, redirect
if (is_logged_in()) {
    redirect('/');
}

$error = '';
$showResend = false;
$resendEmail = '';

// Handle resend verification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['resend_verification'])) {
    $resendEmail = trim($_POST['resend_email'] ?? '');
    if (!empty($resendEmail)) {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT id, name, email, email_verified FROM users WHERE email = ? AND role = 'student'");
        $stmt->execute([$resendEmail]);
        $user = $stmt->fetch();
        if ($user && !$user['email_verified']) {
            $token = generateVerificationToken($user['id']);
            sendVerificationEmail($user['email'], $user['name'], $token, getLang());
        }
        // Always show success (don't reveal if email exists)
        $error = ''; // clear
        $success = __('verification_resent');
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = __('required_field');
    } else {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT id, name, email, password, role, email_verified FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Check if account is disabled
            if (isset($user['account_enabled']) && !$user['account_enabled']) {
                $error = __('account_disabled');
            // Check email verification for students (only if setting is enabled)
            } elseif ($user['role'] === 'student' && !$user['email_verified']) {
                $settings = getSettings();
                if (!empty($settings['email_verification_required'])) {
                    $error = __('email_not_verified');
                    $showResend = true;
                    $resendEmail = $user['email'];
                } else {
                    // Verification not required — allow login
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['name'] = $user['name'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['role'] = $user['role'];
                    redirect('/');
                }
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                redirect('/');
            }
        } else {
            $error = __('invalid_credentials');
        }
    }
}

$pageTitle = __('login_title');
require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="row justify-content-center mt-5">
        <div class="col-md-5">
            <div class="card shadow">
                <div class="card-body p-4">
                    <div class="text-center mb-4">
                        <i class="bi bi-mortarboard-fill text-primary" style="font-size: 3rem;"></i>
                        <h3 class="mt-2"><?= __('login_title') ?></h3>
                        <p class="text-muted"><?= __('app_name') ?></p>
                    </div>

                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success"><i class="bi bi-envelope-check me-2"></i><?= sanitize($success) ?></div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= sanitize($error) ?></div>
                        <?php if ($showResend): ?>
                            <form method="POST" class="mb-3">
                                <input type="hidden" name="resend_verification" value="1">
                                <input type="hidden" name="resend_email" value="<?= sanitize($resendEmail) ?>">
                                <button type="submit" class="btn btn-outline-warning btn-sm w-100">
                                    <i class="bi bi-envelope-arrow-up me-1"></i><?= __('resend_verification') ?>
                                </button>
                            </form>
                        <?php endif; ?>
                    <?php endif; ?>

                    <form method="POST" novalidate>
                        <div class="mb-3">
                            <label for="email" class="form-label"><?= __('email') ?></label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?= sanitize($email ?? '') ?>" required autofocus>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label"><?= __('password') ?></label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-box-arrow-in-right me-1"></i><?= __('login') ?>
                        </button>
                    </form>

                    <div class="text-center mt-3">
                        <span class="text-muted"><?= __('no_account') ?></span>
                        <a href="/register.php"><?= __('register') ?></a>
                    </div>

                    <hr>
                    <div class="text-center">
                        <?php
                        $otherLang = getLang() === 'ar' ? 'en' : 'ar';
                        $otherLangLabel = getLang() === 'ar' ? 'English' : 'العربية';
                        ?>
                        <a href="?lang=<?= $otherLang ?>" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-translate me-1"></i><?= $otherLangLabel ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
