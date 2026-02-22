<?php
/**
 * Professor — Single Project Review Page
 */
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/lang.php';

require_role('doctor');

$projectId = (int)($_GET['id'] ?? 0);
if ($projectId === 0) {
    redirect('/professor/dashboard.php');
}

$project = getProject($projectId);
if (!$project) {
    redirect('/professor/dashboard.php');
}

$members = getProjectMembers($projectId);
$pendingInvites = getProjectPendingInvitations($projectId);
$duplicates = findDuplicateProjects($projectId, $project['title']);
$isAr = getLang() === 'ar';

$statusLabels = [
    'draft' => __('status_draft'),
    'under_review' => __('status_under_review'),
    'accepted' => __('status_accepted'),
    'rejected' => __('status_rejected'),
];
$statusColors = [
    'draft' => 'secondary',
    'under_review' => 'warning',
    'accepted' => 'success',
    'rejected' => 'danger',
];

$pageTitle = sanitize($project['title']);
require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/includes/navbar.php';
?>

<div class="container">
    <!-- Back button -->
    <a href="/professor/dashboard.php" class="btn btn-outline-secondary mb-3">
        <i class="bi bi-arrow-<?= $isAr ? 'right' : 'left' ?> me-1"></i>
        <?= __('back_to_list') ?>
    </a>

    <!-- Duplicate Warning -->
    <?php if (!empty($duplicates)): ?>
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <strong><?= __('duplicate_warning') ?></strong>
            <?php foreach ($duplicates as $dup): ?>
                <a href="/professor/project.php?id=<?= $dup['id'] ?>" class="btn btn-sm btn-warning ms-2">
                    <i class="bi bi-link-45deg me-1"></i><?= __('view_similar') ?> (#<?= $dup['id'] ?>)
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Project Info Card -->
    <div class="card shadow mb-4">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-clipboard-data me-2"></i><?= sanitize($project['title']) ?></h5>
            <span class="badge bg-<?= $statusColors[$project['status']] ?> fs-6">
                <?= $statusLabels[$project['status']] ?>
            </span>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 mb-2">
                    <strong><?= __('project_type') ?>:</strong>
                    <span><?= sanitize($project['type']) ?></span>
                </div>
                <div class="col-md-4 mb-2">
                    <strong><?= __('team_leader') ?>:</strong>
                    <span><?= $project['leader_name'] ? sanitize($project['leader_name']) . ' (' . sanitize($project['leader_email']) . ')' : '<em class="text-muted">' . __('no_leader_assigned') . '</em>' ?></span>
                </div>
                <div class="col-md-4 mb-2">
                    <strong><?= __('submission_date') ?>:</strong>
                    <span><?= $project['submission_date'] ? date('Y-m-d H:i', strtotime($project['submission_date'])) : '-' ?></span>
                </div>
                <?php if ($project['group_number']): ?>
                    <div class="col-md-4 mb-2">
                        <strong><?= __('group_number') ?>:</strong>
                        <span class="badge bg-success fs-6"><?= $project['group_number'] ?></span>
                    </div>
                <?php endif; ?>
                <?php if ($project['doctor_note']): ?>
                    <div class="col-12 mt-2">
                        <strong><?= __('doctor_note') ?>:</strong>
                        <p class="mt-1 p-2 bg-light rounded"><?= sanitize($project['doctor_note']) ?></p>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (!empty($project['description'])): ?>
                <hr>
                <div>
                    <strong><i class="bi bi-card-text me-1"></i><?= __('project_description') ?>:</strong>
                    <div class="mt-2 p-3 bg-light rounded project-description"><?= sanitizeHtml($project['description']) ?></div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Team Members -->
    <h5 class="mb-3"><i class="bi bi-people-fill me-2"></i><?= __('team_members') ?> (<?= count($members) ?>)
        <?php if (!empty($pendingInvites)): ?>
            <span class="badge bg-warning text-dark ms-2"><?= count($pendingInvites) ?> <?= __('pending') ?></span>
        <?php endif; ?>
    </h5>

    <?php foreach ($members as $i => $member): ?>
        <div class="card shadow-sm mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">
                    <?= __('student') ?> <?= $i + 1 ?>: <?= sanitize($member['name']) ?>
                    <?php if ($member['member_role'] === 'leader'): ?>
                        <span class="badge bg-primary ms-2"><?= __('leader') ?></span>
                    <?php endif; ?>
                </h6>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-secondary"><?= sanitize($member['student_code'] ?? '-') ?></span>
                    <?php if ($member['member_role'] !== 'leader'): ?>
                        <button class="btn btn-sm btn-outline-primary" onclick="setLeader(<?= $member['id'] ?>)" title="<?= __('set_as_leader') ?>">
                            <i class="bi bi-star"></i>
                        </button>
                    <?php endif; ?>
                    <button class="btn btn-sm btn-outline-danger" onclick="removeMember(<?= $member['id'] ?>, '<?= sanitize($member['name']) ?>')" title="<?= __('remove_from_project') ?>">
                        <i class="bi bi-person-x"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 mb-2">
                        <small class="text-muted"><?= __('gender') ?></small><br>
                        <?php if ($member['gender']): ?>
                            <?= $member['gender'] === 'male' ? __('male') : __('female') ?>
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-3 mb-2">
                        <small class="text-muted"><?= __('national_id') ?></small><br>
                        <?= sanitize($member['national_id'] ?? '-') ?>
                    </div>
                    <div class="col-md-3 mb-2">
                        <small class="text-muted"><?= __('birth_date') ?></small><br>
                        <?= $member['birth_date'] ?? '-' ?>
                    </div>
                    <div class="col-md-3 mb-2">
                        <small class="text-muted"><?= __('governorate') ?></small><br>
                        <?= sanitize($member['governorate'] ?? '-') ?>
                    </div>
                    <div class="col-md-4 mb-2">
                        <small class="text-muted"><?= __('address') ?></small><br>
                        <?= sanitize($member['address'] ?? '-') ?>
                    </div>
                    <div class="col-md-4 mb-2">
                        <small class="text-muted"><?= __('phone') ?></small><br>
                        <?= sanitize($member['phone'] ?? '-') ?>
                    </div>
                    <div class="col-md-4 mb-2">
                        <small class="text-muted"><?= __('section') ?></small><br>
                        <?= sanitize($member['section'] ?? '-') ?>
                    </div>
                </div>

                <!-- Images -->
                <hr>
                <div class="row">
                    <?php
                    $imageTypes = [
                        'card_image' => __('institute_id'),
                        'national_id_image' => __('national_id_card'),
                        'receipt_image' => __('payment_receipt'),
                    ];
                    $memberId = $member['id'];
                    foreach ($imageTypes as $field => $label):
                        $imgFile = $member[$field] ?? '';
                        $imgPath = "/uploads/user_{$memberId}/{$imgFile}";
                    ?>
                        <div class="col-md-4 text-center mb-2">
                            <small class="text-muted d-block mb-1"><?= $label ?></small>
                            <?php if ($imgFile): ?>
                                <img src="<?= $imgPath ?>" class="img-thumbnail student-image" 
                                     alt="<?= $label ?>" style="max-height: 150px; cursor: pointer;"
                                     onclick="showImageModal('<?= $imgPath ?>', '<?= $label ?>')">
                            <?php else: ?>
                                <span class="text-danger"><i class="bi bi-x-circle"></i> <?= __('missing') ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <?php if (!empty($pendingInvites)): ?>
        <div class="card shadow-sm mb-3 border-warning">
            <div class="card-header bg-warning bg-opacity-25">
                <h6 class="mb-0"><i class="bi bi-hourglass-split me-2"></i><?= __('pending_invitations') ?></h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th><?= __('invited_student') ?></th>
                                <th><?= __('email_col') ?></th>
                                <th><?= __('code') ?></th>
                                <th><?= __('invited_by') ?></th>
                                <th><?= __('invited_at') ?></th>
                                <th><?= __('expires') ?></th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendingInvites as $inv): ?>
                                <tr>
                                    <td>
                                        <?php if ($inv['invited_name']): ?>
                                            <?= sanitize($inv['invited_name']) ?>
                                        <?php else: ?>
                                            <span class="text-muted fst-italic"><?= __('general_invite_link') ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $inv['invited_email'] ? sanitize($inv['invited_email']) : '-' ?></td>
                                    <td><code><?= $inv['invited_student_code'] ? sanitize($inv['invited_student_code']) : '-' ?></code></td>
                                    <td><?= sanitize($inv['invited_by_name']) ?></td>
                                    <td><small><?= date('Y-m-d H:i', strtotime($inv['created_at'])) ?></small></td>
                                    <td><small><?= date('Y-m-d H:i', strtotime($inv['expires_at'])) ?></small></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" onclick="resendInvitation(<?= $inv['id'] ?>)" title="<?= __('resend') ?>">
                                            <i class="bi bi-arrow-repeat"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Assign Students (doctor can always add/remove) -->
    <div class="card shadow mb-4">
        <div class="card-header bg-info bg-opacity-25">
            <h5 class="mb-0"><i class="bi bi-person-plus me-2"></i><?= __('assign_students') ?></h5>
        </div>
        <div class="card-body">
            <div class="input-group mb-2">
                <input type="text" class="form-control" id="assignStudentSearch" 
                       placeholder="<?= __('search_students') ?>">
                <button type="button" class="btn btn-outline-primary" onclick="searchAndAssign()">
                    <i class="bi bi-search me-1"></i><?= __('search') ?>
                </button>
            </div>
            <div id="assignSearchResults" class="list-group mb-2"></div>
            <div id="assignAlert" class="alert d-none"></div>
        </div>
    </div>

    <!-- Review Actions (only if under_review) -->
    <?php if ($project['status'] === 'under_review'): ?>
        <div class="card shadow mt-4">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0"><i class="bi bi-pencil-square me-2"></i><?= __('review_project') ?></h5>
            </div>
            <div class="card-body p-4">
                <div class="mb-3">
                    <label for="doctor_note" class="form-label fw-bold"><?= __('write_note') ?></label>
                    <textarea class="form-control" id="doctor_note" rows="3" 
                              placeholder="<?= __('write_note_placeholder') ?>"></textarea>
                </div>
                <div id="review-error" class="alert alert-danger d-none"></div>
                <div id="review-success" class="alert alert-success d-none"></div>
                <div class="d-flex gap-3">
                    <button type="button" class="btn btn-success btn-lg flex-fill" id="btn-accept">
                        <i class="bi bi-check-circle me-2"></i><?= __('accept') ?>
                    </button>
                    <button type="button" class="btn btn-danger btn-lg flex-fill" id="btn-reject">
                        <i class="bi bi-x-circle me-2"></i><?= __('reject') ?>
                    </button>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Image Modal -->
<div class="modal fade" id="imageModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="imageModalLabel"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <img src="" id="imageModalImg" class="img-fluid" alt="">
            </div>
        </div>
    </div>
</div>

<script>
function showImageModal(src, title) {
    document.getElementById('imageModalLabel').textContent = title;
    document.getElementById('imageModalImg').src = src;
    const modal = new bootstrap.Modal(document.getElementById('imageModal'));
    modal.show();
}

async function resendInvitation(invitationId) {
    try {
        const res = await fetch('/api/invitations.php', {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ invitation_id: invitationId })
        });
        const data = await res.json();
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert(data.error);
        }
    } catch (err) { alert(err.message); }
}

const PROJECT_ID = <?= $projectId ?>;

// Search & assign students
document.getElementById('assignStudentSearch')?.addEventListener('keydown', e => {
    if (e.key === 'Enter') { e.preventDefault(); searchAndAssign(); }
});

async function searchAndAssign() {
    const query = document.getElementById('assignStudentSearch').value.trim();
    if (!query) return;
    
    try {
        const res = await fetch('/api/project.php', {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'search_students', project_id: PROJECT_ID, query })
        });
        const data = await res.json();
        const container = document.getElementById('assignSearchResults');
        container.innerHTML = '';
        
        if (!data.students || data.students.length === 0) {
            container.innerHTML = '<div class="list-group-item text-muted"><?= __('no_results') ?></div>';
            return;
        }
        
        data.students.forEach(s => {
            const item = document.createElement('button');
            item.type = 'button';
            item.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-center';
            const esc = str => { const d = document.createElement('div'); d.textContent = str || ''; return d.innerHTML; };
            item.innerHTML = `
                <span><strong>${esc(s.name)}</strong> <small class="text-muted">${esc(s.email)}</small> <code>${esc(s.student_code || '')}</code></span>
                <span class="badge bg-primary"><i class="bi bi-plus me-1"></i><?= __('add') ?></span>`;
            item.onclick = () => assignStudent(s.id);
            container.appendChild(item);
        });
    } catch (err) {
        console.error(err);
    }
}

async function assignStudent(studentId) {
    try {
        const res = await fetch('/api/project.php', {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'add_student', project_id: PROJECT_ID, student_id: studentId })
        });
        const data = await res.json();
        if (data.success) location.reload();
        else showAssignAlert(data.error, 'danger');
    } catch (err) { showAssignAlert(err.message, 'danger'); }
}

async function removeMember(studentId, name) {
    if (!confirm(<?= json_encode(__('confirm_remove_from_project')) ?>)) return;
    try {
        const res = await fetch('/api/project.php', {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'remove_student', project_id: PROJECT_ID, student_id: studentId })
        });
        const data = await res.json();
        if (data.success) location.reload();
        else alert(data.error);
    } catch (err) { alert(err.message); }
}

async function setLeader(studentId) {
    if (!confirm(<?= json_encode(__('set_as_leader')) ?> + '?')) return;
    try {
        const res = await fetch('/api/project.php', {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'set_leader', project_id: PROJECT_ID, student_id: studentId })
        });
        const data = await res.json();
        if (data.success) location.reload();
        else alert(data.error);
    } catch (err) { alert(err.message); }
}

function showAssignAlert(msg, type) {
    const el = document.getElementById('assignAlert');
    el.className = 'alert alert-' + type;
    el.textContent = msg;
    el.classList.remove('d-none');
    setTimeout(() => el.classList.add('d-none'), 3000);
}
}

<?php if ($project['status'] === 'under_review'): ?>
// Review actions
document.getElementById('btn-accept')?.addEventListener('click', () => reviewProject('accept'));
document.getElementById('btn-reject')?.addEventListener('click', () => reviewProject('reject'));

async function reviewProject(action) {
    const note = document.getElementById('doctor_note').value.trim();
    const errorEl = document.getElementById('review-error');
    const successEl = document.getElementById('review-success');

    // Confirm
    const confirmMsg = action === 'accept' 
        ? <?= json_encode(__('confirm_accept_project')) ?>
        : <?= json_encode(__('confirm_reject_project')) ?>;
    
    if (!confirm(confirmMsg)) return;

    // Disable buttons
    document.getElementById('btn-accept').disabled = true;
    document.getElementById('btn-reject').disabled = true;

    try {
        const res = await fetch('/api/review.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                project_id: <?= $projectId ?>,
                action: action,
                doctor_note: note
            })
        });

        const data = await res.json();

        if (data.success) {
            errorEl.classList.add('d-none');
            successEl.textContent = data.message;
            successEl.classList.remove('d-none');
            
            setTimeout(() => {
                window.location.href = '/professor/dashboard.php';
            }, 1500);
        } else {
            throw new Error(data.error || 'Operation failed');
        }
    } catch (err) {
        errorEl.textContent = err.message;
        errorEl.classList.remove('d-none');
        document.getElementById('btn-accept').disabled = false;
        document.getElementById('btn-reject').disabled = false;
    }
}
<?php endif; ?>
</script>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
