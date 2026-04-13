<?php
/**
 * Admin Dashboard — overview with professor, student, and project stats
 */
require_once dirname(__DIR__) . '/includes/bootstrap.php';

require_admin();

$pdo = getDB();

// Stats
$professorCount = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'doctor'")->fetchColumn();
$studentCount = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
$projectCount = (int)$pdo->query("SELECT COUNT(*) FROM projects")->fetchColumn();
$acceptedCount = (int)$pdo->query("SELECT COUNT(*) FROM projects WHERE status = 'accepted'")->fetchColumn();
$underReviewCount = (int)$pdo->query("SELECT COUNT(*) FROM projects WHERE status = 'under_review'")->fetchColumn();
$rejectedCount = (int)$pdo->query("SELECT COUNT(*) FROM projects WHERE status = 'rejected'")->fetchColumn();
$draftCount = (int)$pdo->query("SELECT COUNT(*) FROM projects WHERE status = 'draft'")->fetchColumn();

// Recent professors
$recentProfessors = $pdo->query("SELECT id, name, email, account_enabled, created_at FROM users WHERE role = 'doctor' ORDER BY created_at DESC LIMIT 5")->fetchAll();

// Recent projects
$recentProjects = $pdo->query("
    SELECT p.*, u.name AS leader_name,
           (SELECT COUNT(*) FROM project_members WHERE project_id = p.id) AS member_count
    FROM projects p
    LEFT JOIN project_members pm ON pm.project_id = p.id AND pm.role = 'leader'
    LEFT JOIN users u ON u.id = pm.user_id
    ORDER BY p.created_at DESC LIMIT 5
")->fetchAll();

$statusLabels = getStatusLabels();
$statusColors = getStatusColors();

$pageTitle = __('admin_dashboard');
require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/includes/navbar.php';

$isAr = getLang() === 'ar';
?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3><i class="bi bi-shield-lock me-2"></i><?= __('admin_dashboard') ?></h3>
    </div>

    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="card shadow-sm border-0 h-100 card-hover">
                <div class="card-body text-center">
                    <div class="fs-1 text-primary mb-2"><i class="bi bi-person-workspace"></i></div>
                    <h2 class="fw-bold mb-1"><?= $professorCount ?></h2>
                    <small class="text-muted"><?= __('professors') ?></small>
                </div>
                <a href="/admin/professors" class="stretched-link"></a>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card shadow-sm border-0 h-100 card-hover">
                <div class="card-body text-center">
                    <div class="fs-1 text-info mb-2"><i class="bi bi-people"></i></div>
                    <h2 class="fw-bold mb-1"><?= $studentCount ?></h2>
                    <small class="text-muted"><?= __('students') ?></small>
                </div>
                <a href="/admin/students" class="stretched-link"></a>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card shadow-sm border-0 h-100 card-hover">
                <div class="card-body text-center">
                    <div class="fs-1 text-success mb-2"><i class="bi bi-folder-check"></i></div>
                    <h2 class="fw-bold mb-1"><?= $projectCount ?></h2>
                    <small class="text-muted"><?= __('total_projects') ?></small>
                </div>
                <a href="/admin/projects" class="stretched-link"></a>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card shadow-sm border-0 h-100 card-hover">
                <div class="card-body text-center">
                    <div class="fs-1 text-warning mb-2"><i class="bi bi-check-circle"></i></div>
                    <h2 class="fw-bold mb-1"><?= $acceptedCount ?></h2>
                    <small class="text-muted"><?= __('accepted_projects') ?></small>
                </div>
                <a href="/admin/projects?tab=accepted" class="stretched-link"></a>
            </div>
        </div>
    </div>

    <!-- Project Status Breakdown -->
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="card border-secondary card-hover">
                <div class="card-body py-2 d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-pencil-square text-secondary me-1"></i> <?= __('draft_projects') ?></span>
                    <span class="badge bg-secondary"><?= $draftCount ?></span>
                </div>
                <a href="/admin/projects?tab=draft" class="stretched-link"></a>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-warning card-hover">
                <div class="card-body py-2 d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-hourglass-split text-warning me-1"></i> <?= __('projects_under_review') ?></span>
                    <span class="badge bg-warning text-dark"><?= $underReviewCount ?></span>
                </div>
                <a href="/admin/projects?tab=under_review" class="stretched-link"></a>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-success card-hover">
                <div class="card-body py-2 d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-check-circle-fill text-success me-1"></i> <?= __('accepted_projects') ?></span>
                    <span class="badge bg-success"><?= $acceptedCount ?></span>
                </div>
                <a href="/admin/projects?tab=accepted" class="stretched-link"></a>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-danger card-hover">
                <div class="card-body py-2 d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-x-circle-fill text-danger me-1"></i> <?= __('rejected_projects') ?></span>
                    <span class="badge bg-danger"><?= $rejectedCount ?></span>
                </div>
                <a href="/admin/projects?tab=rejected" class="stretched-link"></a>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Recent Professors -->
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="bi bi-person-workspace me-2"></i><?= __('recent_professors') ?></h6>
                    <a href="/admin/professors" class="btn btn-sm btn-light"><?= __('view_all') ?></a>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($recentProfessors)): ?>
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-person-x fs-3"></i>
                            <p class="mt-2 mb-0"><?= __('no_professors') ?></p>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($recentProfessors as $prof): ?>
                                <a href="/admin/professors" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><?= sanitize($prof['name']) ?></strong>
                                        <br><small class="text-muted"><?= sanitize($prof['email']) ?></small>
                                    </div>
                                    <div>
                                        <?php if ($prof['account_enabled']): ?>
                                            <span class="badge bg-success"><i class="bi bi-check-lg"></i> <?= __('active') ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-danger"><i class="bi bi-x-lg"></i> <?= __('disabled') ?></span>
                                        <?php endif; ?>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent Projects -->
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="bi bi-folder me-2"></i><?= __('recent_projects') ?></h6>
                    <a href="/admin/projects" class="btn btn-sm btn-light"><?= __('view_all') ?></a>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($recentProjects)): ?>
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-inbox fs-3"></i>
                            <p class="mt-2 mb-0"><?= __('no_projects') ?></p>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($recentProjects as $proj): ?>
                                <a href="/admin/projects?tab=<?= $proj['status'] ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><?= sanitize($proj['title']) ?></strong>
                                        <br><small class="text-muted"><?= sanitize($proj['leader_name'] ?? '—') ?> · <?= $proj['member_count'] ?> <?= __('member') ?></small>
                                    </div>
                                    <span class="badge bg-<?= $statusColors[$proj['status']] ?>"><?= $statusLabels[$proj['status']] ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
