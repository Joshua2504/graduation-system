<?php
/**
 * Login page
 */
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/lang.php';

// If already logged in, redirect
if (is_logged_in()) {
    redirect('/');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = __('required_field');
    } else {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT id, name, email, password, role FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            redirect('/');
        } else {
            $error = __('invalid_credentials');
        }
    }
}

$pageTitle = __('login_title');
require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="row justify-content-center mt-5">
        <div class="col-md-5">
            <div class="card shadow">
                <div class="card-body p-4">
                    <div class="text-center mb-4">
                        <i class="bi bi-mortarboard-fill text-primary" style="font-size: 3rem;"></i>
                        <h3 class="mt-2"><?= __('login_title') ?></h3>
                        <p class="text-muted"><?= __('app_name') ?></p>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= sanitize($error) ?></div>
                    <?php endif; ?>

                    <form method="POST" novalidate>
                        <div class="mb-3">
                            <label for="email" class="form-label"><?= __('email') ?></label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?= sanitize($email ?? '') ?>" required autofocus>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label"><?= __('password') ?></label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-box-arrow-in-right me-1"></i><?= __('login') ?>
                        </button>
                    </form>

                    <div class="text-center mt-3">
                        <span class="text-muted"><?= __('no_account') ?></span>
                        <a href="/register.php"><?= __('register') ?></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
