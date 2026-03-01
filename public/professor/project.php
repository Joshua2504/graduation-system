<?php
/**
 * Professor — Single Project Review Page
 */
require_once dirname(__DIR__) . '/includes/bootstrap.php';

require_role('doctor');

$projectId = (int)($_GET['id'] ?? 0);
if ($projectId === 0) {
    redirect('/professor/dashboard');
}

$project = getProject($projectId);
if (!$project) {
    redirect('/professor/dashboard');
}

$members = getProjectMembers($projectId);
$pendingInvites = getProjectPendingInvitations($projectId);
$duplicates = findDuplicateProjects($projectId, $project['title']);
$reviews = getProjectReviews($projectId);
$isAr = getLang() === 'ar';

$statusLabels = getStatusLabels();
$statusColors = getStatusColors();

$pageTitle = sanitize($project['title']);
require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/includes/navbar.php';
?>

<div class="container">
    <!-- Back button -->
    <a href="/professor/dashboard" class="btn btn-outline-secondary mb-3">
        <i class="bi bi-arrow-<?= $isAr ? 'right' : 'left' ?> me-1"></i>
        <?= __('back_to_list') ?>
    </a>

    <!-- Duplicate Warning -->
    <?php if (!empty($duplicates)): ?>
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <strong><?= __('duplicate_warning') ?></strong>
            <?php foreach ($duplicates as $dup): ?>
                <a href="/professor/project?id=<?= $dup['id'] ?>" class="btn btn-sm btn-warning ms-2">
                    <i class="bi bi-link-45deg me-1"></i><?= __('view_similar') ?> (#<?= $dup['id'] ?>)
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Project Info Card -->
    <div class="card shadow mb-4">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-clipboard-data me-2"></i><span id="projectTitleDisplay"><?= sanitize($project['title']) ?></span></h5>
            <span class="badge bg-<?= $statusColors[$project['status']] ?> fs-6">
                <?= $statusLabels[$project['status']] ?>
            </span>
        </div>
        <div class="card-body">
            <!-- View mode -->
            <div id="projectViewMode">
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
                    <?php if (!empty($project['reviewer_name'])): ?>
                        <div class="col-md-4 mb-2">
                            <strong><?= __('reviewed_by') ?>:</strong>
                            <span><?= sanitize($project['reviewer_name']) ?></span>
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

                <div class="text-end mt-3">
                    <button type="button" class="btn btn-outline-primary" onclick="toggleEditMode(true)">
                        <i class="bi bi-pencil-square me-1"></i><?= __('edit_project_info') ?>
                    </button>
                </div>
            </div>

            <!-- Edit mode -->
            <div id="projectEditMode" class="d-none">
                <form id="editProjectForm">
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold"><?= __('project_name') ?></label>
                            <input type="text" class="form-control" id="editTitle" value="<?= sanitize($project['title']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold"><?= __('project_type') ?></label>
                            <input type="text" class="form-control" id="editType" value="<?= sanitize($project['type']) ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold"><i class="bi bi-card-text me-1"></i><?= __('project_description') ?></label>
                        <div class="simple-editor-toolbar">
                            <button type="button" class="btn" onclick="editorCmd('bold')" title="<?= __('bold') ?>"><i class="bi bi-type-bold"></i></button>
                            <button type="button" class="btn" onclick="editorCmd('italic')" title="<?= __('italic') ?>"><i class="bi bi-type-italic"></i></button>
                            <button type="button" class="btn" onclick="editorCmd('underline')" title="<?= __('underline_text') ?>"><i class="bi bi-type-underline"></i></button>
                            <div class="vr mx-1 opacity-25"></div>
                            <button type="button" class="btn" onclick="editorCmd('insertUnorderedList')" title="<?= __('bullet_list') ?>"><i class="bi bi-list-ul"></i></button>
                            <button type="button" class="btn" onclick="editorCmd('insertOrderedList')" title="<?= __('numbered_list') ?>"><i class="bi bi-list-ol"></i></button>
                            <div class="vr mx-1 opacity-25"></div>
                            <button type="button" class="btn" onclick="editorInsertLink()" title="<?= __('insert_link') ?>"><i class="bi bi-link-45deg"></i></button>
                            <div class="vr mx-1 opacity-25"></div>
                            <button type="button" class="btn" onclick="document.getElementById('editorFileInput').click()" title="<?= __('upload_file') ?>"><i class="bi bi-image"></i></button>
                            <input type="file" id="editorFileInput" accept="image/jpeg,image/png,image/gif,image/webp" class="d-none">
                        </div>
                        <div id="editDescription" class="simple-editor-content" contenteditable="true" data-placeholder="<?= __('description_placeholder') ?>"><?= sanitizeHtml($project['description'] ?? '') ?></div>
                    </div>
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-outline-secondary" onclick="toggleEditMode(false)">
                            <i class="bi bi-x-lg me-1"></i><?= __('cancel') ?>
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-2"></i><?= __('save_changes') ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Team Members -->
    <div class="card shadow-sm mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-people-fill me-2"></i><?= __('team_members') ?> (<?= count($members) ?>)
                <?php if (!empty($pendingInvites)): ?>
                    <span class="badge bg-warning text-dark ms-2"><?= count($pendingInvites) ?> <?= __('pending') ?></span>
                <?php endif; ?>
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th><?= __('name') ?></th>
                            <th><?= __('student_code') ?></th>
                            <th><?= __('role') ?></th>
                            <th><?= __('profile') ?></th>
                            <th><?= __('documents') ?></th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $ppSettings = getSettings(); ?>
                        <?php foreach ($members as $i => $member):
                            $memberPicUrl = '';
                            if (!empty($ppSettings['profile_pictures_enabled'])) {
                                $memberPic = $member['profile_picture'] ?? '';
                                $memberPicUrl = $memberPic ? secureFileUrl($member['id'], $memberPic) : '';
                            }
                            $complete = isProfileComplete($member);
                            $docsCount = 0;
                            $docsTotal = 3;
                            if (!empty($member['card_image'])) $docsCount++;
                            if (!empty($member['national_id_image'])) $docsCount++;
                            if (!empty($member['receipt_image'])) $docsCount++;
                        ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <?php if ($memberPicUrl): ?>
                                            <img src="<?= $memberPicUrl ?>" class="profile-picture-sm rounded-circle" alt="">
                                        <?php else: ?>
                                            <i class="bi bi-person-circle fs-5 text-secondary"></i>
                                        <?php endif; ?>
                                        <?= sanitize($member['name']) ?>
                                    </div>
                                </td>
                                <td><code><?= sanitize($member['student_code'] ?? '-') ?></code></td>
                                <td>
                                    <span class="badge bg-<?= $member['member_role'] === 'leader' ? 'primary' : 'info' ?>">
                                        <?= $member['member_role'] === 'leader' ? __('leader') : __('member') ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($complete): ?>
                                        <span class="text-success"><i class="bi bi-check-circle-fill"></i></span>
                                    <?php else: ?>
                                        <span class="text-warning"><i class="bi bi-exclamation-circle-fill"></i></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $docsCount === $docsTotal ? 'success' : ($docsCount > 0 ? 'warning text-dark' : 'danger') ?>">
                                        <?= $docsCount ?>/<?= $docsTotal ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-1">
                                        <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#memberDetail<?= $member['id'] ?>" title="<?= __('details') ?>">
                                            <i class="bi bi-chevron-down"></i>
                                        </button>
                                        <?php if ($member['member_role'] !== 'leader'): ?>
                                            <button class="btn btn-sm btn-outline-primary" onclick="setLeader(<?= $member['id'] ?>)" title="<?= __('set_as_leader') ?>">
                                                <i class="bi bi-star"></i>
                                            </button>
                                        <?php endif; ?>
                                        <button class="btn btn-sm btn-outline-danger" onclick="removeMember(<?= $member['id'] ?>, <?= htmlspecialchars(json_encode($member['name']), ENT_QUOTES, 'UTF-8') ?>)" title="<?= __('remove_from_project') ?>">
                                            <i class="bi bi-person-x"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr class="collapse" id="memberDetail<?= $member['id'] ?>">
                                <td colspan="7" class="bg-light p-3">
                                    <div class="row mb-3">
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
                                            $imgPath = secureFileUrl($memberId, $imgFile);
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
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

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
                <div class="form-check mb-3" id="allowResubmitGroup">
                    <input class="form-check-input" type="checkbox" id="allow_resubmit" checked>
                    <label class="form-check-label" for="allow_resubmit">
                        <strong><?= __('allow_resubmit') ?></strong>
                        <small class="text-muted d-block"><?= __('allow_resubmit_desc') ?></small>
                    </label>
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

    <!-- Reject Accepted Project (only if accepted) -->
    <?php if ($project['status'] === 'accepted'): ?>
        <div class="card shadow mt-4 border-danger">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0"><i class="bi bi-exclamation-triangle me-2"></i><?= __('reject_accepted') ?></h5>
            </div>
            <div class="card-body p-4">
                <div class="mb-3">
                    <label for="reject_accepted_note" class="form-label fw-bold"><?= __('write_note') ?></label>
                    <textarea class="form-control" id="reject_accepted_note" rows="3" 
                              placeholder="<?= __('write_note_placeholder') ?>"></textarea>
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="reject_accepted_allow_resubmit" checked>
                    <label class="form-check-label" for="reject_accepted_allow_resubmit">
                        <strong><?= __('allow_resubmit') ?></strong>
                        <small class="text-muted d-block"><?= __('allow_resubmit_desc') ?></small>
                    </label>
                </div>
                <div id="reject-accepted-error" class="alert alert-danger d-none"></div>
                <div id="reject-accepted-success" class="alert alert-success d-none"></div>
                <button type="button" class="btn btn-danger btn-lg w-100" id="btn-reject-accepted">
                    <i class="bi bi-x-circle me-2"></i><?= __('reject') ?>
                </button>
            </div>
        </div>
    <?php endif; ?>

    <!-- Toggle Resubmission (only if rejected) -->
    <?php if ($project['status'] === 'rejected'): ?>
        <div class="card shadow mt-4">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="mb-1"><?= __('allow_resubmit') ?></h6>
                    <small class="text-muted"><?= __('allow_resubmit_desc') ?></small>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" 
                           id="toggleResubmit" <?= !empty($project['allow_resubmit']) ? 'checked' : '' ?>
                           style="width: 3em; height: 1.5em;">
                </div>
            </div>
            <div id="resubmit-alert" class="alert d-none mx-3 mb-3"></div>
        </div>
    <?php endif; ?>

    <!-- Review History -->
    <?php if (!empty($reviews)): ?>
        <div class="card shadow mt-4">
            <div class="card-header bg-secondary bg-opacity-25">
                <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i><?= __('review_history') ?></h5>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <?php foreach ($reviews as $rev): ?>
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <span class="badge bg-<?= $rev['action'] === 'accepted' ? 'success' : 'danger' ?> me-2">
                                        <?= $rev['action'] === 'accepted' ? __('status_accepted') : __('status_rejected') ?>
                                    </span>
                                    <?php if ($rev['action'] === 'rejected' && $rev['allow_resubmit'] !== null): ?>
                                        <span class="badge bg-<?= $rev['allow_resubmit'] ? 'info' : 'dark' ?>">
                                            <?= $rev['allow_resubmit'] ? __('rejected_resubmittable') : __('rejected_final') ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if (!empty($rev['reviewer_name'])): ?>
                                        <small class="text-muted ms-2"><i class="bi bi-person me-1"></i><?= sanitize($rev['reviewer_name']) ?></small>
                                    <?php endif; ?>
                                </div>
                                <small class="text-muted"><?= date('Y-m-d H:i', strtotime($rev['created_at'])) ?></small>
                            </div>
                            <?php if (!empty($rev['note'])): ?>
                                <p class="mb-0 mt-2 text-muted"><i class="bi bi-chat-left-text me-1"></i><?= sanitize($rev['note']) ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
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

<script src="/assets/js/editor.js"></script>
<script>
function showImageModal(src, title) {
    document.getElementById('imageModalLabel').textContent = title;
    document.getElementById('imageModalImg').src = src;
    const modal = new bootstrap.Modal(document.getElementById('imageModal'));
    modal.show();
}

async function resendInvitation(invitationId) {
    try {
        const res = await fetch('/api/invitations', {
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

// Toggle resubmit for rejected projects
document.getElementById('toggleResubmit')?.addEventListener('change', async function() {
    const alertEl = document.getElementById('resubmit-alert');
    try {
        const res = await fetch('/api/project', {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'toggle_resubmit', project_id: PROJECT_ID, allow_resubmit: this.checked })
        });
        const data = await res.json();
        if (data.success) {
            alertEl.className = 'alert alert-success mx-3 mb-3';
            alertEl.textContent = data.message;
            alertEl.classList.remove('d-none');
            setTimeout(() => alertEl.classList.add('d-none'), 3000);
        } else {
            throw new Error(data.error);
        }
    } catch (err) {
        this.checked = !this.checked;
        alertEl.className = 'alert alert-danger mx-3 mb-3';
        alertEl.textContent = err.message;
        alertEl.classList.remove('d-none');
    }
});

// Initialize shared editor with paste/drag-drop/resize
initEditor('editDescription', PROJECT_ID, <?= json_encode(__('uploading_file')) ?>);

// Save project edits
document.getElementById('editProjectForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const title = document.getElementById('editTitle').value.trim();
    const type = document.getElementById('editType').value.trim();
    const descEl = document.getElementById('editDescription');
    const description = descEl ? descEl.innerHTML.trim() : '';
    try {
        const res = await fetch('/api/project', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ project_id: PROJECT_ID, title, type, description })
        });
        const data = await res.json();
        if (data.success) location.reload();
        else alert(data.error);
    } catch (err) { alert(err.message); }
});

// Search & assign students
document.getElementById('assignStudentSearch')?.addEventListener('keydown', e => {
    if (e.key === 'Enter') { e.preventDefault(); searchAndAssign(); }
});

async function searchAndAssign() {
    const query = document.getElementById('assignStudentSearch').value.trim();
    if (!query) return;
    
    try {
        const res = await fetch('/api/project', {
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
        const res = await fetch('/api/project', {
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
        const res = await fetch('/api/project', {
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
        const res = await fetch('/api/project', {
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

<?php if ($project['status'] === 'under_review'): ?>
// Review actions
document.getElementById('btn-accept')?.addEventListener('click', () => reviewProject('accept'));
document.getElementById('btn-reject')?.addEventListener('click', () => reviewProject('reject'));

async function reviewProject(action) {
    const note = document.getElementById('doctor_note').value.trim();
    const errorEl = document.getElementById('review-error');
    const successEl = document.getElementById('review-success');
    const allowResubmit = action === 'reject' ? document.getElementById('allow_resubmit').checked : true;

    // Confirm
    const confirmMsg = action === 'accept' 
        ? <?= json_encode(__('confirm_accept_project')) ?>
        : <?= json_encode(__('confirm_reject_project')) ?>;
    
    if (!confirm(confirmMsg)) return;

    // Disable buttons
    document.getElementById('btn-accept').disabled = true;
    document.getElementById('btn-reject').disabled = true;

    try {
        const res = await fetch('/api/review', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                project_id: <?= $projectId ?>,
                action: action,
                doctor_note: note,
                allow_resubmit: allowResubmit
            })
        });

        const data = await res.json();

        if (data.success) {
            errorEl.classList.add('d-none');
            successEl.textContent = data.message;
            successEl.classList.remove('d-none');
            
            setTimeout(() => {
                window.location.href = '/professor/dashboard';
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

<?php if ($project['status'] === 'accepted'): ?>
// Reject accepted project
document.getElementById('btn-reject-accepted')?.addEventListener('click', async () => {
    const note = document.getElementById('reject_accepted_note').value.trim();
    const allowResubmit = document.getElementById('reject_accepted_allow_resubmit').checked;
    const errorEl = document.getElementById('reject-accepted-error');
    const successEl = document.getElementById('reject-accepted-success');
    const btn = document.getElementById('btn-reject-accepted');

    if (!confirm(<?= json_encode(__('confirm_reject_accepted_project')) ?>)) return;

    btn.disabled = true;

    try {
        const res = await fetch('/api/review', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                project_id: <?= $projectId ?>,
                action: 'reject',
                doctor_note: note,
                allow_resubmit: allowResubmit
            })
        });

        const data = await res.json();

        if (data.success) {
            errorEl.classList.add('d-none');
            successEl.textContent = data.message;
            successEl.classList.remove('d-none');
            setTimeout(() => location.reload(), 1500);
        } else {
            throw new Error(data.error || 'Operation failed');
        }
    } catch (err) {
        errorEl.textContent = err.message;
        errorEl.classList.remove('d-none');
        btn.disabled = false;
    }
});
<?php endif; ?>
</script>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
