<?php
/**
 * Shared navbar component
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/lang.php';
require_once __DIR__ . '/demo.php';

$isLoggedIn = is_logged_in();
$role = current_role();
$userName = $_SESSION['name'] ?? '';
$langLabels = ['ar' => 'العربية', 'en' => 'English', 'de' => 'Deutsch'];
$currentLangLabel = $langLabels[getLang()] ?? 'العربية';

// Load profile picture for logged-in users
$navProfilePic = '';
if ($isLoggedIn) {
    require_once __DIR__ . '/functions.php';
    $navUser = getUserProfile(current_user_id());
    if (!empty($navUser['profile_picture'])) {
        $navProfilePic = secureFileUrl(current_user_id(), $navUser['profile_picture']);
    }
}

// Determine current page for active state
$currentPage = basename($_SERVER['PHP_SELF'], '.php');

// Demo mode: check if reset timer has expired
$demoActive = isDemoMode();
$demoResetAt = 0;
if ($demoActive) {
    $demoResetAt = getDemoResetAt();
    if ($demoResetAt > 0 && time() >= $demoResetAt) {
        performDemoReset();
        session_unset();
        session_destroy();
        header('Location: /login');
        exit;
    }
}
?>

<?php if ($demoActive && $demoResetAt > 0): ?>
<div class="demo-timer-banner text-center py-1" id="demoBanner">
    <i class="bi bi-clock-history me-1"></i>
    <span><?= __('demo_resets_in') ?>:</span>
    <strong id="demoCountdown">--:--</strong>
</div>
<script>
(function() {
    const resetAt = <?= $demoResetAt ?>;
    const el = document.getElementById('demoCountdown');
    const banner = document.getElementById('demoBanner');
    function tick() {
        const now = Math.floor(Date.now() / 1000);
        let diff = resetAt - now;
        if (diff <= 0) {
            el.textContent = '0:00';
            // Trigger reset via API then redirect
            fetch('/api/demo-reset', { method: 'POST' })
                .finally(() => window.location.href = '/login');
            return;
        }
        const m = Math.floor(diff / 60);
        const s = diff % 60;
        el.textContent = m + ':' + String(s).padStart(2, '0');
        if (diff <= 60) banner.classList.add('demo-timer-urgent');
        requestAnimationFrame(() => setTimeout(tick, 1000));
    }
    tick();
})();
</script>
<?php endif; ?>

<?php if (is_impersonating()): ?>
<div class="alert alert-warning text-center mb-0 py-2 rounded-0 d-flex justify-content-center align-items-center gap-2" id="impersonationBanner">
    <i class="bi bi-person-badge"></i>
    <strong><?= __('viewing_as') ?>:</strong>
    <?= sanitize($_SESSION['name']) ?>
    <a href="/stop-impersonation" class="btn btn-sm btn-dark ms-2">
        <i class="bi bi-box-arrow-left me-1"></i><?= __('back_to_professor') ?>
    </a>
</div>
<?php endif; ?>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm mb-4">
    <div class="container">
        <a class="navbar-brand fw-bold" href="/">
            <i class="bi bi-mortarboard-fill me-2"></i><?= __('app_name') ?>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto">
                <?php if ($isLoggedIn && $role === 'student'): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'dashboard' ? 'active' : '' ?>" href="/student/dashboard">
                            <i class="bi bi-house-door me-1"></i><?= __('dashboard') ?>
                        </a>
                    </li>
                <?php elseif ($isLoggedIn && $role === 'doctor'): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'dashboard' ? 'active' : '' ?>" href="/professor/dashboard">
                            <i class="bi bi-house-door me-1"></i><?= __('dashboard') ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'settings' ? 'active' : '' ?>" href="/professor/settings">
                            <i class="bi bi-gear me-1"></i><?= __('settings') ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'students' ? 'active' : '' ?>" href="/professor/students">
                            <i class="bi bi-people me-1"></i><?= __('student_accounts') ?>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item">
                    <button class="nav-link btn btn-link" id="themeToggle" onclick="toggleTheme()" title="Toggle dark mode">
                        <i class="bi bi-moon-fill" id="themeIcon"></i>
                    </button>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-translate me-1"></i><?= $currentLangLabel ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <?php foreach ($langLabels as $code => $label): ?>
                            <li>
                                <a class="dropdown-item <?= getLang() === $code ? 'active' : '' ?>" href="?lang=<?= $code ?>">
                                    <?= $label ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </li>
                <?php if ($isLoggedIn): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center gap-1" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <?php if ($navProfilePic): ?>
                                <img src="<?= $navProfilePic ?>" class="navbar-profile-pic rounded-circle" alt="">
                            <?php else: ?>
                                <i class="bi bi-person-circle me-1"></i>
                            <?php endif; ?>
                            <?= sanitize($userName) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <?php if ($role === 'student'): ?>
                                <li>
                                    <a class="dropdown-item" href="/student/profile">
                                        <i class="bi bi-person me-2"></i><?= __('my_profile') ?>
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                            <?php endif; ?>
                            <li>
                                <a class="dropdown-item" href="/logout">
                                    <i class="bi bi-box-arrow-right me-2"></i><?= __('logout') ?>
                                </a>
                            </li>
                        </ul>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
