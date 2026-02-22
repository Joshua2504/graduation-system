<?php
/**
 * Shared navbar component
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/lang.php';

$isLoggedIn = is_logged_in();
$role = current_role();
$userName = $_SESSION['name'] ?? '';
$otherLang = getLang() === 'ar' ? 'en' : 'ar';
$otherLangLabel = getLang() === 'ar' ? 'English' : 'العربية';

// Determine current page for active state
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>

<?php if (is_impersonating()): ?>
<div class="alert alert-warning text-center mb-0 py-2 rounded-0 d-flex justify-content-center align-items-center gap-2" id="impersonationBanner">
    <i class="bi bi-person-badge"></i>
    <strong><?= getLang() === 'ar' ? 'أنت تتصفح كـ' : 'Viewing as' ?>:</strong>
    <?= sanitize($_SESSION['name']) ?>
    <a href="/stop-impersonation.php" class="btn btn-sm btn-dark ms-2">
        <i class="bi bi-box-arrow-left me-1"></i><?= getLang() === 'ar' ? 'العودة للدكتور' : 'Back to Professor' ?>
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
                        <a class="nav-link <?= $currentPage === 'dashboard' ? 'active' : '' ?>" href="/student/dashboard.php">
                            <i class="bi bi-house-door me-1"></i><?= __('dashboard') ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'profile' ? 'active' : '' ?>" href="/student/profile.php">
                            <i class="bi bi-person me-1"></i><?= __('my_profile') ?>
                        </a>
                    </li>
                <?php elseif ($isLoggedIn && $role === 'doctor'): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'dashboard' ? 'active' : '' ?>" href="/professor/dashboard.php">
                            <i class="bi bi-house-door me-1"></i><?= __('dashboard') ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'settings' ? 'active' : '' ?>" href="/professor/settings.php">
                            <i class="bi bi-gear me-1"></i><?= __('settings') ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'students' ? 'active' : '' ?>" href="/professor/students.php">
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
                <li class="nav-item">
                    <a class="nav-link" href="?lang=<?= $otherLang ?>">
                        <i class="bi bi-translate me-1"></i><?= $otherLangLabel ?>
                    </a>
                </li>
                <?php if ($isLoggedIn): ?>
                    <li class="nav-item">
                        <span class="nav-link text-light">
                            <i class="bi bi-person-circle me-1"></i><?= sanitize($userName) ?>
                        </span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/logout.php">
                            <i class="bi bi-box-arrow-right me-1"></i><?= __('logout') ?>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
