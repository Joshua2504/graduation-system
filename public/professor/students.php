<?php
/**
 * Professor — Student Accounts Management Page
 */
require_once dirname(__DIR__) . '/includes/bootstrap.php';

require_role('doctor');

$pdo = getDB();
$isAr = getLang() === 'ar';
$settings = getSettings();
$emailVerRequired = !empty($settings['email_verification_required']);

// Fetch all student accounts
$stmt = $pdo->query("SELECT id, name, email, student_code, gender, national_id, birth_date, governorate, address, phone, section, card_image, national_id_image, receipt_image, profile_picture, profile_completed, email_verified, account_enabled, created_at FROM users WHERE role = 'student' ORDER BY created_at DESC");
$students = $stmt->fetchAll();
$governorates = getGovernorates();

$pageTitle = __('student_accounts');
require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/includes/navbar.php';
?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3><i class="bi bi-people me-2"></i><?= __('student_accounts') ?></h3>
        <div class="d-flex gap-2 align-items-center">
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createStudentModal">
                <i class="bi bi-person-plus me-1"></i><?= __('add_student') ?>
            </button>
            <span class="badge bg-primary fs-6"><?= count($students) ?> <?= __('students_count') ?></span>
            <input type="text" class="form-control form-control-sm" id="searchInput" 
                   placeholder="<?= __('search_placeholder') ?>" style="width: 200px;">
        </div>
    </div>

    <?php if (empty($students)): ?>
        <div class="alert alert-info text-center">
            <i class="bi bi-info-circle me-2"></i><?= __('no_registered_students') ?>
        </div>
    <?php else: ?>
        <div class="card shadow">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="studentsTable">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th><?= __('name') ?></th>
                            <th><?= __('email') ?></th>
                            <th><?= __('student_code') ?></th>
                            <th><?= __('profile') ?></th>
                            <th><?= __('email_col') ?></th>
                            <th><?= __('account') ?></th>
                            <th><?= __('registered') ?></th>
                            <th><?= __('actions') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $i => $s): ?>
                        <tr id="user-row-<?= $s['id'] ?>" class="<?= !$s['account_enabled'] ? 'table-secondary' : '' ?>">
                            <td><?= $i + 1 ?></td>
                            <td class="fw-bold">
                                <div class="d-flex align-items-center gap-2">
                                    <?php
                                        $sPic = $s['profile_picture'] ?? '';
                                        $sPicUrl = $sPic ? secureFileUrl($s['id'], $sPic) : '';
                                    ?>
                                    <?php if ($sPicUrl): ?>
                                        <img src="<?= $sPicUrl ?>" class="profile-picture-sm rounded-circle" alt="">
                                    <?php else: ?>
                                        <i class="bi bi-person-circle fs-5 text-secondary"></i>
                                    <?php endif; ?>
                                    <?= sanitize($s['name']) ?>
                                </div>
                            </td>
                            <td><small><?= sanitize($s['email']) ?></small></td>
                            <td><code><?= sanitize($s['student_code'] ?? '—') ?></code></td>
                            <td>
                                <?php if ($s['profile_completed']): ?>
                                    <span class="badge bg-success"><i class="bi bi-check-circle"></i></span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark"><i class="bi bi-exclamation-circle"></i></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($s['email_verified']): ?>
                                    <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i><?= __('verified') ?></span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark"><i class="bi bi-clock me-1"></i><?= __('unverified') ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($s['account_enabled']): ?>
                                    <span class="badge bg-success"><i class="bi bi-check-lg me-1"></i><?= __('active') ?></span>
                                <?php else: ?>
                                    <span class="badge bg-danger"><i class="bi bi-x-lg me-1"></i><?= __('disabled') ?></span>
                                <?php endif; ?>
                            </td>
                            <td><small class="text-muted"><?= date('Y-m-d', strtotime($s['created_at'])) ?></small></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-primary" onclick="openEditModal(<?= $s['id'] ?>)" title="<?= __('edit_profile') ?>">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <?php if (!$s['email_verified']): ?>
                                        <button class="btn btn-outline-success" onclick="userAction(<?= $s['id'] ?>, 'verify')" title="<?= __('verify_email') ?>">
                                            <i class="bi bi-envelope-check"></i>
                                        </button>
                                        <button class="btn btn-outline-info" onclick="userAction(<?= $s['id'] ?>, 'resend_verification')" title="<?= __('resend_verification_email') ?>">
                                            <i class="bi bi-envelope-arrow-up"></i>
                                        </button>
                                    <?php else: ?>
                                        <button class="btn btn-outline-warning" onclick="userAction(<?= $s['id'] ?>, 'unverify')" title="<?= __('unverify') ?>">
                                            <i class="bi bi-envelope-x"></i>
                                        </button>
                                    <?php endif; ?>

                                    <?php if ($s['account_enabled']): ?>
                                        <button class="btn btn-outline-danger" onclick="userAction(<?= $s['id'] ?>, 'disable')" title="<?= __('disable_account') ?>">
                                            <i class="bi bi-person-x"></i>
                                        </button>
                                    <?php else: ?>
                                        <button class="btn btn-outline-success" onclick="userAction(<?= $s['id'] ?>, 'enable')" title="<?= __('enable_account') ?>">
                                            <i class="bi bi-person-check"></i>
                                        </button>
                                    <?php endif; ?>

                                    <button class="btn btn-outline-danger" onclick="userAction(<?= $s['id'] ?>, 'delete')" title="<?= __('delete') ?>">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                    <button class="btn btn-outline-secondary" onclick="userAction(<?= $s['id'] ?>, 'impersonate')" title="<?= __('login_as_student') ?>">
                                        <i class="bi bi-incognito"></i>
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

<!-- Edit Student Modal -->
<div class="modal fade" id="editStudentModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i><?= __('edit_student_profile') ?></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="editLoading" class="text-center py-4">
                    <div class="spinner-border text-primary"></div>
                </div>
                <div id="editAlert" class="alert d-none"></div>
                <form id="editStudentForm" class="d-none">
                    <input type="hidden" id="edit_user_id">
                    
                    <!-- Basic Info -->
                    <h6 class="text-primary mb-3"><i class="bi bi-person me-1"></i><?= __('basic_info') ?></h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label"><?= __('name') ?></label>
                            <input type="text" class="form-control" id="edit_name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><?= __('email') ?></label>
                            <input type="email" class="form-control" id="edit_email" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label"><?= __('student_code') ?></label>
                            <input type="text" class="form-control" id="edit_student_code">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label"><?= __('gender') ?></label>
                            <select class="form-select" id="edit_gender">
                                <option value=""><?= __('select_option') ?></option>
                                <option value="male"><?= __('male') ?></option>
                                <option value="female"><?= __('female') ?></option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label"><?= __('birth_date') ?></label>
                            <input type="date" class="form-control" id="edit_birth_date">
                        </div>
                    </div>

                    <!-- Personal Info -->
                    <h6 class="text-primary mb-3"><i class="bi bi-card-text me-1"></i><?= __('personal_info') ?></h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label"><?= __('national_id') ?></label>
                            <input type="text" class="form-control" id="edit_national_id" pattern="\d{14}" maxlength="14">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><?= __('phone') ?></label>
                            <input type="text" class="form-control" id="edit_phone" pattern="\d{11}" maxlength="11">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><?= __('governorate') ?></label>
                            <select class="form-select" id="edit_governorate">
                                <option value=""><?= __('select_governorate') ?></option>
                                <?php foreach ($governorates as $gov): ?>
                                    <option value="<?= $gov ?>"><?= $gov ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><?= __('section') ?></label>
                            <input type="text" class="form-control" id="edit_section">
                        </div>
                        <div class="col-12">
                            <label class="form-label"><?= __('address') ?></label>
                            <input type="text" class="form-control" id="edit_address">
                        </div>
                    </div>

                    <!-- Documents -->
                    <h6 class="text-primary mb-3"><i class="bi bi-image me-1"></i><?= __('documents') ?></h6>
                    <div class="row g-3 mb-3">
                        <?php
                        $docTypes = [
                            'card' => __('institute_id'),
                            'national_id' => __('national_id_card'),
                            'receipt' => __('payment_receipt'),
                        ];
                        foreach ($docTypes as $dtype => $dlabel): ?>
                            <div class="col-md-4">
                                <label class="form-label"><?= $dlabel ?></label>
                                <div id="edit_img_preview_<?= $dtype ?>" class="mb-2 text-center">
                                    <span class="text-muted small"><?= __('no_image') ?></span>
                                </div>
                                <input type="file" class="form-control form-control-sm" accept="image/jpeg,image/png"
                                       onchange="uploadStudentImage(<?= "this, '$dtype'" ?>)">
                                <div id="edit_img_progress_<?= $dtype ?>" class="progress mt-1 d-none" style="height: 4px;">
                                    <div class="progress-bar" style="width: 0%"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= __('close') ?></button>
                <button type="button" class="btn btn-primary" id="saveProfileBtn" onclick="saveStudentProfile()">
                    <i class="bi bi-check-lg me-1"></i><?= __('save') ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Image Preview Modal -->
<div class="modal fade" id="imgPreviewModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="imgPreviewLabel"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <img src="" id="imgPreviewImg" class="img-fluid" alt="">
            </div>
        </div>
    </div>
</div>

<!-- Create Student Modal -->
<div class="modal fade" id="createStudentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-person-plus me-2"></i><?= __('add_new_student') ?></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="createAlert" class="alert d-none"></div>
                <form id="createStudentForm">
                    <div class="mb-3">
                        <label class="form-label"><?= __('name') ?> <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="create_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= __('email') ?> <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="create_email" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= __('student_code') ?></label>
                        <input type="text" class="form-control" id="create_student_code">
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= __('password') ?> <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="create_password" required minlength="6">
                            <button type="button" class="btn btn-outline-secondary" onclick="generatePassword()" title="<?= __('generate_password') ?>">
                                <i class="bi bi-magic"></i>
                            </button>
                        </div>
                    </div>
                    <div class="form-check mb-3">
                        <input type="checkbox" class="form-check-input" id="create_send_invite" checked>
                        <label class="form-check-label" for="create_send_invite">
                            <i class="bi bi-envelope me-1"></i><?= __('send_welcome_email') ?>
                        </label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= __('close') ?></button>
                <button type="button" class="btn btn-primary" id="createStudentBtn" onclick="createStudent()">
                    <i class="bi bi-person-plus me-1"></i><?= __('create_account') ?>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
const isAr = <?= json_encode($isAr) ?>;
let editModal;

document.addEventListener('DOMContentLoaded', () => {
    editModal = new bootstrap.Modal(document.getElementById('editStudentModal'));
});

// Search / filter
document.getElementById('searchInput')?.addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('#studentsTable tbody tr').forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(q) ? '' : 'none';
    });
});

// Open edit modal and load student data
async function openEditModal(userId) {
    const form = document.getElementById('editStudentForm');
    const loading = document.getElementById('editLoading');
    const alertEl = document.getElementById('editAlert');

    form.classList.add('d-none');
    loading.classList.remove('d-none');
    alertEl.classList.add('d-none');
    editModal.show();

    try {
        const res = await fetch('/api/users?id=' + userId);
        const data = await res.json();
        if (!data.user) throw new Error(data.error || 'Error');

        const u = data.user;
        document.getElementById('edit_user_id').value = u.id;
        document.getElementById('edit_name').value = u.name || '';
        document.getElementById('edit_email').value = u.email || '';
        document.getElementById('edit_student_code').value = u.student_code || '';
        document.getElementById('edit_gender').value = u.gender || '';
        document.getElementById('edit_birth_date').value = u.birth_date || '';
        document.getElementById('edit_national_id').value = u.national_id || '';
        document.getElementById('edit_phone').value = u.phone || '';
        document.getElementById('edit_governorate').value = u.governorate || '';
        document.getElementById('edit_section').value = u.section || '';
        document.getElementById('edit_address').value = u.address || '';

        // Show image previews
        const imgTypes = { card: 'card_image', national_id: 'national_id_image', receipt: 'receipt_image' };
        for (const [type, field] of Object.entries(imgTypes)) {
            const container = document.getElementById('edit_img_preview_' + type);
            if (u[field]) {
                const path = '/api/file?user=' + u.id + '&file=' + encodeURIComponent(u[field]);
                container.innerHTML = `<img src="${path}" class="img-thumbnail" style="max-height:100px; cursor:pointer;" onclick="previewImage('${path}', '${type}')">`;
            } else {
                container.innerHTML = `<span class="text-muted small">${isAr ? <?= json_encode(__('no_image')) ?> : <?= json_encode(__('no_image')) ?>}</span>`;
            }
        }

        loading.classList.add('d-none');
        form.classList.remove('d-none');
    } catch (err) {
        loading.classList.add('d-none');
        alertEl.className = 'alert alert-danger';
        alertEl.textContent = err.message;
        alertEl.classList.remove('d-none');
    }
}

// Save profile changes
async function saveStudentProfile() {
    const btn = document.getElementById('saveProfileBtn');
    const alertEl = document.getElementById('editAlert');
    btn.disabled = true;

    const userId = document.getElementById('edit_user_id').value;
    const payload = {
        user_id: parseInt(userId),
        name: document.getElementById('edit_name').value.trim(),
        email: document.getElementById('edit_email').value.trim(),
        student_code: document.getElementById('edit_student_code').value.trim(),
        gender: document.getElementById('edit_gender').value,
        birth_date: document.getElementById('edit_birth_date').value,
        national_id: document.getElementById('edit_national_id').value.trim(),
        phone: document.getElementById('edit_phone').value.trim(),
        governorate: document.getElementById('edit_governorate').value,
        section: document.getElementById('edit_section').value.trim(),
        address: document.getElementById('edit_address').value.trim(),
    };

    try {
        const res = await fetch('/api/users', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (data.success) {
            alertEl.className = 'alert alert-success';
            alertEl.innerHTML = '<i class="bi bi-check-circle me-1"></i>' + data.message;
            alertEl.classList.remove('d-none');
            setTimeout(() => location.reload(), 800);
        } else {
            throw new Error(data.error);
        }
    } catch (err) {
        alertEl.className = 'alert alert-danger';
        alertEl.textContent = err.message;
        alertEl.classList.remove('d-none');
        btn.disabled = false;
    }
}

// Upload image for student (doctor editing)
function uploadStudentImage(input, type) {
    const file = input.files[0];
    if (!file) return;

    const userId = document.getElementById('edit_user_id').value;
    const progressEl = document.getElementById('edit_img_progress_' + type);
    const progressBar = progressEl.querySelector('.progress-bar');

    const formData = new FormData();
    formData.append('file', file);
    formData.append('user_id', userId);
    formData.append('action', 'upload_image');
    formData.append('image_type', type);

    const xhr = new XMLHttpRequest();
    xhr.open('POST', '/api/users', true);

    progressEl.classList.remove('d-none');
    progressBar.style.width = '0%';

    xhr.upload.addEventListener('progress', (e) => {
        if (e.lengthComputable) {
            progressBar.style.width = Math.round((e.loaded / e.total) * 100) + '%';
        }
    });

    xhr.addEventListener('load', () => {
        progressEl.classList.add('d-none');
        try {
            const data = JSON.parse(xhr.responseText);
            if (data.success) {
                const container = document.getElementById('edit_img_preview_' + type);
                const securePath = '/api/file?user=' + document.getElementById('edit_user_id').value + '&file=' + encodeURIComponent(data.filename);
                container.innerHTML = `<img src="${securePath}" class="img-thumbnail" style="max-height:100px; cursor:pointer;" onclick="previewImage('${securePath}', '${type}')">`;
            } else {
                alert(data.error || 'Upload failed');
            }
        } catch (e) {
            alert('Upload error');
        }
        input.value = '';
    });

    xhr.addEventListener('error', () => {
        progressEl.classList.add('d-none');
        alert('Upload failed');
    });

    xhr.send(formData);
}

// Preview image in modal
function previewImage(src, type) {
    document.getElementById('imgPreviewLabel').textContent = type;
    document.getElementById('imgPreviewImg').src = src;
    new bootstrap.Modal(document.getElementById('imgPreviewModal')).show();
}

async function userAction(userId, action) {
    const confirmMsgs = {
        verify:   <?= json_encode(__('confirm_verify_email')) ?>,
        unverify: <?= json_encode(__('confirm_unverify_email')) ?>,
        enable:   <?= json_encode(__('confirm_enable_account')) ?>,
        disable:  <?= json_encode(__('confirm_disable_account')) ?>,
        delete:   <?= json_encode(__('confirm_delete_account')) ?>,
        resend_verification: <?= json_encode(__('confirm_resend_verification')) ?>,
        impersonate: <?= json_encode(__('confirm_impersonate')) ?>,
    };

    if (action !== 'impersonate' && !confirm(confirmMsgs[action] || 'Are you sure?')) return;

    try {
        const res = await fetch('/api/users', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: userId, action: action })
        });
        const data = await res.json();
        if (data.success) {
            if (action === 'impersonate' && data.redirect) {
                window.location.href = data.redirect;
            } else {
                location.reload();
            }
        } else {
            alert(data.error || 'Error');
        }
    } catch (e) {
        alert('Network error');
    }
}

// Generate random password
function generatePassword() {
    const chars = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789';
    let pass = '';
    for (let i = 0; i < 10; i++) pass += chars.charAt(Math.floor(Math.random() * chars.length));
    document.getElementById('create_password').value = pass;
}

// Create student account
async function createStudent() {
    const btn = document.getElementById('createStudentBtn');
    const alertEl = document.getElementById('createAlert');
    btn.disabled = true;
    alertEl.classList.add('d-none');

    const payload = {
        user_id: 0,
        action: 'create_user',
        name: document.getElementById('create_name').value.trim(),
        email: document.getElementById('create_email').value.trim(),
        student_code: document.getElementById('create_student_code').value.trim(),
        password: document.getElementById('create_password').value,
        send_invite: document.getElementById('create_send_invite').checked,
    };

    if (!payload.name || !payload.email || !payload.password) {
        alertEl.className = 'alert alert-danger';
        alertEl.textContent = isAr ? <?= json_encode(__('name_email_password_required')) ?> : <?= json_encode(__('name_email_password_required')) ?>;
        alertEl.classList.remove('d-none');
        btn.disabled = false;
        return;
    }

    try {
        const res = await fetch('/api/users', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (data.success) {
            alertEl.className = 'alert alert-success';
            let msg = '<i class="bi bi-check-circle me-1"></i>' + data.message;
            if (data.email_sent) msg += ' <span class="badge bg-info">' + <?= json_encode(__('email_sent')) ?> + '</span>';
            alertEl.innerHTML = msg;
            alertEl.classList.remove('d-none');
            document.getElementById('createStudentForm').reset();
            setTimeout(() => location.reload(), 1200);
        } else {
            throw new Error(data.error);
        }
    } catch (err) {
        alertEl.className = 'alert alert-danger';
        alertEl.textContent = err.message;
        alertEl.classList.remove('d-none');
        btn.disabled = false;
    }
}
</script>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
