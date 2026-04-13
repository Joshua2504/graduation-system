<?php
/**
 * Login page
 */
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/mailer.php';

// If already logged in, redirect
if (is_logged_in()) {
    redirect('/');
}

// If no users exist and not in demo mode, redirect to initial setup
if (!isDemoMode()) {
    $pdo = getDB();
    $userCount = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if ($userCount === 0) {
        redirect('/register');
    }
}

$error = '';
$showResend = false;
$resendEmail = '';
$settings = getSettings();
$loginMethods = $settings['login_methods'] ?? 'both';

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
    $login_input = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($login_input) || empty($password)) {
        $error = __('required_field');
    } else {
        $pdo = getDB();
        // Determine login method based on setting
        $isEmail = strpos($login_input, '@') !== false;

        // Enforce login method restriction (only for students; admins/professors always use email)
        if ($loginMethods === 'email_only' && !$isEmail) {
            $error = __('login_method_not_allowed');
        } elseif ($loginMethods === 'student_code_only' && $isEmail) {
            // Allow email login for non-student roles (admin/professor)
            $pdo2 = getDB();
            $stmtCheck = $pdo2->prepare("SELECT role FROM users WHERE email = ?");
            $stmtCheck->execute([$login_input]);
            $roleCheck = $stmtCheck->fetchColumn();
            if ($roleCheck && $roleCheck !== 'student') {
                // Allow — admin/professor can always use email
            } else {
                $error = __('login_method_not_allowed');
            }
        }

        if (empty($error)) {
        // Allow login with email or student code
        if ($isEmail) {
            $stmt = $pdo->prepare("SELECT id, name, email, password, role, email_verified, account_enabled FROM users WHERE email = ?");
        } else {
            $stmt = $pdo->prepare("SELECT id, name, email, password, role, email_verified, account_enabled FROM users WHERE student_code = ?");
        }
        $stmt->execute([$login_input]);
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
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['name'] = $user['name'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['role'] = $user['role'];
                    scheduleDemoReset();
                    $redir = $_SESSION['redirect_after_login'] ?? '/';
                    unset($_SESSION['redirect_after_login']);
                    if (!str_starts_with($redir, '/') || str_starts_with($redir, '//')) $redir = '/';
                    redirect($redir);
                }
            } else {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                scheduleDemoReset();
                $redir = $_SESSION['redirect_after_login'] ?? '/';
                unset($_SESSION['redirect_after_login']);
                if (!str_starts_with($redir, '/') || str_starts_with($redir, '//')) $redir = '/';
                redirect($redir);
            }
        } else {
            $error = __('invalid_credentials');
        }
        } // end if (empty($error))
    }
}

$pageTitle = __('login_title');
require_once __DIR__ . '/includes/header.php';
?>

<div class="landing-page">
    <div class="container-fluid px-0">
        <div class="row g-0 min-vh-100">

            <!-- Left: Introduction / Hero Section -->
            <div class="col-lg-7 d-none d-lg-flex landing-hero">
                <div class="landing-hero-content">
                    <div class="landing-brand mb-4">
                        <i class="bi bi-mortarboard-fill"></i>
                    </div>
                    <h1 class="landing-title mb-3"><?= __('landing_hero_title') ?></h1>
                    <p class="landing-subtitle mb-5"><?= __('landing_hero_subtitle') ?></p>

                    <!-- Feature Grid -->
                    <div class="row g-3 landing-features mb-5">
                        <div class="col-sm-6">
                            <div class="landing-feature-card">
                                <div class="landing-feature-icon"><i class="bi bi-people-fill"></i></div>
                                <h5><?= __('landing_feature_team') ?></h5>
                                <p><?= __('landing_feature_team_desc') ?></p>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="landing-feature-card">
                                <div class="landing-feature-icon"><i class="bi bi-send-check-fill"></i></div>
                                <h5><?= __('landing_feature_submit') ?></h5>
                                <p><?= __('landing_feature_submit_desc') ?></p>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="landing-feature-card">
                                <div class="landing-feature-icon"><i class="bi bi-clipboard2-check-fill"></i></div>
                                <h5><?= __('landing_feature_review') ?></h5>
                                <p><?= __('landing_feature_review_desc') ?></p>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="landing-feature-card">
                                <div class="landing-feature-icon"><i class="bi bi-translate"></i></div>
                                <h5><?= __('landing_feature_bilingual') ?></h5>
                                <p><?= __('landing_feature_bilingual_desc') ?></p>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="landing-feature-card">
                                <div class="landing-feature-icon"><i class="bi bi-shield-lock-fill"></i></div>
                                <h5><?= __('landing_feature_secure') ?></h5>
                                <p><?= __('landing_feature_secure_desc') ?></p>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="landing-feature-card">
                                <div class="landing-feature-icon"><i class="bi bi-phone-fill"></i></div>
                                <h5><?= __('landing_feature_responsive') ?></h5>
                                <p><?= __('landing_feature_responsive_desc') ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- How It Works Steps -->
                    <div class="landing-steps">
                        <h4 class="mb-4 fw-bold"><i class="bi bi-signpost-split-fill me-2"></i><?= __('landing_how_it_works') ?></h4>
                        <div class="landing-steps-row">
                            <div class="landing-step">
                                <div class="landing-step-num">1</div>
                                <h6><?= __('landing_step1_title') ?></h6>
                                <small><?= __('landing_step1_desc') ?></small>
                            </div>
                            <div class="landing-step-arrow"><i class="bi bi-arrow-<?= getLang() === 'ar' ? 'left' : 'right' ?>"></i></div>
                            <div class="landing-step">
                                <div class="landing-step-num">2</div>
                                <h6><?= __('landing_step2_title') ?></h6>
                                <small><?= __('landing_step2_desc') ?></small>
                            </div>
                            <div class="landing-step-arrow"><i class="bi bi-arrow-<?= getLang() === 'ar' ? 'left' : 'right' ?>"></i></div>
                            <div class="landing-step">
                                <div class="landing-step-num">3</div>
                                <h6><?= __('landing_step3_title') ?></h6>
                                <small><?= __('landing_step3_desc') ?></small>
                            </div>
                            <div class="landing-step-arrow"><i class="bi bi-arrow-<?= getLang() === 'ar' ? 'left' : 'right' ?>"></i></div>
                            <div class="landing-step">
                                <div class="landing-step-num">4</div>
                                <h6><?= __('landing_step4_title') ?></h6>
                                <small><?= __('landing_step4_desc') ?></small>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Stats -->
                    <div class="landing-stats mt-5">
                        <div class="landing-stat">
                            <i class="bi bi-hand-thumbs-up-fill"></i>
                            <span><?= __('landing_stat_easy') ?></span>
                        </div>
                        <div class="landing-stat">
                            <i class="bi bi-lightning-charge-fill"></i>
                            <span><?= __('landing_stat_fast') ?></span>
                        </div>
                        <div class="landing-stat">
                            <i class="bi bi-boxes"></i>
                            <span><?= __('landing_stat_complete') ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right: Login Form -->
            <div class="col-lg-5 d-flex align-items-center justify-content-center landing-form-side">
                <div class="landing-form-wrapper">
                    <!-- Top controls: theme toggle + language switcher -->
                    <div class="d-flex justify-content-end gap-2 mb-3">
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="toggleTheme()" title="Toggle dark mode">
                            <i class="bi bi-moon-fill" id="themeIcon"></i>
                        </button>
                        <?php
                        $langLabels = ['ar' => 'العربية', 'en' => 'English', 'de' => 'Deutsch'];
                        global $supportedLangs;
                        $activeLangLabels = array_intersect_key($langLabels, array_flip($supportedLangs ?? ['ar']));
                        ?>
                        <?php if (count($activeLangLabels) > 1): ?>
                        <div class="btn-group">
                            <button type="button" class="btn btn-outline-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-translate me-1"></i><?= $activeLangLabels[getLang()] ?? reset($activeLangLabels) ?>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <?php foreach ($activeLangLabels as $code => $label): ?>
                                    <li><a class="dropdown-item <?= getLang() === $code ? 'active' : '' ?>" href="?lang=<?= $code ?>"><?= $label ?></a></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Mobile-only intro -->
                    <div class="d-lg-none text-center mb-4">
                        <i class="bi bi-mortarboard-fill text-primary" style="font-size: 2.5rem;"></i>
                        <h4 class="mt-2"><?= __('app_name') ?></h4>
                        <p class="text-muted small mb-3"><?= __('landing_hero_subtitle') ?></p>
                        <div class="landing-mobile-features">
                            <div class="landing-mobile-feature">
                                <i class="bi bi-people-fill text-primary"></i>
                                <span><?= __('landing_feature_team') ?></span>
                            </div>
                            <div class="landing-mobile-feature">
                                <i class="bi bi-send-check-fill text-success"></i>
                                <span><?= __('landing_feature_submit') ?></span>
                            </div>
                            <div class="landing-mobile-feature">
                                <i class="bi bi-clipboard2-check-fill text-info"></i>
                                <span><?= __('landing_feature_review') ?></span>
                            </div>
                            <div class="landing-mobile-feature">
                                <i class="bi bi-translate text-warning"></i>
                                <span><?= __('landing_feature_bilingual') ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="card shadow landing-card">
                        <div class="card-body p-4">
                            <div class="text-center mb-4">
                                <h3 class="mb-1"><?= __('landing_welcome_back') ?></h3>
                                <p class="text-muted mb-0"><?= __('landing_sign_in_continue') ?></p>
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
                                    <?php
                                    // Dynamic label and placeholder based on login_methods setting
                                    if ($loginMethods === 'email_only') {
                                        $loginLabel = __('email');
                                        $loginPlaceholder = __('email_placeholder');
                                        $loginIcon = 'bi-envelope';
                                        $inputType = 'email';
                                    } elseif ($loginMethods === 'student_code_only') {
                                        $loginLabel = __('student_code');
                                        $loginPlaceholder = __('student_code_placeholder');
                                        $loginIcon = 'bi-person-badge';
                                        $inputType = 'text';
                                    } else {
                                        $loginLabel = __('email_or_code');
                                        $loginPlaceholder = __('email_or_code_placeholder');
                                        $loginIcon = 'bi-person';
                                        $inputType = 'text';
                                    }
                                    ?>
                                    <label for="email" class="form-label"><?= $loginLabel ?></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi <?= $loginIcon ?>"></i></span>
                                        <input type="<?= $inputType ?>" class="form-control" id="email" name="email"
                                               value="<?= sanitize($login_input ?? '') ?>" required autofocus
                                               placeholder="<?= $loginPlaceholder ?>">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="password" class="form-label"><?= __('password') ?></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                        <input type="password" class="form-control" id="password" name="password" required>
                                    </div>
                                    <div class="text-end mt-1">
                                        <a href="/forgot-password" class="small text-muted"><?= __('forgot_password') ?></a>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary w-100 py-2">
                                    <i class="bi bi-box-arrow-in-right me-1"></i><?= __('login') ?>
                                </button>
                            </form>

                            <div class="text-center mt-3">
                                <span class="text-muted"><?= __('no_account') ?></span>
                                <a href="/register"><?= __('register') ?></a>
                            </div>

                            <?php if (isDemoMode()): ?>
                            <?php $demoCreds = getDemoCredentials(); ?>
                            <hr>
                            <div class="demo-credentials-box small">
                                <div class="fw-semibold mb-1"><i class="bi bi-key me-1"></i><?= __('demo_credentials') ?></div>
                                <table class="table table-sm table-borderless mb-0" style="font-size: 0.8rem;">
                                    <?php foreach ($demoCreds as $email => $pass): ?>
                                    <tr>
                                        <td class="text-muted py-0"><?= sanitize($email) ?></td>
                                        <td class="py-0"><code><?= sanitize($pass) ?></code></td>
                                        <td class="py-0 text-end">
                                            <button type="button" class="btn btn-outline-primary btn-sm py-0 px-2 demo-login-btn"
                                                    data-email="<?= sanitize($email) ?>" data-password="<?= sanitize($pass) ?>">
                                                <i class="bi bi-box-arrow-in-right me-1"></i><?= __('login') ?>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </table>
                            </div>
                            <script>
                            document.querySelectorAll('.demo-login-btn').forEach(btn => {
                                btn.addEventListener('click', () => {
                                    document.getElementById('email').value = btn.dataset.email;
                                    document.getElementById('password').value = btn.dataset.password;
                                    btn.closest('.card-body').querySelector('form[method="POST"]:not([class])').submit();
                                });
                            });
                            </script>
                            <?php endif; ?>
                        </div>
                    </div>

                    <footer class="text-center text-muted mt-4">
                        <small>&copy; <?= date('Y') ?> <?= __('app_name') ?></small>
                    </footer>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
function toggleTheme() {
    const html = document.documentElement;
    const current = html.getAttribute('data-bs-theme') || 'light';
    const next = current === 'dark' ? 'light' : 'dark';
    html.setAttribute('data-bs-theme', next);
    localStorage.setItem('theme', next);
    updateThemeIcon(next);
}
function updateThemeIcon(theme) {
    const icon = document.getElementById('themeIcon');
    if (icon) {
        icon.className = theme === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon-fill';
    }
}
updateThemeIcon(document.documentElement.getAttribute('data-bs-theme') || 'light');
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
