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
$stmt = $pdo->query("SELECT id, name, email, student_code, email_verified, account_enabled, created_at FROM users WHERE role = 'student' ORDER BY created_at DESC");
$students = $stmt->fetchAll();

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

<script>
const isAr = <?= json_encode($isAr) ?>;

// Search / filter
document.getElementById('searchInput')?.addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('#studentsTable tbody tr').forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(q) ? '' : 'none';
    });
});

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
