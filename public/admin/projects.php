<?php
/**
 * Admin — All Projects Overview
 * Read-only view of all projects in the system
 */
require_once dirname(__DIR__) . '/includes/bootstrap.php';

require_admin();

$pdo = getDB();
$sort = ($_GET['sort'] ?? 'recent') === 'oldest' ? 'ASC' : 'DESC';
$sortParam = $sort === 'ASC' ? 'oldest' : 'recent';
$activeTab = $_GET['tab'] ?? 'all';
$validTabs = ['all', 'draft', 'under_review', 'accepted', 'rejected'];
if (!in_array($activeTab, $validTabs)) $activeTab = 'all';

// Load all projects grouped by status
$stats = ['all' => 0];
$allProjects = [];
$totalProjects = 0;
foreach (['draft', 'under_review', 'accepted', 'rejected'] as $s) {
    $stmt = $pdo->prepare("SELECT p.*, u.name AS leader_name, u.email AS leader_email,
                                  reviewer.name AS reviewer_name,
                                  (SELECT COUNT(*) FROM project_members WHERE project_id = p.id) AS member_count
                           FROM projects p
                           LEFT JOIN project_members pm ON pm.project_id = p.id AND pm.role = 'leader'
                           LEFT JOIN users u ON u.id = pm.user_id
                           LEFT JOIN users reviewer ON reviewer.id = p.reviewed_by
                           WHERE p.status = ?
                           ORDER BY p.created_at $sort");
    $stmt->execute([$s]);
    $allProjects[$s] = $stmt->fetchAll();
    $stats[$s] = count($allProjects[$s]);
    $stats['all'] += $stats[$s];
    $totalProjects += $stats[$s];
}

$statusLabels = getStatusLabels();
$statusColors = getStatusColors();

$pageTitle = __('all_projects');
require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/includes/navbar.php';

$isAr = getLang() === 'ar';
?>

<div class="container">
    <div class="d-flex align-items-center gap-2 mb-3">
        <h3 class="mb-0 me-auto"><i class="bi bi-folder me-2"></i><?= __('all_projects') ?></h3>
        <input type="search" id="projectSearch" class="form-control form-control-sm w-auto" style="min-width:220px"
               placeholder="<?= __('search_projects') ?>" oninput="filterProjects()">
    </div>

    <!-- Tab Navigation + Sort -->
    <div class="card shadow">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <ul class="nav nav-tabs card-header-tabs">
                    <li class="nav-item">
                        <a class="nav-link <?= $activeTab === 'all' ? 'active' : '' ?>"
                           href="?tab=all&sort=<?= sanitize($sortParam) ?>"
                           data-tab="all"
                           onclick="showTab('all', event)">
                            <i class="bi bi-collection text-primary me-1"></i>
                            <?= __('all') ?>
                            <span class="badge bg-primary ms-1"><?= $stats['all'] ?></span>
                        </a>
                    </li>
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
                               href="?tab=<?= $key ?>&sort=<?= sanitize($sortParam) ?>"
                               data-tab="<?= $key ?>"
                               onclick="showTab('<?= $key ?>', event)">
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
            <?php if ($totalProjects === 0): ?>
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
                                <th><?= __('project_name') ?></th>
                                <th><?= __('project_type') ?></th>
                                <th><?= __('team_leader') ?></th>
                                <th><?= __('member_count') ?></th>
                                <th><?= __('status_draft') ?></th>
                                <th><?= __('reviewed_by') ?></th>
                                <th><?= __('group_number') ?></th>
                            </tr>
                        </thead>
                        <tbody id="projectTableBody">
                            <?php
                            $adminTabs = [
                                'draft'        => ['label' => __('draft_projects'),        'icon' => 'pencil-square',    'color' => 'secondary'],
                                'under_review' => ['label' => __('projects_under_review'), 'icon' => 'hourglass-split',  'color' => 'warning'],
                                'accepted'     => ['label' => __('accepted_projects'),     'icon' => 'check-circle-fill','color' => 'success'],
                                'rejected'     => ['label' => __('rejected_projects'),     'icon' => 'x-circle-fill',    'color' => 'danger'],
                            ];
                            foreach ($adminTabs as $key => $tab):
                                $groupProjects = $allProjects[$key];
                            ?>
                                <tr class="status-group-header table-light d-none" data-status="<?= $key ?>">
                                    <td colspan="8" class="py-2">
                                        <i class="bi bi-<?= $tab['icon'] ?> text-<?= $tab['color'] ?> me-1"></i>
                                        <strong><?= $tab['label'] ?></strong>
                                        <span class="badge bg-<?= $tab['color'] ?> ms-1"><?= count($groupProjects) ?></span>
                                    </td>
                                </tr>
                                <?php foreach ($groupProjects as $i => $p): ?>
                                <tr data-status="<?= $key ?>" style="cursor:pointer" onclick="location.href='/professor/project?id=<?= $p['id'] ?>'">
                                    <td><?= $i + 1 ?></td>
                                    <td class="fw-bold"><?= sanitize($p['title']) ?></td>
                                    <td><small class="text-muted"><?= sanitize($p['type'] ?: '—') ?></small></td>
                                    <td><?= sanitize($p['leader_name'] ?? '—') ?></td>
                                    <td><span class="badge bg-light text-dark"><?= $p['member_count'] ?></span></td>
                                    <td><span class="badge bg-<?= $statusColors[$p['status']] ?>"><?= $statusLabels[$p['status']] ?></span></td>
                                    <td><small class="text-muted"><?= sanitize($p['reviewer_name'] ?? '—') ?></small></td>
                                    <td><?= $p['group_number'] ? '<span class="badge bg-success">#' . $p['group_number'] . '</span>' : '<span class="text-muted">—</span>' ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                            <tr id="noSearchResults" class="d-none">
                                <td colspan="8" class="text-center text-muted py-4">
                                    <i class="bi bi-search me-1"></i><?= __('no_results') ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
let currentTab = '<?= $activeTab ?>';

function showTab(tab, event) {
    event?.preventDefault();
    currentTab = tab;
    history.replaceState(null, '', '?tab=' + tab + '&sort=<?= $sortParam ?>');
    document.querySelectorAll('.card-header-tabs .nav-link').forEach(a => {
        a.classList.toggle('active', a.dataset.tab === tab);
    });
    document.getElementById('projectSearch').value = '';
    applyTabFilter();
}

function applyTabFilter() {
    document.querySelectorAll('.status-group-header').forEach(r => r.classList.add('d-none'));
    const rows = document.querySelectorAll('#projectTableBody tr[data-status]:not(.status-group-header)');
    let visible = 0;
    rows.forEach(row => {
        const show = currentTab === 'all' || row.dataset.status === currentTab;
        row.classList.toggle('d-none', !show);
        if (show) visible++;
    });
    document.getElementById('noSearchResults').classList.toggle('d-none', visible > 0);
}

function filterProjects() {
    const q = document.getElementById('projectSearch').value.toLowerCase().trim();
    if (!q) { applyTabFilter(); return; }

    document.querySelectorAll('#projectTableBody tr[data-status]:not(.status-group-header)').forEach(r => r.classList.add('d-none'));
    const statuses = ['draft', 'under_review', 'accepted', 'rejected'];
    let totalVisible = 0;
    statuses.forEach(status => {
        const header = document.querySelector(`.status-group-header[data-status="${status}"]`);
        const rows = document.querySelectorAll(`#projectTableBody tr[data-status="${status}"]:not(.status-group-header)`);
        let groupVisible = 0;
        rows.forEach(row => {
            const match = row.textContent.toLowerCase().includes(q);
            row.classList.toggle('d-none', !match);
            if (match) groupVisible++;
        });
        if (header) header.classList.toggle('d-none', groupVisible === 0);
        totalVisible += groupVisible;
    });
    document.getElementById('noSearchResults').classList.toggle('d-none', totalVisible > 0);
}

document.addEventListener('DOMContentLoaded', applyTabFilter);
</script>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
