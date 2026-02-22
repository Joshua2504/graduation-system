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
    $emailVerReq = isset($_POST['email_verification_required']) ? 1 : 0;
    $minTeam = max(1, min(10, (int)($_POST['min_team_size'] ?? 2)));
    $maxTeam = max(1, min(10, (int)($_POST['max_team_size'] ?? 7)));
    if ($maxTeam < $minTeam) $maxTeam = $minTeam;
    $stmt = $pdo->prepare("UPDATE settings SET registration_open = ?, email_verification_required = ?, min_team_size = ?, max_team_size = ? WHERE id = 1");
    $stmt->execute([$regOpen, $emailVerReq, $minTeam, $maxTeam]);
    $settings['registration_open'] = $regOpen;
    $settings['email_verification_required'] = $emailVerReq;
    $settings['min_team_size'] = $minTeam;
    $settings['max_team_size'] = $maxTeam;
    $message = $isAr ? 'تم حفظ الإعدادات بنجاح' : 'Settings saved successfully';
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

                        <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded mb-3">
                            <div>
                                <h6 class="mb-1"><?= __('toggle_email_verification') ?></h6>
                                <small class="text-muted">
                                    <?= $isAr 
                                        ? 'عند التفعيل، يجب على الطلاب تأكيد بريدهم الإلكتروني قبل تسجيل الدخول'
                                        : 'When enabled, students must verify their email before logging in' ?>
                                </small>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" 
                                       id="email_verification_required" name="email_verification_required" 
                                       <?= !empty($settings['email_verification_required']) ? 'checked' : '' ?>
                                       style="width: 3em; height: 1.5em;">
                            </div>
                        </div>

                        <!-- Team Size Settings -->
                        <div class="p-3 bg-light rounded mb-3">
                            <h6 class="mb-2"><i class="bi bi-people me-1"></i><?= __('team_size') ?></h6>
                            <small class="text-muted d-block mb-3">
                                <?= $isAr 
                                    ? 'تحديد الحد الأدنى والأقصى لعدد أعضاء الفريق'
                                    : 'Set the minimum and maximum number of team members' ?>
                            </small>
                            <div class="row g-3">
                                <div class="col-6">
                                    <label class="form-label small fw-bold"><?= __('min_members') ?></label>
                                    <input type="number" class="form-control" name="min_team_size" 
                                           value="<?= (int)($settings['min_team_size'] ?? 2) ?>" min="1" max="10">
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-bold"><?= __('max_members') ?></label>
                                    <input type="number" class="form-control" name="max_team_size" 
                                           value="<?= (int)($settings['max_team_size'] ?? 7) ?>" min="1" max="10">
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-2 justify-content-center mb-3">
                            <span class="badge fs-6 <?= $settings['registration_open'] ? 'bg-success' : 'bg-danger' ?>">
                                <i class="bi bi-<?= $settings['registration_open'] ? 'unlock' : 'lock' ?> me-1"></i>
                                <?= $settings['registration_open'] ? __('registration_open') : __('registration_locked') ?>
                            </span>
                            <span class="badge fs-6 <?= !empty($settings['email_verification_required']) ? 'bg-info' : 'bg-secondary' ?>">
                                <i class="bi bi-<?= !empty($settings['email_verification_required']) ? 'envelope-check' : 'envelope-x' ?> me-1"></i>
                                <?= !empty($settings['email_verification_required']) 
                                    ? ($isAr ? 'تأكيد البريد مطلوب' : 'Email Verification On')
                                    : ($isAr ? 'تأكيد البريد معطل' : 'Email Verification Off') ?>
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
