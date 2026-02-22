<?php
/**
 * Professor Dashboard — stats + project lists
 */
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/lang.php';

require_role('doctor');

$pdo = getDB();
$sort = ($_GET['sort'] ?? 'recent') === 'oldest' ? 'ASC' : 'DESC';
$activeTab = $_GET['tab'] ?? 'under_review';

// Stats
$stats = [];
foreach (['draft', 'under_review', 'accepted', 'rejected'] as $s) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM projects WHERE status = ?");
    $stmt->execute([$s]);
    $stats[$s] = (int)$stmt->fetchColumn();
}

// Projects for active tab
$stmt = $pdo->prepare("SELECT p.*, u.name AS leader_name, u.email AS leader_email,
                              (SELECT COUNT(*) FROM project_members WHERE project_id = p.id) AS member_count
                       FROM projects p 
                       JOIN project_members pm ON pm.project_id = p.id AND pm.role = 'leader'
                       JOIN users u ON u.id = pm.user_id 
                       WHERE p.status = ? 
                       ORDER BY p.created_at $sort");
$stmt->execute([$activeTab]);
$projects = $stmt->fetchAll();

// Duplicate detection: find titles that appear more than once
$dupeStmt = $pdo->query("SELECT LOWER(title) AS ltitle, COUNT(*) AS cnt FROM projects GROUP BY LOWER(title) HAVING cnt > 1");
$duplicateTitles = [];
while ($row = $dupeStmt->fetch()) {
    $duplicateTitles[] = $row['ltitle'];
}

$pageTitle = __('dashboard');
require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/includes/navbar.php';

$isAr = getLang() === 'ar';
?>

<div class="container">
    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card shadow-sm border-secondary">
                <div class="card-body text-center">
                    <i class="bi bi-pencil-square text-secondary fs-1"></i>
                    <h2 class="mt-2 mb-0"><?= $stats['draft'] ?></h2>
                    <p class="text-muted"><?= __('draft_projects') ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card shadow-sm border-warning">
                <div class="card-body text-center">
                    <i class="bi bi-hourglass-split text-warning fs-1"></i>
                    <h2 class="mt-2 mb-0"><?= $stats['under_review'] ?></h2>
                    <p class="text-muted"><?= __('projects_under_review') ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card shadow-sm border-success">
                <div class="card-body text-center">
                    <i class="bi bi-check-circle-fill text-success fs-1"></i>
                    <h2 class="mt-2 mb-0"><?= $stats['accepted'] ?></h2>
                    <p class="text-muted"><?= __('accepted_projects') ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card shadow-sm border-danger">
                <div class="card-body text-center">
                    <i class="bi bi-x-circle-fill text-danger fs-1"></i>
                    <h2 class="mt-2 mb-0"><?= $stats['rejected'] ?></h2>
                    <p class="text-muted"><?= __('rejected_projects') ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab Navigation + Sort -->
    <div class="card shadow">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <ul class="nav nav-tabs card-header-tabs">
                    <?php 
                    $tabs = [
                        'draft' => ['label' => __('draft_projects'), 'icon' => 'pencil-square', 'color' => 'secondary'],
                        'under_review' => ['label' => __('projects_under_review'), 'icon' => 'hourglass-split', 'color' => 'warning'],
                        'accepted' => ['label' => __('accepted_projects'), 'icon' => 'check-circle-fill', 'color' => 'success'],
                        'rejected' => ['label' => __('rejected_projects'), 'icon' => 'x-circle-fill', 'color' => 'danger'],
                    ];
                    foreach ($tabs as $key => $tab): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= $activeTab === $key ? 'active' : '' ?>" 
                               href="?tab=<?= $key ?>&sort=<?= $_GET['sort'] ?? 'recent' ?>">
                                <i class="bi bi-<?= $tab['icon'] ?> text-<?= $tab['color'] ?> me-1"></i>
                                <?= $tab['label'] ?>
                                <span class="badge bg-<?= $tab['color'] ?> ms-1"><?= $stats[$key] ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <div class="mt-2 mt-md-0">
                    <div class="btn-group btn-group-sm">
                        <a href="?tab=<?= $activeTab ?>&sort=recent" 
                           class="btn btn-outline-primary <?= $sort === 'DESC' ? 'active' : '' ?>">
                            <i class="bi bi-sort-down me-1"></i><?= __('sort_recent') ?>
                        </a>
                        <a href="?tab=<?= $activeTab ?>&sort=oldest" 
                           class="btn btn-outline-primary <?= $sort === 'ASC' ? 'active' : '' ?>">
                            <i class="bi bi-sort-up me-1"></i><?= __('sort_oldest') ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <?php if (empty($projects)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-inbox fs-1"></i>
                    <p class="mt-2"><?= __('no_projects') ?></p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th><?= $isAr ? 'اسم المشروع' : 'Project Name' ?></th>
                                <th><?= __('team_leader') ?></th>
                                <th><?= __('member_count') ?></th>
                                <th><?= __('submission_date') ?></th>
                                <?php if ($activeTab === 'accepted'): ?>
                                    <th><?= __('group_number') ?></th>
                                <?php endif; ?>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($projects as $i => $p): 
                                $isDupe = in_array(strtolower($p['title']), $duplicateTitles);
                                // Find the other duplicate project
                                $dupeLink = '';
                                if ($isDupe) {
                                    $dupeStmt2 = $pdo->prepare("SELECT id FROM projects WHERE LOWER(title) = LOWER(?) AND id != ? LIMIT 1");
                                    $dupeStmt2->execute([$p['title'], $p['id']]);
                                    $dupeProject = $dupeStmt2->fetch();
                                    if ($dupeProject) {
                                        $dupeLink = '/professor/project.php?id=' . $dupeProject['id'];
                                    }
                                }
                            ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td>
                                        <strong><?= sanitize($p['title']) ?></strong>
                                        <?php if ($isDupe): ?>
                                            <br>
                                            <span class="badge bg-warning text-dark">
                                                <i class="bi bi-exclamation-triangle me-1"></i><?= __('duplicate_warning') ?>
                                            </span>
                                            <?php if ($dupeLink): ?>
                                                <a href="<?= $dupeLink ?>" class="small"><?= __('view_similar') ?></a>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= sanitize($p['leader_name']) ?></td>
                                    <td><span class="badge bg-info"><?= $p['member_count'] ?></span></td>
                                    <td><?= $p['submission_date'] ? date('Y-m-d H:i', strtotime($p['submission_date'])) : '-' ?></td>
                                    <?php if ($activeTab === 'accepted'): ?>
                                        <td><span class="badge bg-success fs-6"><?= $p['group_number'] ?></span></td>
                                    <?php endif; ?>
                                    <td>
                                        <a href="/professor/project.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye me-1"></i><?= __('view_project') ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
