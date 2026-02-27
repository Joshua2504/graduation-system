<?php
/**
 * Professor — Settings Page (toggle registration)
 */
require_once dirname(__DIR__) . '/includes/bootstrap.php';

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
    $studentCreation = isset($_POST['student_project_creation']) ? 1 : 0;
    $showReviewerName = isset($_POST['show_reviewer_name']) ? 1 : 0;
    $leaderTransfer = isset($_POST['leader_transfer']) ? 1 : 0;
    $profilePicturesEnabled = isset($_POST['profile_pictures_enabled']) ? 1 : 0;

    // Enabled languages — at least one must be selected
    $allLangs = ['ar', 'en', 'de'];
    $selectedLangs = array_filter($allLangs, fn($l) => isset($_POST['lang_' . $l]));
    if (empty($selectedLangs)) $selectedLangs = ['ar']; // fallback
    $enabledLanguages = implode(',', $selectedLangs);

    // Login methods
    $loginMethods = $_POST['login_methods'] ?? 'both';
    if (!in_array($loginMethods, ['both', 'email_only', 'student_code_only'])) $loginMethods = 'both';

    $stmt = $pdo->prepare("UPDATE settings SET registration_open = ?, email_verification_required = ?, min_team_size = ?, max_team_size = ?, student_project_creation = ?, show_reviewer_name = ?, leader_transfer = ?, enabled_languages = ?, login_methods = ?, profile_pictures_enabled = ? WHERE id = 1");
    $stmt->execute([$regOpen, $emailVerReq, $minTeam, $maxTeam, $studentCreation, $showReviewerName, $leaderTransfer, $enabledLanguages, $loginMethods, $profilePicturesEnabled]);
    $settings['registration_open'] = $regOpen;
    $settings['email_verification_required'] = $emailVerReq;
    $settings['min_team_size'] = $minTeam;
    $settings['max_team_size'] = $maxTeam;
    $settings['student_project_creation'] = $studentCreation;
    $settings['show_reviewer_name'] = $showReviewerName;
    $settings['leader_transfer'] = $leaderTransfer;
    $settings['enabled_languages'] = $enabledLanguages;
    $settings['login_methods'] = $loginMethods;
    $settings['profile_pictures_enabled'] = $profilePicturesEnabled;
    $message = __('settings_saved');
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
                                    <?= __('registration_description') ?>
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
                                    <?= __('email_verification_description') ?>
                                </small>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" 
                                       id="email_verification_required" name="email_verification_required" 
                                       <?= !empty($settings['email_verification_required']) ? 'checked' : '' ?>
                                       style="width: 3em; height: 1.5em;">
                            </div>
                        </div>

                        <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded mb-3">
                            <div>
                                <h6 class="mb-1"><?= __('toggle_student_project_creation') ?></h6>
                                <small class="text-muted">
                                    <?= __('student_project_creation_description') ?>
                                </small>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" 
                                       id="student_project_creation" name="student_project_creation" 
                                       <?= !empty($settings['student_project_creation']) ? 'checked' : '' ?>
                                       style="width: 3em; height: 1.5em;">
                            </div>
                        </div>

                        <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded mb-3">
                            <div>
                                <h6 class="mb-1"><?= __('toggle_show_reviewer_name') ?></h6>
                                <small class="text-muted">
                                    <?= __('show_reviewer_name_description') ?>
                                </small>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" 
                                       id="show_reviewer_name" name="show_reviewer_name" 
                                       <?= !empty($settings['show_reviewer_name']) ? 'checked' : '' ?>
                                       style="width: 3em; height: 1.5em;">
                            </div>
                        </div>

                        <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded mb-3">
                            <div>
                                <h6 class="mb-1"><?= __('toggle_leader_transfer') ?></h6>
                                <small class="text-muted">
                                    <?= __('leader_transfer_description') ?>
                                </small>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" 
                                       id="leader_transfer" name="leader_transfer" 
                                       <?= !empty($settings['leader_transfer']) ? 'checked' : '' ?>
                                       style="width: 3em; height: 1.5em;">
                            </div>
                        </div>

                        <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded mb-3">
                            <div>
                                <h6 class="mb-1"><?= __('toggle_profile_pictures') ?></h6>
                                <small class="text-muted">
                                    <?= __('profile_pictures_description') ?>
                                </small>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" 
                                       id="profile_pictures_enabled" name="profile_pictures_enabled" 
                                       <?= !empty($settings['profile_pictures_enabled']) ? 'checked' : '' ?>
                                       style="width: 3em; height: 1.5em;">
                            </div>
                        </div>

                        <!-- Enabled Languages -->
                        <div class="p-3 bg-light rounded mb-3">
                            <h6 class="mb-2"><i class="bi bi-translate me-1"></i><?= __('enabled_languages') ?></h6>
                            <small class="text-muted d-block mb-3">
                                <?= __('enabled_languages_description') ?>
                            </small>
                            <?php
                            $enabledLangs = explode(',', $settings['enabled_languages'] ?? 'ar');
                            $langOptions = [
                                'ar' => 'العربية (Arabic)',
                                'en' => 'English',
                                'de' => 'Deutsch (German)',
                            ];
                            ?>
                            <?php foreach ($langOptions as $code => $label): ?>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="lang_<?= $code ?>" name="lang_<?= $code ?>"
                                       <?= in_array($code, $enabledLangs) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="lang_<?= $code ?>"><?= $label ?></label>
                            </div>
                            <?php endforeach; ?>
                            <small class="text-muted"><i class="bi bi-info-circle me-1"></i><?= __('enabled_languages_hint') ?></small>
                        </div>

                        <!-- Login Methods -->
                        <div class="p-3 bg-light rounded mb-3">
                            <h6 class="mb-2"><i class="bi bi-box-arrow-in-right me-1"></i><?= __('login_methods') ?></h6>
                            <small class="text-muted d-block mb-3">
                                <?= __('login_methods_description') ?>
                            </small>
                            <?php $currentLoginMethod = $settings['login_methods'] ?? 'both'; ?>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="login_methods" id="login_both" value="both"
                                       <?= $currentLoginMethod === 'both' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="login_both"><?= __('login_method_both') ?></label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="login_methods" id="login_email_only" value="email_only"
                                       <?= $currentLoginMethod === 'email_only' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="login_email_only"><?= __('login_method_email_only') ?></label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="login_methods" id="login_student_code_only" value="student_code_only"
                                       <?= $currentLoginMethod === 'student_code_only' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="login_student_code_only"><?= __('login_method_student_code_only') ?></label>
                            </div>
                        </div>

                        <!-- Team Size Settings -->
                        <div class="p-3 bg-light rounded mb-3">
                            <h6 class="mb-2"><i class="bi bi-people me-1"></i><?= __('team_size') ?></h6>
                            <small class="text-muted d-block mb-3">
                                <?= __('team_size_description') ?>
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
