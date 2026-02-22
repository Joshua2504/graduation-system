<?php
/**
 * Professor — Settings Page (toggle registration)
 */
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/lang.php';

require_role('doctor');

$pdo = getDB();
$settings = getSettings();
$isAr = getLang() === 'ar';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $regOpen = isset($_POST['registration_open']) ? 1 : 0;
    $stmt = $pdo->prepare("UPDATE settings SET registration_open = ? WHERE id = 1");
    $stmt->execute([$regOpen]);
    $settings['registration_open'] = $regOpen;
    $message = $regOpen 
        ? ($isAr ? 'تم فتح التسجيل بنجاح' : 'Registration opened successfully')
        : ($isAr ? 'تم إغلاق التسجيل بنجاح' : 'Registration closed successfully');
}

$pageTitle = __('settings');
require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/includes/navbar.php';
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-gear me-2"></i><?= __('settings') ?></h5>
                </div>
                <div class="card-body p-4">
                    <?php if ($message): ?>
                        <div class="alert alert-success"><?= sanitize($message) ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded mb-3">
                            <div>
                                <h6 class="mb-1"><?= __('toggle_registration') ?></h6>
                                <small class="text-muted">
                                    <?= $isAr 
                                        ? 'التحكم في إمكانية تسجيل حسابات جديدة للطلاب'
                                        : 'Control whether new student accounts can be registered' ?>
                                </small>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" 
                                       id="registration_open" name="registration_open" 
                                       <?= $settings['registration_open'] ? 'checked' : '' ?>
                                       style="width: 3em; height: 1.5em;">
                            </div>
                        </div>

                        <div class="text-center">
                            <span class="badge fs-6 <?= $settings['registration_open'] ? 'bg-success' : 'bg-danger' ?>">
                                <i class="bi bi-<?= $settings['registration_open'] ? 'unlock' : 'lock' ?> me-1"></i>
                                <?= $settings['registration_open'] ? __('registration_open') : __('registration_locked') ?>
                            </span>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 mt-4">
                            <i class="bi bi-check-lg me-2"></i><?= __('save') ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
