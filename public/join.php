<?php
/**
 * Join Project Page — handles token-based and code-based join links
 * Routes: /join.php?token=X or /join.php?code=X
 */
require_once __DIR__ . '/includes/bootstrap.php';

// Must be logged in as student
if (!is_logged_in()) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    redirect('/login');
}
require_role('student');

$userId = current_user_id();
$isAr = getLang() === 'ar';
$token = sanitize($_GET['token'] ?? '');
$code = sanitize($_GET['code'] ?? '');
$error = '';
$success = '';
$project = null;
$alreadyMember = false;

// Check if student is already in a project or locked by acceptance
if (countUserProjects($userId) >= 1) {
    if (isStudentProjectLocked($userId)) {
        $error = __('project_accepted_locked_msg');
    } else {
        $error = __('project_limit_reached_msg');
    }
}

if (!$error && $token) {
    // Token-based invite
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT i.*, p.title, p.type, p.status AS project_status, p.join_code,
                                  u.name AS invited_by_name
                           FROM invitations i
                           JOIN projects p ON p.id = i.project_id
                           JOIN users u ON u.id = i.invited_by
                           WHERE i.token = ? AND i.status = 'pending'");
    $stmt->execute([$token]);
    $invitation = $stmt->fetch();

    if (!$invitation) {
        $error = __('invalid_invitation_link');
    } elseif ($invitation['expires_at'] && strtotime($invitation['expires_at']) < time()) {
        $error = __('invitation_expired');
    } elseif ($invitation['project_status'] !== 'draft') {
        $error = __('project_not_accepting');
    } else {
        $project = [
            'id' => $invitation['project_id'],
            'title' => $invitation['title'],
            'type' => $invitation['type'],
            'invited_by_name' => $invitation['invited_by_name'],
            'token' => $token,
        ];
        $alreadyMember = isProjectMember($invitation['project_id'], $userId);
        $settings = getSettings();
        $memberCount = countProjectMembers($invitation['project_id']);
        $isFull = $memberCount >= (int)$settings['max_team_size'];
        // Check year/department compatibility
        if (!$alreadyMember && !$isFull) {
            $compatError = checkStudentProjectCompatibility($userId, (int)$invitation['project_id']);
            if ($compatError) {
                $error = $compatError;
                $project = null;
            }
        }
    }
} elseif (!$error && $code) {
    // Code-based join
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT p.*, u.name AS leader_name
                           FROM projects p
                           LEFT JOIN project_members pm ON pm.project_id = p.id AND pm.role = 'leader'
                           LEFT JOIN users u ON u.id = pm.user_id
                           WHERE p.join_code = ?");
    $stmt->execute([$code]);
    $result = $stmt->fetch();

    if (!$result) {
        $error = __('invalid_join_code');
    } elseif ($result['status'] !== 'draft') {
        $error = __('project_not_accepting');
    } else {
        $project = [
            'id' => $result['id'],
            'title' => $result['title'],
            'type' => $result['type'],
            'invited_by_name' => $result['leader_name'],
            'code' => $code,
        ];
        $alreadyMember = isProjectMember($result['id'], $userId);
        $settings = getSettings();
        $memberCount = countProjectMembers($result['id']);
        $isFull = $memberCount >= (int)$settings['max_team_size'];
        // Check year/department compatibility
        if (!$alreadyMember && !$isFull) {
            $compatError = checkStudentProjectCompatibility($userId, (int)$result['id']);
            if ($compatError) {
                $error = $compatError;
                $project = null;
            }
        }
    }
} elseif (!$error) {
    redirect('/student/dashboard');
}

$pageTitle = __('join_project_title');
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-primary text-white text-center">
                    <h5 class="mb-0"><i class="bi bi-person-plus me-2"></i><?= __('project_invitation') ?></h5>
                </div>
                <div class="card-body p-4 text-center">
                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle me-2"></i><?= sanitize($error) ?>
                        </div>
                        <a href="/student/dashboard" class="btn btn-primary">
                            <i class="bi bi-house-door me-1"></i><?= __('dashboard') ?>
                        </a>

                    <?php elseif ($alreadyMember): ?>
                        <div class="alert alert-info">
                            <i class="bi bi-check-circle me-2"></i>
                            <?= __('already_in_project') ?>
                        </div>
                        <a href="/student/project?id=<?= $project['id'] ?>" class="btn btn-primary">
                            <i class="bi bi-eye me-1"></i><?= __('view_project') ?>
                        </a>

                    <?php elseif ($isFull): ?>
                        <div class="alert alert-warning">
                            <i class="bi bi-people-fill me-2"></i><?= __('project_full') ?>
                        </div>
                        <a href="/student/dashboard" class="btn btn-primary">
                            <i class="bi bi-house-door me-1"></i><?= __('dashboard') ?>
                        </a>

                    <?php elseif ($project): ?>
                        <div class="mb-4">
                            <i class="bi bi-clipboard-data text-primary" style="font-size: 3rem;"></i>
                        </div>
                        <h4 class="mb-3"><?= sanitize($project['title']) ?></h4>
                        <?php if ($project['type']): ?>
                            <p class="text-muted"><?= __('type') ?>: <?= sanitize($project['type']) ?></p>
                        <?php endif; ?>
                        <p class="text-muted">
                            <?= __('invited_by_label') ?>: <strong><?= sanitize($project['invited_by_name']) ?></strong>
                        </p>

                        <div id="joinAlert" class="alert d-none"></div>

                        <div class="d-flex gap-3 justify-content-center mt-4">
                            <button class="btn btn-success btn-lg" onclick="joinProject()">
                                <i class="bi bi-check-lg me-2"></i><?= __('join') ?>
                            </button>
                            <a href="/student/dashboard" class="btn btn-outline-secondary btn-lg">
                                <i class="bi bi-x-lg me-2"></i><?= __('decline') ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($project && !$alreadyMember && !$isFull): ?>
<script>
async function joinProject() {
    const alertEl = document.getElementById('joinAlert');
    try {
        const body = {};
        <?php if (!empty($project['token'])): ?>
            body.token = <?= json_encode($project['token']) ?>;
        <?php elseif (!empty($project['code'])): ?>
            body.join_code = <?= json_encode($project['code']) ?>;
        <?php endif; ?>
        body.action = 'accept';

        const res = await fetch('/api/invitations', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body)
        });
        const data = await res.json();
        if (data.success) {
            alertEl.className = 'alert alert-success';
            alertEl.innerHTML = '<i class="bi bi-check-circle me-2"></i>' + (data.message || 'Joined!');
            alertEl.classList.remove('d-none');
            setTimeout(() => {
                window.location.href = '/student/project?id=<?= $project['id'] ?>';
            }, 1000);
        } else {
            throw new Error(data.error);
        }
    } catch (err) {
        alertEl.className = 'alert alert-danger';
        alertEl.textContent = err.message;
        alertEl.classList.remove('d-none');
    }
}
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
