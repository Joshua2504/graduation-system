<?php
/**
 * Professor Dashboard — stats + project lists
 */
require_once dirname(__DIR__) . '/includes/bootstrap.php';

require_role('doctor');

$pdo = getDB();
$sort = ($_GET['sort'] ?? 'recent') === 'oldest' ? 'ASC' : 'DESC';
$sortParam = $sort === 'ASC' ? 'oldest' : 'recent';
$activeTab = $_GET['tab'] ?? 'under_review';
$validTabs = ['draft', 'under_review', 'accepted', 'rejected'];
if (!in_array($activeTab, $validTabs)) $activeTab = 'under_review';

// Stats
$stats = [];
foreach (['draft', 'under_review', 'accepted', 'rejected'] as $s) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM projects WHERE status = ?");
    $stmt->execute([$s]);
    $stats[$s] = (int)$stmt->fetchColumn();
}

// Load all projects grouped by status
$allProjects = [];
$totalProjects = 0;
foreach (['draft', 'under_review', 'accepted', 'rejected'] as $s) {
    $stmt = $pdo->prepare("SELECT p.*, u.name AS leader_name, u.email AS leader_email,
                                  (SELECT COUNT(*) FROM project_members WHERE project_id = p.id) AS member_count
                           FROM projects p
                           LEFT JOIN project_members pm ON pm.project_id = p.id AND pm.role = 'leader'
                           LEFT JOIN users u ON u.id = pm.user_id
                           WHERE p.status = ?
                           ORDER BY p.created_at $sort");
    $stmt->execute([$s]);
    $allProjects[$s] = $stmt->fetchAll();
    $stats[$s] = count($allProjects[$s]);
    $totalProjects += $stats[$s];
}

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
    <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
        <h3 class="mb-0"><i class="bi bi-house-door me-2"></i><?= __('dashboard') ?></h3>
        <div class="ms-auto d-flex gap-2 align-items-center flex-wrap justify-content-end">
            <input type="search" id="projectSearch" class="form-control form-control-sm" style="min-width:160px; max-width:260px;"
                   placeholder="<?= __('search_projects') ?>" oninput="filterProjects()">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createProjectModal">
                <i class="bi bi-plus-circle me-1"></i><?= __('create_project_professor') ?>
            </button>
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
                                <th><?= __('team_leader') ?></th>
                                <th><?= __('member_count') ?></th>
                                <th><?= __('submission_date') ?></th>
                                <th><?= __('group_number') ?></th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="projectTableBody">
                            <?php foreach ($tabs as $key => $tab):
                                $groupProjects = $allProjects[$key];
                            ?>
                                <tr class="status-group-header table-light d-none" data-status="<?= $key ?>">
                                    <td colspan="7" class="py-2">
                                        <i class="bi bi-<?= $tab['icon'] ?> text-<?= $tab['color'] ?> me-1"></i>
                                        <strong><?= $tab['label'] ?></strong>
                                        <span class="badge bg-<?= $tab['color'] ?> ms-1"><?= count($groupProjects) ?></span>
                                    </td>
                                </tr>
                                <?php foreach ($groupProjects as $i => $p):
                                    $isDupe = in_array(strtolower($p['title']), $duplicateTitles);
                                    $dupeLink = '';
                                    if ($isDupe) {
                                        $dupeStmt2 = $pdo->prepare("SELECT id FROM projects WHERE LOWER(title) = LOWER(?) AND id != ? LIMIT 1");
                                        $dupeStmt2->execute([$p['title'], $p['id']]);
                                        $dupeProject = $dupeStmt2->fetch();
                                        if ($dupeProject) $dupeLink = '/professor/project?id=' . $dupeProject['id'];
                                    }
                                ?>
                                    <tr data-status="<?= $key ?>" style="cursor:pointer" onclick="location.href='/professor/project?id=<?= $p['id'] ?>'">
                                        <td><?= $i + 1 ?></td>
                                        <td>
                                            <strong><?= sanitize($p['title']) ?></strong>
                                            <?php if ($isDupe): ?>
                                                <br>
                                                <span class="badge bg-warning text-dark">
                                                    <i class="bi bi-exclamation-triangle me-1"></i><?= __('duplicate_warning') ?>
                                                </span>
                                                <?php if ($dupeLink): ?>
                                                    <a href="<?= $dupeLink ?>" class="small" onclick="event.stopPropagation()"><?= __('view_similar') ?></a>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= $p['leader_name'] ? sanitize($p['leader_name']) : '<span class="text-muted fst-italic">' . __('no_leader_assigned') . '</span>' ?></td>
                                        <td><span class="badge bg-info"><?= $p['member_count'] ?></span></td>
                                        <td><?= $p['submission_date'] ? date('Y-m-d H:i', strtotime($p['submission_date'])) : '-' ?></td>
                                        <td><?= $p['group_number'] ? '<span class="badge bg-success fs-6">' . $p['group_number'] . '</span>' : '<span class="text-muted">—</span>' ?></td>
                                        <td>
                                            <a href="/professor/project?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary" onclick="event.stopPropagation()">
                                                <i class="bi bi-eye me-1"></i><?= __('view_project') ?>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                            <tr id="noSearchResults" class="d-none">
                                <td colspan="7" class="text-center text-muted py-4">
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

<!-- Create Project Modal -->
<div class="modal fade" id="createProjectModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i><?= __('create_project_professor') ?></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="profCreateForm">
                    <div class="mb-3">
                        <label class="form-label fw-bold"><?= __('project_name') ?> <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="profNewTitle" required maxlength="500">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold"><?= __('project_type') ?></label>
                        <input type="text" class="form-control" id="profNewType" maxlength="255"
                               placeholder="<?= __('project_type_placeholder') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold"><?= __('project_description') ?></label>
                        <div class="simple-editor-toolbar">
                            <button type="button" class="btn" onclick="editorCmd('bold')" title="<?= __('bold') ?>"><i class="bi bi-type-bold"></i></button>
                            <button type="button" class="btn" onclick="editorCmd('italic')" title="<?= __('italic') ?>"><i class="bi bi-type-italic"></i></button>
                            <button type="button" class="btn" onclick="editorCmd('underline')" title="<?= __('underline_text') ?>"><i class="bi bi-type-underline"></i></button>
                            <div class="vr mx-1 opacity-25"></div>
                            <button type="button" class="btn" onclick="editorCmd('insertUnorderedList')" title="<?= __('bullet_list') ?>"><i class="bi bi-list-ul"></i></button>
                            <button type="button" class="btn" onclick="editorCmd('insertOrderedList')" title="<?= __('numbered_list') ?>"><i class="bi bi-list-ol"></i></button>
                            <div class="vr mx-1 opacity-25"></div>
                            <button type="button" class="btn" onclick="editorInsertLink()" title="<?= __('insert_link') ?>"><i class="bi bi-link-45deg"></i></button>
                        </div>
                        <div id="profNewDescription" class="simple-editor-content" contenteditable="true" data-placeholder="<?= __('description_placeholder') ?>" style="min-height:100px;"></div>
                    </div>

                    <hr>
                    <h6 class="fw-bold"><i class="bi bi-people me-1"></i><?= __('assign_students') ?></h6>
                    <div class="mb-3">
                        <div class="input-group">
                            <input type="text" class="form-control" id="profStudentSearch" 
                                   placeholder="<?= __('search_students') ?>">
                            <button type="button" class="btn btn-outline-primary" onclick="searchStudents()">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                        <div id="profSearchResults" class="list-group mt-2"></div>
                    </div>
                    <div id="profSelectedStudents" class="mb-3"></div>
                    <div id="profCreateAlert" class="alert d-none"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= __('cancel') ?></button>
                <button type="button" class="btn btn-primary" onclick="profCreateProject()">
                    <i class="bi bi-plus-circle me-1"></i><?= __('create_project') ?>
                </button>
            </div>
        </div>
    </div>
</div>

<script src="/assets/js/editor.js"></script>
<script>
let selectedStudents = [];
let selectedLeaderId = null;

function searchStudents() {
    const query = document.getElementById('profStudentSearch').value.trim();
    if (!query) return;
    
    // We use the project PATCH endpoint with a temporary project_id=0 for searching
    // But actually, let's search for all students not yet in this (new) project
    fetch('/api/users?search=' + encodeURIComponent(query))
        .then(r => r.json())
        .then(data => {
            const container = document.getElementById('profSearchResults');
            container.innerHTML = '';
            const students = data.students || [];
            
            if (students.length === 0) {
                container.innerHTML = '<div class="list-group-item text-muted"><?= __('no_results') ?></div>';
                return;
            }
            
            students.forEach(s => {
                if (selectedStudents.find(x => x.id == s.id)) return; // skip already selected
                const item = document.createElement('button');
                item.type = 'button';
                item.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-center';
                item.innerHTML = `
                    <span><strong>${escHtml(s.name)}</strong> <small class="text-muted">${escHtml(s.email)}</small> <code>${escHtml(s.student_code || '')}</code></span>
                    <span class="badge bg-primary"><i class="bi bi-plus"></i> <?= __('add') ?></span>`;
                item.onclick = () => addStudent(s);
                container.appendChild(item);
            });
        })
        .catch(err => console.error(err));
}

// Allow searching on Enter key
document.getElementById('profStudentSearch')?.addEventListener('keydown', e => {
    if (e.key === 'Enter') { e.preventDefault(); searchStudents(); }
});

function addStudent(s) {
    if (selectedStudents.find(x => x.id == s.id)) return;
    selectedStudents.push(s);
    if (selectedStudents.length === 1) selectedLeaderId = s.id; // first student = default leader
    renderSelectedStudents();
    document.getElementById('profSearchResults').innerHTML = '';
    document.getElementById('profStudentSearch').value = '';
}

function removeStudent(id) {
    selectedStudents = selectedStudents.filter(s => s.id != id);
    if (selectedLeaderId == id) {
        selectedLeaderId = selectedStudents.length > 0 ? selectedStudents[0].id : null;
    }
    renderSelectedStudents();
}

function setLeader(id) {
    selectedLeaderId = id;
    renderSelectedStudents();
}

function renderSelectedStudents() {
    const container = document.getElementById('profSelectedStudents');
    if (selectedStudents.length === 0) {
        container.innerHTML = '';
        return;
    }
    
    let html = '<label class="form-label small fw-bold"><?= __('current_members') ?>:</label><div class="list-group">';
    selectedStudents.forEach(s => {
        const isLeader = s.id == selectedLeaderId;
        html += `<div class="list-group-item d-flex justify-content-between align-items-center">
            <span>
                <strong>${escHtml(s.name)}</strong>
                <small class="text-muted ms-2">${escHtml(s.email)}</small>
                ${isLeader ? '<span class="badge bg-primary ms-2"><?= __('leader') ?></span>' : ''}
            </span>
            <span>
                ${!isLeader ? `<button type="button" class="btn btn-sm btn-outline-primary me-1" onclick="setLeader(${s.id})" title="<?= __('set_as_leader') ?>"><i class="bi bi-star"></i></button>` : ''}
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeStudent(${s.id})" title="<?= __('remove_member') ?>"><i class="bi bi-x-lg"></i></button>
            </span>
        </div>`;
    });
    html += '</div>';
    container.innerHTML = html;
}

async function profCreateProject() {
    const title = document.getElementById('profNewTitle').value.trim();
    const type = document.getElementById('profNewType').value.trim();
    const descEl = document.getElementById('profNewDescription');
    const description = descEl ? descEl.innerHTML.trim() : '';
    const alertEl = document.getElementById('profCreateAlert');
    alertEl.classList.add('d-none');
    
    if (!title) return;
    
    try {
        const body = {
            title, type, description,
            students: selectedStudents.map(s => s.id),
            leader_id: selectedLeaderId
        };
        const res = await fetch('/api/project', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body)
        });
        const data = await res.json();
        if (data.success) {
            window.location.href = '/professor/project?id=' + data.project_id;
        } else {
            throw new Error(data.error);
        }
    } catch (err) {
        alertEl.className = 'alert alert-danger';
        alertEl.textContent = err.message;
        alertEl.classList.remove('d-none');
    }
}

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
        const show = row.dataset.status === currentTab;
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

function escHtml(str) {
    const d = document.createElement('div');
    d.textContent = str || '';
    return d.innerHTML;
}
</script>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
