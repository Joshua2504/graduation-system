<?php
/**
 * Admin — Department Management Page
 */
require_once dirname(__DIR__) . '/includes/bootstrap.php';

require_admin();

$pdo = getDB();
$isAr = getLang() === 'ar';

// Fetch all departments with user count
$stmt = $pdo->query("
    SELECT d.id, d.name, d.created_at,
           COUNT(u.id) AS user_count
    FROM departments d
    LEFT JOIN users u ON u.section = d.name
    GROUP BY d.id
    ORDER BY d.name ASC
");
$departments = $stmt->fetchAll();

$pageTitle = __('departments');
require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/includes/navbar.php';
?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3><i class="bi bi-diagram-3 me-2"></i><?= __('departments') ?></h3>
        <div class="d-flex gap-2 align-items-center">
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addDeptModal">
                <i class="bi bi-plus-lg me-1"></i><?= __('add_department') ?>
            </button>
            <span class="badge bg-primary fs-6"><?= count($departments) ?></span>
        </div>
    </div>

    <div class="alert alert-info">
        <i class="bi bi-info-circle me-2"></i><?= __('departments_description') ?>
    </div>

    <?php if (empty($departments)): ?>
        <div class="alert alert-warning text-center">
            <i class="bi bi-exclamation-triangle me-2"></i><?= __('no_departments') ?>
        </div>
    <?php else: ?>
        <div class="card shadow">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="deptsTable">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th><?= __('department_name') ?></th>
                            <th><?= __('users') ?></th>
                            <th><?= __('registered') ?></th>
                            <th><?= __('actions') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($departments as $i => $d): ?>
                        <tr id="dept-row-<?= $d['id'] ?>">
                            <td><?= $i + 1 ?></td>
                            <td class="fw-bold"><?= sanitize($d['name']) ?></td>
                            <td><span class="badge bg-secondary"><?= (int)$d['user_count'] ?></span></td>
                            <td><small class="text-muted"><?= date('Y-m-d', strtotime($d['created_at'])) ?></small></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-primary"
                                            onclick="openEdit(<?= $d['id'] ?>, <?= htmlspecialchars(json_encode($d['name']), ENT_QUOTES, 'UTF-8') ?>)"
                                            title="<?= __('edit_department') ?>">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-outline-danger"
                                            onclick="deleteDept(<?= $d['id'] ?>, <?= htmlspecialchars(json_encode($d['name']), ENT_QUOTES, 'UTF-8') ?>)"
                                            title="<?= __('delete_department') ?>">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Add Department Modal -->
<div class="modal fade" id="addDeptModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-plus-lg me-2"></i><?= __('add_department') ?></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="addDeptError" class="alert alert-danger d-none"></div>
                <div class="mb-3">
                    <label class="form-label"><?= __('department_name') ?> <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="addDeptName" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= __('cancel') ?></button>
                <button type="button" class="btn btn-primary" onclick="addDept()">
                    <i class="bi bi-plus-lg me-1"></i><?= __('add_department') ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Department Modal -->
<div class="modal fade" id="editDeptModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title"><i class="bi bi-pencil me-2"></i><?= __('edit_department') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="editDeptError" class="alert alert-danger d-none"></div>
                <input type="hidden" id="editDeptId">
                <div class="mb-3">
                    <label class="form-label"><?= __('department_name') ?> <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="editDeptName" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= __('cancel') ?></button>
                <button type="button" class="btn btn-warning" onclick="saveDeptEdit()">
                    <i class="bi bi-check-lg me-1"></i><?= __('save') ?>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
async function addDept() {
    const name = document.getElementById('addDeptName').value.trim();
    const errEl = document.getElementById('addDeptError');
    errEl.classList.add('d-none');
    if (!name) return;
    try {
        const res = await fetch('/api/departments', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ name })
        });
        const data = await res.json();
        if (data.success) {
            location.reload();
        } else {
            errEl.textContent = data.error;
            errEl.classList.remove('d-none');
        }
    } catch (err) {
        errEl.textContent = err.message;
        errEl.classList.remove('d-none');
    }
}

function openEdit(id, name) {
    document.getElementById('editDeptId').value = id;
    document.getElementById('editDeptName').value = name;
    document.getElementById('editDeptError').classList.add('d-none');
    new bootstrap.Modal(document.getElementById('editDeptModal')).show();
}

async function saveDeptEdit() {
    const id = parseInt(document.getElementById('editDeptId').value);
    const name = document.getElementById('editDeptName').value.trim();
    const errEl = document.getElementById('editDeptError');
    errEl.classList.add('d-none');
    if (!name) return;
    try {
        const res = await fetch('/api/departments', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, name })
        });
        const data = await res.json();
        if (data.success) {
            location.reload();
        } else {
            errEl.textContent = data.error;
            errEl.classList.remove('d-none');
        }
    } catch (err) {
        errEl.textContent = err.message;
        errEl.classList.remove('d-none');
    }
}

async function deleteDept(id, name) {
    const msg = <?= json_encode(__('confirm_delete_department')) ?>;
    if (!confirm(msg + '\n\n' + name)) return;
    try {
        const res = await fetch('/api/departments', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        });
        const data = await res.json();
        if (data.success) {
            location.reload();
        } else {
            alert(data.error);
        }
    } catch (err) {
        alert(err.message);
    }
}

// Allow pressing Enter in add modal
document.getElementById('addDeptName').addEventListener('keydown', e => {
    if (e.key === 'Enter') addDept();
});
document.getElementById('editDeptName').addEventListener('keydown', e => {
    if (e.key === 'Enter') saveDeptEdit();
});
</script>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
