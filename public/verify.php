<?php
/**
 * Email verification page
 * 
 * Handles ?token=... from the verification email link.
 */
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/lang.php';

$status = 'invalid'; // invalid | success | already
$message = '';

$token = trim($_GET['token'] ?? '');

if (!empty($token) && preg_match('/^[a-f0-9]{64}$/', $token)) {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT id, email_verified, token_expires_at FROM users WHERE verification_token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if ($user) {
        if ($user['email_verified']) {
            $status = 'already';
            $message = __('verification_already');
        } elseif ($user['token_expires_at'] && strtotime($user['token_expires_at']) < time()) {
            $status = 'invalid';
            $message = __('verification_invalid');
        } else {
            // Verify the user
            $stmt = $pdo->prepare("UPDATE users SET email_verified = 1, verification_token = NULL, token_expires_at = NULL WHERE id = ?");
            $stmt->execute([$user['id']]);
            $status = 'success';
            $message = __('email_verified_success');
        }
    } else {
        $status = 'invalid';
        $message = __('verification_invalid');
    }
} else {
    $message = __('verification_invalid');
}

$pageTitle = __('email_verification_title');
require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="row justify-content-center mt-5">
        <div class="col-md-5">
            <div class="card shadow">
                <div class="card-body p-4 text-center">
                    <?php if ($status === 'success'): ?>
                        <div class="mb-4">
                            <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
                        </div>
                        <h4 class="text-success mb-3"><?= sanitize($message) ?></h4>
                        <a href="/login" class="btn btn-primary btn-lg">
                            <i class="bi bi-box-arrow-in-right me-2"></i><?= __('login') ?>
                        </a>
                    <?php elseif ($status === 'already'): ?>
                        <div class="mb-4">
                            <i class="bi bi-info-circle-fill text-info" style="font-size: 4rem;"></i>
                        </div>
                        <h4 class="text-info mb-3"><?= sanitize($message) ?></h4>
                        <a href="/login" class="btn btn-primary">
                            <i class="bi bi-box-arrow-in-right me-2"></i><?= __('login') ?>
                        </a>
                    <?php else: ?>
                        <div class="mb-4">
                            <i class="bi bi-x-circle-fill text-danger" style="font-size: 4rem;"></i>
                        </div>
                        <h4 class="text-danger mb-3"><?= sanitize($message) ?></h4>
                        <p class="text-muted">
                            <?= __('verification_request_new_link') ?>
                        </p>
                        <a href="/login" class="btn btn-outline-primary">
                            <i class="bi bi-box-arrow-in-right me-2"></i><?= __('login') ?>
                        </a>
                    <?php endif; ?>

                    <hr>
                    <div class="text-center">
                        <?php
                        $otherLang = getLang() === 'ar' ? 'en' : 'ar';
                        $otherLangLabel = getLang() === 'ar' ? 'English' : 'العربية';
                        $currentToken = sanitize($token);
                        ?>
                        <a href="?token=<?= $currentToken ?>&lang=<?= $otherLang ?>" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-translate me-1"></i><?= $otherLangLabel ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
