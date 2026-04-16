<?php
/**
 * Professor — Shared Files Management Page
 */
require_once dirname(__DIR__) . '/includes/bootstrap.php';

require_role('doctor');

$pdo = getDB();
$isAr = getLang() === 'ar';
$departments = getDepartments();

// Fetch all shared files
$stmt = $pdo->query("
    SELECT sf.*, u.name AS uploader_name
    FROM shared_files sf
    LEFT JOIN users u ON u.id = sf.uploaded_by
    ORDER BY sf.created_at DESC
");
$files = $stmt->fetchAll();

$yearLabels = [
    '1st' => __('first_year'),
    '2nd' => __('second_year'),
    '3rd' => __('third_year'),
    '4th' => __('fourth_year'),
];

$pageTitle = __('files');
require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/includes/navbar.php';
?>

<div class="container">
    <div class="d-flex align-items-center gap-2 mb-4 flex-wrap">
        <h3 class="mb-0"><i class="bi bi-folder2-open me-2"></i><?= __('files') ?></h3>
        <div class="ms-auto d-flex gap-2 align-items-center flex-wrap justify-content-end">
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addFileModal">
                <i class="bi bi-file-earmark-plus me-1"></i><?= __('add_file') ?>
            </button>
            <span class="badge bg-primary fs-6"><?= count($files) ?> <?= __('files_count') ?></span>
            <input type="text" class="form-control form-control-sm" id="searchInput"
                   placeholder="<?= __('search_placeholder') ?>" style="min-width:140px; max-width:200px;">
        </div>
    </div>

    <?php if (empty($files)): ?>
        <div class="alert alert-info text-center">
            <i class="bi bi-info-circle me-2"></i><?= __('no_files') ?>
        </div>
    <?php else: ?>
        <div class="card shadow">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="filesTable">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th><?= __('file_name') ?></th>
                            <th><?= __('year') ?></th>
                            <th><?= __('department') ?></th>
                            <th><?= __('file_note') ?></th>
                            <th><?= __('uploaded_by') ?></th>
                            <th><?= __('actions') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($files as $i => $f): ?>
                        <tr id="file-row-<?= $f['id'] ?>">
                            <td><?= $i + 1 ?></td>
                            <td class="fw-bold"><?= sanitize($f['name']) ?></td>
                            <td><?= sanitize($yearLabels[$f['year']] ?? $f['year']) ?></td>
                            <td><?= sanitize($f['department']) ?></td>
                            <td><?= sanitize($f['note'] ?? '') ?></td>
                            <td><?= sanitize($f['uploader_name'] ?? '') ?></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="/api/shared-files?download=<?= $f['id'] ?>" class="btn btn-outline-success" title="<?= __('download_file') ?>">
                                        <i class="bi bi-download"></i>
                                    </a>
                                    <button class="btn btn-outline-primary" onclick="openEditModal(<?= $f['id'] ?>)" title="<?= __('edit_file') ?>">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-outline-danger" onclick="deleteFile(<?= $f['id'] ?>)" title="<?= __('delete_file') ?>">
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

<!-- Add File Modal -->
<div class="modal fade" id="addFileModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-file-earmark-plus me-2"></i><?= __('add_new_file') ?></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="addAlert" class="alert d-none"></div>
                <form id="addFileForm" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label"><?= __('file_name') ?> <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="add_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= __('year') ?></label>
                        <select class="form-select" id="add_year">
                            <option value=""><?= __('select_option') ?></option>
                            <option value="1st"><?= __('first_year') ?></option>
                            <option value="2nd"><?= __('second_year') ?></option>
                            <option value="3rd"><?= __('third_year') ?></option>
                            <option value="4th"><?= __('fourth_year') ?></option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= __('department') ?></label>
                        <select class="form-select" id="add_department">
                            <option value=""><?= __('select_department') ?></option>
                            <?php foreach ($departments as $d): ?>
                                <option value="<?= sanitize($d['name']) ?>"><?= sanitize($d['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= __('file_note') ?></label>
                        <textarea class="form-control" id="add_note" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= __('file_upload') ?> <span class="text-danger">*</span></label>
                        <input type="file" class="form-control" id="add_file" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= __('close') ?></button>
                <button type="button" class="btn btn-primary" id="addFileBtn" onclick="addFile()">
                    <i class="bi bi-upload me-1"></i><?= __('add_file') ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Edit File Modal -->
<div class="modal fade" id="editFileModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i><?= __('edit_file') ?></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="editAlert" class="alert d-none"></div>
                <form id="editFileForm" enctype="multipart/form-data">
                    <input type="hidden" id="edit_file_id">
                    <div class="mb-3">
                        <label class="form-label"><?= __('file_name') ?> <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= __('year') ?></label>
                        <select class="form-select" id="edit_year">
                            <option value=""><?= __('select_option') ?></option>
                            <option value="1st"><?= __('first_year') ?></option>
                            <option value="2nd"><?= __('second_year') ?></option>
                            <option value="3rd"><?= __('third_year') ?></option>
                            <option value="4th"><?= __('fourth_year') ?></option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= __('department') ?></label>
                        <select class="form-select" id="edit_department">
                            <option value=""><?= __('select_department') ?></option>
                            <?php foreach ($departments as $d): ?>
                                <option value="<?= sanitize($d['name']) ?>"><?= sanitize($d['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= __('file_note') ?></label>
                        <textarea class="form-control" id="edit_note" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= __('file_upload') ?> <small class="text-muted">(<?= __('optional_replace') ?>)</small></label>
                        <input type="file" class="form-control" id="edit_file">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= __('close') ?></button>
                <button type="button" class="btn btn-primary" id="saveFileBtn" onclick="saveFile()">
                    <i class="bi bi-check-lg me-1"></i><?= __('save') ?>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
const isAr = <?= json_encode($isAr) ?>;
let addFileModal, editFileModal;

document.addEventListener('DOMContentLoaded', () => {
    addFileModal  = new bootstrap.Modal(document.getElementById('addFileModal'));
    editFileModal = new bootstrap.Modal(document.getElementById('editFileModal'));
});

// Search / filter
document.getElementById('searchInput')?.addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('#filesTable tbody tr').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
});

function showAlert(id, msg, type = 'danger') {
    const el = document.getElementById(id);
    if (!el) return;
    el.className = 'alert alert-' + type;
    el.textContent = msg;
    el.classList.remove('d-none');
}

// ─── Add file ─────────────────────────────────────────────────────────────────
async function addFile() {
    const btn = document.getElementById('addFileBtn');
    const name = document.getElementById('add_name').value.trim();
    if (!name) { showAlert('addAlert', <?= json_encode(__('file_name_required')) ?>); return; }
    const fileInput = document.getElementById('add_file');
    if (!fileInput.files.length) { showAlert('addAlert', <?= json_encode(__('file_required')) ?>); return; }

    const fd = new FormData();
    fd.append('name', name);
    fd.append('year', document.getElementById('add_year').value);
    fd.append('department', document.getElementById('add_department').value);
    fd.append('note', document.getElementById('add_note').value);
    fd.append('file', fileInput.files[0]);

    btn.disabled = true;
    try {
        const res = await fetch('/api/shared-files', { method: 'POST', body: fd });
        const data = await res.json();
        if (!res.ok || data.error) { showAlert('addAlert', data.error || <?= json_encode(__('error')) ?>); return; }
        addFileModal.hide();
        window.location.reload();
    } catch (e) {
        showAlert('addAlert', <?= json_encode(__('error')) ?>);
    } finally {
        btn.disabled = false;
    }
}

// ─── Open edit modal ──────────────────────────────────────────────────────────
function openEditModal(fileId) {
    const row = document.getElementById('file-row-' + fileId);
    if (!row) return;
    const cells = row.querySelectorAll('td');
    document.getElementById('edit_file_id').value = fileId;
    document.getElementById('edit_name').value = cells[1].textContent.trim();
    // year — find matching option by display text
    const yearSel = document.getElementById('edit_year');
    const yearText = cells[2].textContent.trim();
    [...yearSel.options].forEach(o => { if (o.text.trim() === yearText) yearSel.value = o.value; });
    // department
    const deptSel = document.getElementById('edit_department');
    const deptText = cells[3].textContent.trim();
    [...deptSel.options].forEach(o => { if (o.text.trim() === deptText) deptSel.value = o.value; });
    document.getElementById('edit_note').value = cells[4].textContent.trim();
    document.getElementById('edit_file').value = '';
    document.getElementById('editAlert').classList.add('d-none');
    editFileModal.show();
}

// ─── Save (update) file ───────────────────────────────────────────────────────
async function saveFile() {
    const btn = document.getElementById('saveFileBtn');
    const fileId = document.getElementById('edit_file_id').value;
    const name = document.getElementById('edit_name').value.trim();
    if (!name) { showAlert('editAlert', <?= json_encode(__('file_name_required')) ?>); return; }

    const fd = new FormData();
    fd.append('name', name);
    fd.append('year', document.getElementById('edit_year').value);
    fd.append('department', document.getElementById('edit_department').value);
    fd.append('note', document.getElementById('edit_note').value);
    const fi = document.getElementById('edit_file');
    if (fi.files.length) fd.append('file', fi.files[0]);

    btn.disabled = true;
    try {
        const res = await fetch('/api/shared-files?_method=PUT&id=' + fileId, { method: 'POST', body: fd });
        const data = await res.json();
        if (!res.ok || data.error) { showAlert('editAlert', data.error || <?= json_encode(__('error')) ?>); return; }
        editFileModal.hide();
        window.location.reload();
    } catch (e) {
        showAlert('editAlert', <?= json_encode(__('error')) ?>);
    } finally {
        btn.disabled = false;
    }
}

// ─── Delete file ──────────────────────────────────────────────────────────────
async function deleteFile(fileId) {
    if (!confirm(<?= json_encode(__('confirm_delete_file')) ?>)) return;
    try {
        const res = await fetch('/api/shared-files?_method=DELETE&id=' + fileId, { method: 'POST' });
        const data = await res.json();
        if (!res.ok || data.error) { alert(data.error || <?= json_encode(__('error')) ?>); return; }
        const row = document.getElementById('file-row-' + fileId);
        if (row) row.remove();
        // Update badge count
        const badge = document.querySelector('.badge.bg-primary.fs-6');
        if (badge) {
            const c = parseInt(badge.textContent);
            badge.textContent = (c - 1) + ' ' + <?= json_encode(__('files_count')) ?>;
        }
    } catch (e) {
        alert(<?= json_encode(__('error')) ?>);
    }
}
</script>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
