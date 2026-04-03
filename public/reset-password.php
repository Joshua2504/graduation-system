<?php
/**
 * Reset Password page
 *
 * Validates the reset token from the URL and allows the user to set a new password.
 * Works for both students and professors.
 */
require_once __DIR__ . '/includes/bootstrap.php';

// Redirect already-authenticated users
if (is_logged_in()) {
    redirect('/');
}

$token = trim($_GET['token'] ?? '');
$tokenValid = false;
$userId = null;
$success = '';
$error = '';

// Validate token format
if (!empty($token) && preg_match('/^[a-f0-9]{64}$/', $token)) {
    $pdo = getDB();
    $stmt = $pdo->prepare(
        "SELECT id, reset_token_expires_at FROM users WHERE reset_token = ? AND account_enabled = 1"
    );
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if ($user) {
        if ($user['reset_token_expires_at'] && strtotime($user['reset_token_expires_at']) <= time()) {
            $error = __('password_reset_invalid');
        } else {
            $tokenValid = true;
            $userId = $user['id'];
        }
    } else {
        $error = __('password_reset_invalid');
    }
} else {
    $error = __('password_reset_invalid');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tokenValid && $userId) {
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($newPassword) || empty($confirmPassword)) {
        $error = __('required_field');
    } elseif (strlen($newPassword) < 6) {
        $error = __('password_too_short');
    } elseif ($newPassword !== $confirmPassword) {
        $error = __('passwords_do_not_match');
    } else {
        $pdo = getDB();
        $hashed = password_hash($newPassword, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare(
            "UPDATE users SET password = ?, reset_token = NULL, reset_token_expires_at = NULL WHERE id = ?"
        );
        $stmt->execute([$hashed, $userId]);
        $tokenValid = false;
        $success = __('password_reset_success');
    }
}

$pageTitle = __('reset_password_title');
require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="row justify-content-center mt-5">
        <div class="col-md-5">
            <div class="card shadow">
                <div class="card-body p-4">
                    <div class="text-center mb-4">
                        <i class="bi bi-lock-fill text-danger" style="font-size: 2.5rem;"></i>
                        <h3 class="mt-2 mb-1"><?= __('reset_password_title') ?></h3>
                        <?php if ($tokenValid): ?>
                            <p class="text-muted small mb-0"><?= __('reset_password_desc') ?></p>
                        <?php endif; ?>
                    </div>

                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle me-2"></i><?= sanitize($success) ?>
                        </div>
                        <div class="text-center">
                            <a href="/login" class="btn btn-primary">
                                <i class="bi bi-box-arrow-in-right me-1"></i><?= __('back_to_login') ?>
                            </a>
                        </div>
                    <?php elseif (!$tokenValid): ?>
                        <div class="alert alert-danger">
                            <i class="bi bi-x-circle me-2"></i><?= sanitize($error) ?>
                        </div>
                        <div class="text-center">
                            <a href="/forgot-password" class="btn btn-outline-danger">
                                <i class="bi bi-arrow-repeat me-1"></i><?= __('forgot_password_title') ?>
                            </a>
                        </div>
                    <?php else: ?>
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?= sanitize($error) ?></div>
                        <?php endif; ?>

                        <form method="POST" novalidate>
                            <div class="mb-3">
                                <label for="new_password" class="form-label"><?= __('new_password') ?></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                    <input type="password" class="form-control" id="new_password"
                                           name="new_password" required autofocus
                                           minlength="6">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label"><?= __('confirm_new_password') ?></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                                    <input type="password" class="form-control" id="confirm_password"
                                           name="confirm_password" required
                                           minlength="6">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-danger w-100 py-2">
                                <i class="bi bi-check-lg me-1"></i><?= __('reset_password_submit') ?>
                            </button>
                        </form>
                    <?php endif; ?>

                    <hr>
                    <div class="text-center">
                        <?php
                        $langSwitcherExtraParams = 'token=' . urlencode(sanitize($token));
                        require __DIR__ . '/includes/lang_switcher.php';
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
