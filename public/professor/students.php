<?php
/**
 * Professor — Student Accounts Management Page
 */
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/lang.php';

require_role('doctor');

$pdo = getDB();
$isAr = getLang() === 'ar';
$settings = getSettings();
$emailVerRequired = !empty($settings['email_verification_required']);

// Fetch all student accounts
$stmt = $pdo->query("SELECT id, name, email, student_code, gender, national_id, birth_date, governorate, address, phone, section, card_image, national_id_image, receipt_image, profile_completed, email_verified, account_enabled, created_at FROM users WHERE role = 'student' ORDER BY created_at DESC");
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
            <span class="badge bg-primary fs-6"><?= count($students) ?> <?= $isAr ? 'طالب' : 'students' ?></span>
            <input type="text" class="form-control form-control-sm" id="searchInput" 
                   placeholder="<?= $isAr ? 'بحث...' : 'Search...' ?>" style="width: 200px;">
        </div>
    </div>

    <?php if (empty($students)): ?>
        <div class="alert alert-info text-center">
            <i class="bi bi-info-circle me-2"></i><?= $isAr ? 'لا يوجد طلاب مسجلين' : 'No registered students' ?>
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
                            <th><?= $isAr ? 'الملف' : 'Profile' ?></th>
                            <th><?= $isAr ? 'البريد' : 'Email' ?></th>
                            <th><?= $isAr ? 'الحساب' : 'Account' ?></th>
                            <th><?= $isAr ? 'تاريخ التسجيل' : 'Registered' ?></th>
                            <th><?= $isAr ? 'إجراءات' : 'Actions' ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $i => $s): ?>
                        <tr id="user-row-<?= $s['id'] ?>" class="<?= !$s['account_enabled'] ? 'table-secondary' : '' ?>">
                            <td><?= $i + 1 ?></td>
                            <td class="fw-bold"><?= sanitize($s['name']) ?></td>
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
                                    <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i><?= $isAr ? 'مؤكد' : 'Verified' ?></span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark"><i class="bi bi-clock me-1"></i><?= $isAr ? 'غير مؤكد' : 'Unverified' ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($s['account_enabled']): ?>
                                    <span class="badge bg-success"><i class="bi bi-check-lg me-1"></i><?= $isAr ? 'نشط' : 'Active' ?></span>
                                <?php else: ?>
                                    <span class="badge bg-danger"><i class="bi bi-x-lg me-1"></i><?= $isAr ? 'معطل' : 'Disabled' ?></span>
                                <?php endif; ?>
                            </td>
                            <td><small class="text-muted"><?= date('Y-m-d', strtotime($s['created_at'])) ?></small></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-primary" onclick="openEditModal(<?= $s['id'] ?>)" title="<?= $isAr ? 'تعديل الملف الشخصي' : 'Edit Profile' ?>">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <?php if (!$s['email_verified']): ?>
                                        <button class="btn btn-outline-success" onclick="userAction(<?= $s['id'] ?>, 'verify')" title="<?= $isAr ? 'تأكيد البريد' : 'Verify Email' ?>">
                                            <i class="bi bi-envelope-check"></i>
                                        </button>
                                    <?php else: ?>
                                        <button class="btn btn-outline-warning" onclick="userAction(<?= $s['id'] ?>, 'unverify')" title="<?= $isAr ? 'إلغاء التأكيد' : 'Unverify' ?>">
                                            <i class="bi bi-envelope-x"></i>
                                        </button>
                                    <?php endif; ?>

                                    <?php if ($s['account_enabled']): ?>
                                        <button class="btn btn-outline-danger" onclick="userAction(<?= $s['id'] ?>, 'disable')" title="<?= $isAr ? 'تعطيل الحساب' : 'Disable Account' ?>">
                                            <i class="bi bi-person-x"></i>
                                        </button>
                                    <?php else: ?>
                                        <button class="btn btn-outline-success" onclick="userAction(<?= $s['id'] ?>, 'enable')" title="<?= $isAr ? 'تفعيل الحساب' : 'Enable Account' ?>">
                                            <i class="bi bi-person-check"></i>
                                        </button>
                                    <?php endif; ?>

                                    <button class="btn btn-outline-danger" onclick="userAction(<?= $s['id'] ?>, 'delete')" title="<?= $isAr ? 'حذف' : 'Delete' ?>">
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

<!-- Edit Student Modal -->
<div class="modal fade" id="editStudentModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i><?= $isAr ? 'تعديل بيانات الطالب' : 'Edit Student Profile' ?></h5>
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
                    <h6 class="text-primary mb-3"><i class="bi bi-person me-1"></i><?= $isAr ? 'البيانات الأساسية' : 'Basic Info' ?></h6>
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
                            <label class="form-label"><?= $isAr ? 'الجنس' : 'Gender' ?></label>
                            <select class="form-select" id="edit_gender">
                                <option value=""><?= $isAr ? 'اختر' : 'Select' ?></option>
                                <option value="male"><?= $isAr ? 'ذكر' : 'Male' ?></option>
                                <option value="female"><?= $isAr ? 'أنثى' : 'Female' ?></option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label"><?= $isAr ? 'تاريخ الميلاد' : 'Birth Date' ?></label>
                            <input type="date" class="form-control" id="edit_birth_date">
                        </div>
                    </div>

                    <!-- Personal Info -->
                    <h6 class="text-primary mb-3"><i class="bi bi-card-text me-1"></i><?= $isAr ? 'البيانات الشخصية' : 'Personal Info' ?></h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label"><?= $isAr ? 'الرقم القومي' : 'National ID' ?></label>
                            <input type="text" class="form-control" id="edit_national_id" pattern="\d{14}" maxlength="14">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><?= $isAr ? 'رقم الهاتف' : 'Phone' ?></label>
                            <input type="text" class="form-control" id="edit_phone" pattern="\d{11}" maxlength="11">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><?= $isAr ? 'المحافظة' : 'Governorate' ?></label>
                            <select class="form-select" id="edit_governorate">
                                <option value=""><?= $isAr ? 'اختر المحافظة' : 'Select Governorate' ?></option>
                                <?php foreach ($governorates as $gov): ?>
                                    <option value="<?= $gov ?>"><?= $gov ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><?= $isAr ? 'القسم' : 'Department' ?></label>
                            <input type="text" class="form-control" id="edit_section">
                        </div>
                        <div class="col-12">
                            <label class="form-label"><?= $isAr ? 'العنوان' : 'Address' ?></label>
                            <input type="text" class="form-control" id="edit_address">
                        </div>
                    </div>

                    <!-- Documents -->
                    <h6 class="text-primary mb-3"><i class="bi bi-image me-1"></i><?= $isAr ? 'المستندات' : 'Documents' ?></h6>
                    <div class="row g-3 mb-3">
                        <?php
                        $docTypes = [
                            'card' => $isAr ? 'بطاقة المعهد' : 'Institute ID',
                            'national_id' => $isAr ? 'البطاقة الشخصية' : 'National ID',
                            'receipt' => $isAr ? 'إيصال الدفع' : 'Payment Receipt',
                        ];
                        foreach ($docTypes as $dtype => $dlabel): ?>
                            <div class="col-md-4">
                                <label class="form-label"><?= $dlabel ?></label>
                                <div id="edit_img_preview_<?= $dtype ?>" class="mb-2 text-center">
                                    <span class="text-muted small"><?= $isAr ? 'لا توجد صورة' : 'No image' ?></span>
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
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= $isAr ? 'إغلاق' : 'Close' ?></button>
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
        const res = await fetch('/api/users.php?id=' + userId);
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
                const path = '/uploads/user_' + u.id + '/' + u[field];
                container.innerHTML = `<img src="${path}" class="img-thumbnail" style="max-height:100px; cursor:pointer;" onclick="previewImage('${path}', '${type}')">`;
            } else {
                container.innerHTML = `<span class="text-muted small">${isAr ? 'لا توجد صورة' : 'No image'}</span>`;
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
        const res = await fetch('/api/users.php', {
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
    xhr.open('POST', '/api/users.php', true);

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
                container.innerHTML = `<img src="${data.path}" class="img-thumbnail" style="max-height:100px; cursor:pointer;" onclick="previewImage('${data.path}', '${type}')">`;
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
        verify:   isAr ? 'تأكيد بريد هذا الطالب؟' : 'Verify this student\'s email?',
        unverify: isAr ? 'إلغاء تأكيد بريد هذا الطالب؟' : 'Unverify this student\'s email?',
        enable:   isAr ? 'تفعيل حساب هذا الطالب؟' : 'Enable this student\'s account?',
        disable:  isAr ? 'تعطيل حساب هذا الطالب؟' : 'Disable this student\'s account?',
        delete:   isAr ? 'حذف هذا الحساب نهائياً؟ سيتم حذف جميع بياناته.' : 'Permanently delete this account? All data will be removed.',
    };

    if (!confirm(confirmMsgs[action] || 'Are you sure?')) return;

    try {
        const res = await fetch('/api/users.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: userId, action: action })
        });
        const data = await res.json();
        if (data.success) {
            location.reload();
        } else {
            alert(data.error || 'Error');
        }
    } catch (e) {
        alert('Network error');
    }
}
</script>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
