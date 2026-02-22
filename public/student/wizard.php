<?php
/**
 * Student Wizard Shell — JS takes over rendering
 */
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/lang.php';

require_role('student');

$userId = current_user_id();
$project = getUserProject($userId);

// If project is accepted or under_review, redirect to dashboard
if ($project && in_array($project['status'], ['under_review', 'accepted'])) {
    redirect('/student/dashboard.php');
}

$pageTitle = __('project_details');
$lang = getLang();
$dir = getDir();
$bootstrapCss = $lang === 'ar' 
    ? 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css'
    : 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css';

// Prepare initial state for JS
$initialState = [
    'userId' => $userId,
    'projectId' => $project['id'] ?? null,
    'projectData' => $project,
    'students' => $project ? getProjectStudents($project['id']) : [],
    'lang' => $lang,
    'governorates' => getGovernorates(),
    'projectTypes' => getProjectTypes(),
];
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>" dir="<?= $dir ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize($pageTitle) ?></title>
    <link rel="stylesheet" href="<?= $bootstrapCss ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="bg-light">

<?php require_once dirname(__DIR__) . '/includes/navbar.php'; ?>

<div class="container">
    <div id="wizard-root">
        <!-- JS will render here -->
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden"><?= __('loading') ?></span>
            </div>
            <p class="mt-3 text-muted"><?= __('loading') ?></p>
        </div>
    </div>
</div>

<!-- Toast for auto-save -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1100">
    <div id="autosave-toast" class="toast align-items-center text-white bg-success border-0" role="alert" data-bs-delay="2000">
        <div class="d-flex">
            <div class="toast-body">
                <i class="bi bi-check-circle me-2"></i><?= __('auto_saved') ?>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<!-- Initial state from PHP -->
<script>
    window.WIZARD_STATE = <?= json_encode($initialState, JSON_UNESCAPED_UNICODE) ?>;
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="/assets/js/uploader.js"></script>
<script src="/assets/js/autosave.js"></script>
<script src="/assets/js/steps/step1.js"></script>
<script src="/assets/js/steps/studentStep.js"></script>
<script src="/assets/js/steps/step9.js"></script>
<script src="/assets/js/wizard.js"></script>

</body>
</html>
