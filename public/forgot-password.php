<?php
/**
 * Forgot Password page
 *
 * Accepts an email address and sends a password reset link.
 * Works for both students and professors.
 */
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/mailer.php';

// Redirect already-authenticated users
if (is_logged_in()) {
    redirect('/');
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        $error = __('required_field');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = __('invalid_email_format');
    } else {
        $pdo = getDB();
        $stmt = $pdo->prepare(
            "SELECT id, name, email, account_enabled FROM users WHERE email = ? AND role IN ('student','doctor','admin')"
        );
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // Always show the same message to prevent email enumeration
        if ($user && $user['account_enabled']) {
            $token = generatePasswordResetToken($user['id']);
            sendPasswordResetEmail($user['email'], $user['name'], $token, getLang());
        }

        $success = __('password_reset_sent');
    }
}

$pageTitle = __('forgot_password_title');
require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="row justify-content-center mt-5">
        <div class="col-md-5">
            <div class="card shadow">
                <div class="card-body p-4">
                    <div class="text-center mb-4">
                        <i class="bi bi-key-fill text-danger" style="font-size: 2.5rem;"></i>
                        <h3 class="mt-2 mb-1"><?= __('forgot_password_title') ?></h3>
                        <p class="text-muted small mb-0"><?= __('forgot_password_desc') ?></p>
                    </div>

                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <i class="bi bi-envelope-check me-2"></i><?= sanitize($success) ?>
                        </div>
                        <div class="text-center">
                            <a href="/login" class="btn btn-primary">
                                <i class="bi bi-box-arrow-in-right me-1"></i><?= __('back_to_login') ?>
                            </a>
                        </div>
                    <?php else: ?>
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?= sanitize($error) ?></div>
                        <?php endif; ?>

                        <form method="POST" novalidate>
                            <div class="mb-3">
                                <label for="email" class="form-label"><?= __('email') ?></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                    <input type="email" class="form-control" id="email" name="email"
                                           value="<?= sanitize($_POST['email'] ?? '') ?>"
                                           required autofocus
                                           placeholder="<?= __('email') ?>">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-danger w-100 py-2">
                                <i class="bi bi-send me-1"></i><?= __('forgot_password_submit') ?>
                            </button>
                        </form>

                        <div class="text-center mt-3">
                            <a href="/login" class="text-muted small">
                                <i class="bi bi-arrow-<?= getLang() === 'ar' ? 'right' : 'left' ?> me-1"></i><?= __('back_to_login') ?>
                            </a>
                        </div>
                    <?php endif; ?>

                    <hr>
                    <div class="text-center">
                        <?php require __DIR__ . '/includes/lang_switcher.php'; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
