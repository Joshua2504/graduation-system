<?php
/**
 * Admin — Professor Accounts Management Page
 */
require_once dirname(__DIR__) . '/includes/bootstrap.php';

require_admin();

$pdo = getDB();
$isAr = getLang() === 'ar';
$settings = getSettings();

// Fetch all professor accounts
$stmt = $pdo->query("SELECT id, name, email, gender, phone, section, profile_picture, account_enabled, created_at FROM users WHERE role = 'doctor' ORDER BY created_at DESC");
$professors = $stmt->fetchAll();

$pageTitle = __('professor_accounts');
require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/includes/navbar.php';
?>

<div class="container">
    <div class="d-flex align-items-center gap-2 mb-4 flex-wrap">
        <h3 class="mb-0"><i class="bi bi-person-workspace me-2"></i><?= __('professor_accounts') ?></h3>
        <div class="ms-auto d-flex gap-2 align-items-center flex-wrap justify-content-end">
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createProfessorModal">
                <i class="bi bi-person-plus me-1"></i><?= __('add_professor') ?>
            </button>
            <span class="badge bg-primary fs-6"><?= count($professors) ?> <?= __('professors_count') ?></span>
            <input type="text" class="form-control form-control-sm" id="searchInput" 
                   placeholder="<?= __('search_placeholder') ?>" style="min-width:140px; max-width:200px;">
        </div>
    </div>

    <?php if (empty($professors)): ?>
        <div class="alert alert-info text-center">
            <i class="bi bi-info-circle me-2"></i><?= __('no_professors') ?>
        </div>
    <?php else: ?>
        <div class="card shadow">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="professorsTable">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th data-sortable><?= __('name') ?></th>
                            <th data-sortable><?= __('email') ?></th>
                            <th data-sortable><?= __('section') ?></th>
                            <th data-sortable><?= __('account') ?></th>
                            <th data-sortable><?= __('registered') ?></th>
                            <th><?= __('actions') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($professors as $i => $p): ?>
                        <tr id="prof-row-<?= $p['id'] ?>" class="<?= !$p['account_enabled'] ? 'table-secondary' : '' ?>">
                            <td><?= $i + 1 ?></td>
                            <td class="fw-bold">
                                <div class="d-flex align-items-center gap-2">
                                    <?php if (!empty($settings['profile_pictures_enabled'])):
                                        $pPic = $p['profile_picture'] ?? '';
                                        $pPicUrl = $pPic ? secureFileUrl($p['id'], $pPic) : '';
                                    ?>
                                    <?php if ($pPicUrl): ?>
                                        <img src="<?= $pPicUrl ?>" class="profile-picture-sm rounded-circle" alt="">
                                    <?php else: ?>
                                        <i class="bi bi-person-circle fs-5 text-secondary"></i>
                                    <?php endif; ?>
                                    <?php else: ?>
                                        <i class="bi bi-person-circle fs-5 text-secondary"></i>
                                    <?php endif; ?>
                                    <?= sanitize($p['name']) ?>
                                </div>
                            </td>
                            <td><small><?= sanitize($p['email']) ?></small></td>
                            <td><small class="text-muted"><?= sanitize($p['section'] ?? '—') ?></small></td>
                            <td>
                                <?php if ($p['account_enabled']): ?>
                                    <span class="badge bg-success"><i class="bi bi-check-lg me-1"></i><?= __('active') ?></span>
                                <?php else: ?>
                                    <span class="badge bg-danger"><i class="bi bi-x-lg me-1"></i><?= __('disabled') ?></span>
                                <?php endif; ?>
                            </td>
                            <td><small class="text-muted"><?= date('Y-m-d', strtotime($p['created_at'])) ?></small></td>
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <?php if ($p['account_enabled']): ?>
                                            <li>
                                                <a class="dropdown-item text-warning" href="#" onclick="professorAction(<?= $p['id'] ?>, 'disable'); return false;">
                                                    <i class="bi bi-slash-circle me-2"></i><?= __('disable_account') ?>
                                                </a>
                                            </li>
                                        <?php else: ?>
                                            <li>
                                                <a class="dropdown-item text-success" href="#" onclick="professorAction(<?= $p['id'] ?>, 'enable'); return false;">
                                                    <i class="bi bi-check-circle me-2"></i><?= __('enable_account') ?>
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                        <li>
                                            <a class="dropdown-item" href="#" onclick="professorAction(<?= $p['id'] ?>, 'reset_password'); return false;">
                                                <i class="bi bi-key me-2"></i><?= __('reset_password') ?>
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item text-info" href="#" onclick="professorAction(<?= $p['id'] ?>, 'impersonate'); return false;">
                                                <i class="bi bi-box-arrow-in-right me-2"></i><?= __('login_as_professor') ?>
                                            </a>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <a class="dropdown-item text-danger" href="#" onclick="professorAction(<?= $p['id'] ?>, 'delete'); return false;">
                                                <i class="bi bi-trash me-2"></i><?= __('delete') ?>
                                            </a>
                                        </li>
                                    </ul>
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

<!-- Create Professor Modal -->
<div class="modal fade" id="createProfessorModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-person-plus me-2"></i><?= __('add_new_professor') ?></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="createProfError" class="alert alert-danger d-none"></div>
                <div id="createProfSuccess" class="alert alert-success d-none"></div>
                <form id="createProfessorForm">
                    <div class="mb-3">
                        <label class="form-label"><?= __('name') ?> <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= __('email') ?> <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= __('password') ?> <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="text" class="form-control" name="password" id="profPassword" required minlength="6">
                            <button type="button" class="btn btn-outline-secondary" onclick="generateProfPassword()">
                                <i class="bi bi-shuffle me-1"></i><?= __('generate_password') ?>
                            </button>
                        </div>
                        <small class="text-muted"><?= __('password_hint') ?></small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= __('section') ?></label>
                        <input type="text" class="form-control" name="section">
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="send_invite" id="profSendInvite" checked>
                        <label class="form-check-label" for="profSendInvite">
                            <i class="bi bi-envelope me-1"></i><?= __('send_welcome_email') ?>
                        </label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= __('cancel') ?></button>
                <button type="button" class="btn btn-primary" onclick="createProfessor()">
                    <i class="bi bi-person-plus me-1"></i><?= __('create_account') ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Reset Password Modal -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title"><i class="bi bi-key me-2"></i><?= __('reset_password') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="resetPassError" class="alert alert-danger d-none"></div>
                <div id="resetPassSuccess" class="alert alert-success d-none"></div>
                <input type="hidden" id="resetPassUserId">
                <div class="mb-3">
                    <label class="form-label"><?= __('new_password') ?></label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="resetPassValue" minlength="6">
                        <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('resetPassValue').value = generateRandomPassword()">
                            <i class="bi bi-shuffle"></i>
                        </button>
                    </div>
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="resetPassSendEmail" checked>
                    <label class="form-check-label" for="resetPassSendEmail">
                        <i class="bi bi-envelope me-1"></i><?= __('send_new_password_email') ?>
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= __('cancel') ?></button>
                <button type="button" class="btn btn-warning" onclick="resetPassword()">
                    <i class="bi bi-key me-1"></i><?= __('reset_password') ?>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Search filter
document.getElementById('searchInput')?.addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('#professorsTable tbody tr').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
});

function generateRandomPassword() {
    const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';
    let pass = '';
    for (let i = 0; i < 10; i++) pass += chars.charAt(Math.floor(Math.random() * chars.length));
    return pass;
}

function generateProfPassword() {
    document.getElementById('profPassword').value = generateRandomPassword();
}

async function createProfessor() {
    const form = document.getElementById('createProfessorForm');
    const data = Object.fromEntries(new FormData(form));
    data.send_invite = form.querySelector('[name="send_invite"]').checked ? 1 : 0;

    const errEl = document.getElementById('createProfError');
    const successEl = document.getElementById('createProfSuccess');
    errEl.classList.add('d-none');
    successEl.classList.add('d-none');

    try {
        const res = await fetch('/api/professors', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({...data, action: 'create_professor'})
        });
        const json = await res.json();
        if (json.success) {
            successEl.textContent = json.message || '<?= __('success') ?>';
            successEl.classList.remove('d-none');
            setTimeout(() => location.reload(), 1000);
        } else {
            errEl.textContent = json.error || '<?= __('error') ?>';
            errEl.classList.remove('d-none');
        }
    } catch (e) {
        errEl.textContent = '<?= __('error') ?>';
        errEl.classList.remove('d-none');
    }
}

function professorAction(userId, action) {
    const confirmActions = {
        'disable': '<?= __('confirm_disable_professor') ?>',
        'enable': '<?= __('confirm_enable_professor') ?>',
        'delete': '<?= __('confirm_delete_professor') ?>',
        'impersonate': '<?= __('confirm_impersonate_professor') ?>'
    };

    if (action === 'reset_password') {
        document.getElementById('resetPassUserId').value = userId;
        document.getElementById('resetPassValue').value = generateRandomPassword();
        document.getElementById('resetPassError').classList.add('d-none');
        document.getElementById('resetPassSuccess').classList.add('d-none');
        new bootstrap.Modal(document.getElementById('resetPasswordModal')).show();
        return;
    }

    if (confirmActions[action] && !confirm(confirmActions[action])) return;

    fetch('/api/professors', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({user_id: userId, action: action})
    })
    .then(r => r.json())
    .then(json => {
        if (json.success) {
            if (action === 'impersonate' && json.redirect) {
                window.location.href = json.redirect;
            } else {
                location.reload();
            }
        } else {
            alert(json.error || '<?= __('error') ?>');
        }
    })
    .catch(() => alert('<?= __('error') ?>'));
}

async function resetPassword() {
    const userId = document.getElementById('resetPassUserId').value;
    const password = document.getElementById('resetPassValue').value;
    const sendEmail = document.getElementById('resetPassSendEmail').checked;
    const errEl = document.getElementById('resetPassError');
    const successEl = document.getElementById('resetPassSuccess');
    errEl.classList.add('d-none');
    successEl.classList.add('d-none');

    if (!password || password.length < 6) {
        errEl.textContent = '<?= __('password_min_length') ?>';
        errEl.classList.remove('d-none');
        return;
    }

    try {
        const res = await fetch('/api/professors', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({user_id: parseInt(userId), action: 'reset_password', password, send_email: sendEmail})
        });
        const json = await res.json();
        if (json.success) {
            successEl.textContent = json.message || '<?= __('success') ?>';
            successEl.classList.remove('d-none');
            setTimeout(() => {
                bootstrap.Modal.getInstance(document.getElementById('resetPasswordModal')).hide();
            }, 1500);
        } else {
            errEl.textContent = json.error || '<?= __('error') ?>';
            errEl.classList.remove('d-none');
        }
    } catch (e) {
        errEl.textContent = '<?= __('error') ?>';
        errEl.classList.remove('d-none');
    }
}
</script>

<script>
// Initialize dropdowns inside .table-responsive with strategy: fixed
// This prevents clipping from overflow:auto without causing horizontal page overflow
document.querySelectorAll('.table-responsive .dropdown-toggle').forEach(function(toggle) {
    new bootstrap.Dropdown(toggle, {
        popperConfig: function(defaultConfig) {
            return Object.assign({}, defaultConfig, { strategy: 'fixed' });
        }
    });
});
</script>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
