<?php
/**
 * Student Project Page — view project details, manage team, invite members, submit
 */
require_once dirname(__DIR__) . '/includes/bootstrap.php';

require_role('student');

$projectId = (int)($_GET['id'] ?? 0);
if ($projectId === 0) redirect('/student/dashboard');

$userId = current_user_id();

if (!isProjectMember($projectId, $userId)) {
    redirect('/student/dashboard');
}

$project = getProject($projectId);
if (!$project) redirect('/student/dashboard');

$members = getProjectMembers($projectId);
$pendingInvites = getProjectPendingInvitations($projectId);
$isLeader = isProjectLeader($projectId, $userId);
$isAr = getLang() === 'ar';
$settings = getSettings();
$memberCount = count($members);
$minSize = (int)$settings['min_team_size'];
$maxSize = (int)$settings['max_team_size'];
$isFull = $memberCount >= $maxSize;

// Check if all profiles complete
$allProfilesComplete = true;
foreach ($members as $m) {
    if (!isProfileComplete($m)) {
        $allProfilesComplete = false;
        break;
    }
}

$canSubmit = $isLeader && $project['status'] === 'draft' && $memberCount >= $minSize && $allProfilesComplete;
$canResubmit = $isLeader && $project['status'] === 'rejected' && $memberCount >= $minSize && $allProfilesComplete;
$canTransferLeadership = $isLeader && !empty($settings['leader_transfer']) && $memberCount > 1;

$statusLabels = getStatusLabels();
$statusColors = getStatusColors();

$pageTitle = sanitize($project['title']);
require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/includes/navbar.php';
?>

<div class="container">
    <!-- Back -->
    <a href="/student/dashboard" class="btn btn-outline-secondary mb-3">
        <i class="bi bi-arrow-<?= $isAr ? 'right' : 'left' ?> me-1"></i>
        <?= __('dashboard') ?>
    </a>

    <!-- Project Info -->
    <div class="card shadow mb-4">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-clipboard-data me-2"></i><?= sanitize($project['title']) ?></h5>
            <span class="badge bg-<?= $statusColors[$project['status']] ?> fs-6">
                <?= $statusLabels[$project['status']] ?>
            </span>
        </div>
        <div class="card-body">
            <!-- View mode -->
            <div id="projectViewMode">
                <div class="row">
                    <div class="col-md-3 mb-2">
                        <strong><?= __('project_type') ?>:</strong>
                        <span><?= sanitize($project['type']) ?: '-' ?></span>
                    </div>
                    <div class="col-md-3 mb-2">
                        <strong><?= __('team_leader') ?>:</strong>
                        <span><?= sanitize($project['leader_name']) ?></span>
                    </div>
                    <div class="col-md-3 mb-2">
                        <strong><?= __('member_count') ?>:</strong>
                        <span><?= $memberCount ?>/<?= $maxSize ?></span>
                    </div>
                    <?php if ($project['group_number']): ?>
                        <div class="col-md-3 mb-2">
                            <strong><?= __('group_number') ?>:</strong>
                            <span class="badge bg-success fs-6"><?= $project['group_number'] ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ($project['submission_date']): ?>
                        <div class="col-md-3 mb-2">
                            <strong><?= __('submission_date') ?>:</strong>
                            <span><?= date('Y-m-d H:i', strtotime($project['submission_date'])) ?></span>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (!empty($project['description'])): ?>
                    <hr>
                    <div>
                        <strong><i class="bi bi-card-text me-1"></i><?= __('project_description') ?>:</strong>
                        <div class="mt-2 p-3 bg-light rounded project-description"><?= sanitizeHtml($project['description']) ?></div>
                    </div>
                <?php elseif (empty($project['description'])): ?>
                    <hr>
                    <div class="text-muted"><i class="bi bi-card-text me-1"></i><?= __('no_description') ?></div>
                <?php endif; ?>

                <?php if ($project['status'] === 'rejected' && !empty($project['doctor_note'])): ?>
                    <div class="alert alert-warning mt-3 mb-0">
                        <strong><i class="bi bi-chat-left-text me-2"></i><?= __('doctor_note') ?>:</strong>
                        <p class="mb-0 mt-1"><?= sanitize($project['doctor_note']) ?></p>
                        <?php if (!empty($settings['show_reviewer_name']) && !empty($project['reviewer_name'])): ?>
                            <small class="text-muted mt-1 d-block"><i class="bi bi-person me-1"></i><?= __('reviewed_by') ?>: <?= sanitize($project['reviewer_name']) ?></small>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if ($project['status'] === 'accepted'): ?>
                    <div class="alert alert-success mt-3 mb-0">
                        <i class="bi bi-check-circle me-2"></i><?= __('project_accepted_msg') ?>
                        <?php if (!empty($settings['show_reviewer_name']) && !empty($project['reviewer_name'])): ?>
                            <small class="text-muted mt-1 d-block"><i class="bi bi-person me-1"></i><?= __('reviewed_by') ?>: <?= sanitize($project['reviewer_name']) ?></small>
                        <?php endif; ?>
                    </div>
                <?php elseif ($project['status'] === 'under_review'): ?>
                    <div class="alert alert-info mt-3 mb-0">
                        <i class="bi bi-hourglass-split me-2"></i><?= __('project_submitted') ?>
                    </div>
                <?php endif; ?>

                <?php if ($isLeader && in_array($project['status'], ['draft', 'rejected'])): ?>
                    <div class="text-end mt-3">
                        <button type="button" class="btn btn-outline-primary" onclick="toggleEditMode(true)">
                            <i class="bi bi-pencil-square me-1"></i><?= __('edit_project_info') ?>
                        </button>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Edit mode (leader, draft/rejected only) -->
            <?php if ($isLeader && in_array($project['status'], ['draft', 'rejected'])): ?>
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
            <?php endif; ?>
        </div>
    </div>

    <div class="row">
        <!-- Team Members -->
        <div class="col-lg-<?= ($isLeader && $project['status'] === 'draft') ? '7' : '12' ?>">
            <div class="card shadow mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-people-fill me-2"></i><?= __('team_members') ?> (<?= $memberCount ?>/<?= $maxSize ?>)</h5>
                    <?php if ($allProfilesComplete): ?>
                        <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i><?= __('all_profiles_complete') ?></span>
                    <?php else: ?>
                        <span class="badge bg-warning text-dark"><i class="bi bi-exclamation-triangle me-1"></i><?= __('profiles_incomplete') ?></span>
                    <?php endif; ?>
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
                                    <?php if (($isLeader && $project['status'] === 'draft') || $canTransferLeadership): ?>
                                        <th></th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($members as $i => $m): 
                                    $complete = isProfileComplete($m);
                                    $mPic = $m['profile_picture'] ?? '';
                                    $mPicUrl = $mPic ? secureFileUrl($m['id'], $mPic) : '';
                                ?>
                                    <tr>
                                        <td><?= $i + 1 ?></td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <?php if ($mPicUrl): ?>
                                                    <img src="<?= $mPicUrl ?>" class="profile-picture-sm rounded-circle" alt="">
                                                <?php else: ?>
                                                    <i class="bi bi-person-circle fs-5 text-secondary"></i>
                                                <?php endif; ?>
                                                <?= sanitize($m['name']) ?>
                                            </div>
                                        </td>
                                        <td><code><?= sanitize($m['student_code'] ?? '-') ?></code></td>
                                        <td>
                                            <span class="badge bg-<?= $m['member_role'] === 'leader' ? 'primary' : 'info' ?>">
                                                <?= $m['member_role'] === 'leader' ? __('leader') : __('member') ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($complete): ?>
                                                <span class="text-success"><i class="bi bi-check-circle-fill"></i></span>
                                            <?php else: ?>
                                                <span class="text-warning"><i class="bi bi-exclamation-circle-fill"></i></span>
                                            <?php endif; ?>
                                        </td>
                                        <?php if (($isLeader && $project['status'] === 'draft') || $canTransferLeadership): ?>
                                            <td>
                                                <?php if ($m['member_role'] !== 'leader'): ?>
                                                    <div class="d-flex align-items-center gap-1">
                                                        <?php if ($canTransferLeadership): ?>
                                                            <button class="btn btn-sm btn-outline-primary" onclick="transferLeadership(<?= $m['id'] ?>, '<?= sanitize($m['name']) ?>')" title="<?= __('transfer_leadership') ?>">
                                                                <i class="bi bi-star"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                        <?php if ($isLeader && $project['status'] === 'draft'): ?>
                                                            <button class="btn btn-sm btn-outline-danger" onclick="removeMember(<?= $m['id'] ?>, '<?= sanitize($m['name']) ?>')">
                                                                <i class="bi bi-x-lg"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                                <?php foreach ($pendingInvites as $inv): ?>
                                    <tr class="table-warning">
                                        <td><i class="bi bi-hourglass-split text-warning"></i></td>
                                        <td>
                                            <?php if ($inv['invited_name']): ?>
                                                <?= sanitize($inv['invited_name']) ?>
                                            <?php else: ?>
                                                <span class="text-muted fst-italic"><?= __('invite_link_label') ?></span>
                                            <?php endif; ?>
                                            <span class="badge bg-warning text-dark ms-1"><?= __('pending_status') ?></span>
                                        </td>
                                        <td><code><?= $inv['invited_student_code'] ? sanitize($inv['invited_student_code']) : '-' ?></code></td>
                                        <td>
                                            <span class="badge bg-warning text-dark"><?= __('invited') ?></span>
                                        </td>
                                        <td>—</td>
                                        <?php if (($isLeader && $project['status'] === 'draft') || $canTransferLeadership): ?>
                                            <td>
                                                <?php if ($isLeader && $project['status'] === 'draft'): ?>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-outline-primary" onclick="resendInvitation(<?= $inv['id'] ?>)" title="<?= __('resend_invitation') ?>">
                                                        <i class="bi bi-arrow-repeat"></i>
                                                    </button>
                                                    <button class="btn btn-outline-danger" onclick="cancelInvitation(<?= $inv['id'] ?>)" title="<?= __('cancel_invitation') ?>">
                                                        <i class="bi bi-x-lg"></i>
                                                    </button>
                                                </div>
                                                <?php endif; ?>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Invite Section (leader only, draft) -->
        <?php if ($isLeader && $project['status'] === 'draft'): ?>
            <div class="col-lg-5">
                <div class="card shadow mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-person-plus me-2"></i><?= __('invite_members') ?></h5>
                    </div>
                    <div class="card-body">
                        <?php if ($isFull): ?>
                            <div class="alert alert-info mb-0">
                                <i class="bi bi-check-circle me-2"></i><?= __('project_full') ?>
                            </div>
                        <?php else: ?>
                            <!-- Join Code -->
                            <div class="mb-3">
                                <label class="form-label fw-bold"><i class="bi bi-hash me-1"></i><?= __('join_code') ?></label>
                                <div class="input-group">
                                    <input type="text" class="form-control text-center fw-bold fs-4" 
                                           value="<?= sanitize($project['join_code']) ?>" readonly 
                                           id="joinCodeDisplay" style="letter-spacing: 4px;">
                                    <button class="btn btn-outline-primary" onclick="copyText(document.getElementById('joinCodeDisplay').value, this)">
                                        <i class="bi bi-clipboard"></i>
                                    </button>
                                </div>
                            </div>

                            <hr>

                            <!-- Generate Invite Link -->
                            <div class="mb-3">
                                <label class="form-label fw-bold"><i class="bi bi-link-45deg me-1"></i><?= __('invite_link') ?></label>
                                <div class="d-flex gap-2">
                                    <button class="btn btn-outline-primary flex-fill" onclick="generateLink()">
                                        <i class="bi bi-link me-1"></i><?= __('generate_link') ?>
                                    </button>
                                </div>
                                <div id="linkResult" class="mt-2 d-none">
                                    <div class="input-group input-group-sm">
                                        <input type="text" class="form-control" id="inviteLinkField" readonly>
                                        <button class="btn btn-outline-primary" onclick="copyText(document.getElementById('inviteLinkField').value, this)">
                                            <i class="bi bi-clipboard"></i>
                                        </button>
                                    </div>
                                    <!-- QR Code -->
                                    <div class="text-center mt-2">
                                        <canvas id="qrcodeCanvas" class="border rounded"></canvas>
                                    </div>
                                </div>
                            </div>

                            <hr>

                            <!-- Direct Invite -->
                            <div class="mb-3">
                                <label class="form-label fw-bold"><i class="bi bi-person-plus me-1"></i><?= __('invite_by_search') ?></label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="directInviteSearch" 
                                           placeholder="<?= __('email_or_code_placeholder') ?>">
                                    <button class="btn btn-primary" onclick="sendDirectInvite()">
                                        <i class="bi bi-send"></i>
                                    </button>
                                </div>
                                <div id="directInviteAlert" class="alert d-none mt-2 mb-0 py-1 small"></div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Submit / Leave Actions -->
    <div class="card shadow mb-4">
        <div class="card-body d-flex gap-3 flex-wrap justify-content-center">
            <?php if ($canSubmit || $canResubmit): ?>
                <button class="btn btn-success btn-lg" onclick="submitProject()">
                    <i class="bi bi-send me-2"></i><?= $canResubmit ? __('edit_resubmit') : __('submit_project') ?>
                </button>
            <?php elseif ($isLeader && in_array($project['status'], ['draft', 'rejected'])): ?>
                <button class="btn btn-success btn-lg" disabled title="<?= !$allProfilesComplete ? __('profiles_incomplete') : ($memberCount < $minSize ? __('min_members') . ': ' . $minSize : '') ?>">
                    <i class="bi bi-send me-2"></i><?= __('submit_project') ?>
                </button>
                <small class="text-muted align-self-center">
                    <?php if ($memberCount < $minSize): ?>
                        <?= sprintf(__('team_min_size_msg'), $minSize) ?>
                    <?php elseif (!$allProfilesComplete): ?>
                        <?= __('profiles_incomplete') ?>
                    <?php endif; ?>
                </small>
            <?php endif; ?>

            <?php if ($project['status'] === 'draft'): ?>
                <?php if (!$isLeader): ?>
                    <button class="btn btn-outline-danger" onclick="leaveProject()">
                        <i class="bi bi-box-arrow-right me-2"></i><?= __('leave_project') ?>
                    </button>
                <?php else: ?>
                    <button class="btn btn-outline-danger" onclick="deleteProject()">
                        <i class="bi bi-trash me-2"></i><?= __('delete_project') ?>
                    </button>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Submit Confirmation Modal -->
<?php if ($canSubmit || $canResubmit): ?>
<div class="modal fade" id="submitConfirmModal" tabindex="-1" aria-labelledby="submitConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="submitConfirmModalLabel"><i class="bi bi-exclamation-triangle me-2"></i><?= __('submit_warning_title') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= __('cancel') ?>"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning mb-3">
                    <i class="bi bi-lock me-2"></i><?= __('submit_warning_message') ?>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="submitConfirmCheckbox">
                    <label class="form-check-label" for="submitConfirmCheckbox">
                        <?= __('submit_confirm_checkbox') ?>
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-lg me-1"></i><?= __('cancel') ?>
                </button>
                <button type="button" class="btn btn-success" id="submitConfirmBtn" disabled>
                    <i class="bi bi-send me-1"></i><?= $canResubmit ? __('edit_resubmit') : __('submit_project') ?>
                </button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- QR Code library (lightweight) -->
<script src="https://cdn.jsdelivr.net/npm/qrious@4.0.2/dist/qrious.min.js"></script>
<script src="/assets/js/editor.js"></script>

<script>
const PROJECT_ID = <?= $projectId ?>;

// Initialize editor with paste/drag-drop/resize
initEditor('editDescription', PROJECT_ID, <?= json_encode(__('uploading_file')) ?>);

// Edit project
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

// Generate invite link
async function generateLink() {
    try {
        const res = await fetch('/api/invitations', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ project_id: PROJECT_ID, invite_type: 'link' })
        });
        const data = await res.json();
        if (data.success) {
            const url = location.origin + data.join_url;
            document.getElementById('inviteLinkField').value = url;
            document.getElementById('linkResult').classList.remove('d-none');
            // Generate QR code
            new QRious({
                element: document.getElementById('qrcodeCanvas'),
                value: url,
                size: 180,
                level: 'M'
            });
        } else {
            alert(data.error);
        }
    } catch (err) { alert(err.message); }
}

// Direct invite
async function sendDirectInvite() {
    const search = document.getElementById('directInviteSearch').value.trim();
    const alertEl = document.getElementById('directInviteAlert');
    if (!search) return;
    try {
        const res = await fetch('/api/invitations', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ project_id: PROJECT_ID, invite_type: 'direct', search })
        });
        const data = await res.json();
        if (data.success) {
            alertEl.className = 'alert alert-success mt-2 mb-0 py-1 small';
            alertEl.innerHTML = '<i class="bi bi-check-circle me-1"></i>' + data.message + (data.invited_name ? ' (' + data.invited_name + ')' : '');
            document.getElementById('directInviteSearch').value = '';
        } else {
            throw new Error(data.error);
        }
    } catch (err) {
        alertEl.className = 'alert alert-danger mt-2 mb-0 py-1 small';
        alertEl.textContent = err.message;
    }
    alertEl.classList.remove('d-none');
}

// Copy text
function copyText(text, btn) {
    navigator.clipboard.writeText(text).then(() => {
        const orig = btn.innerHTML;
        btn.innerHTML = '<i class="bi bi-check-lg"></i>';
        setTimeout(() => btn.innerHTML = orig, 1500);
    });
}

// Transfer leadership
async function transferLeadership(memberId, name) {
    const msg = <?= json_encode(__('confirm_transfer_leadership')) ?> + ' ' + name + '?';
    if (!confirm(msg)) return;
    try {
        const res = await fetch('/api/project', {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'set_leader', project_id: PROJECT_ID, student_id: memberId })
        });
        const data = await res.json();
        if (data.success) location.reload();
        else alert(data.error);
    } catch (err) { alert(err.message); }
}

// Remove member
async function removeMember(memberId, name) {
    const msg = <?= json_encode(__('confirm_remove_member')) ?> + ' ' + name + '?';
    if (!confirm(msg)) return;
    try {
        const res = await fetch('/api/project', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ project_id: PROJECT_ID, remove_user_id: memberId })
        });
        const data = await res.json();
        if (data.success) location.reload();
        else alert(data.error);
    } catch (err) { alert(err.message); }
}

// Leave project
async function leaveProject() {
    const msg = <?= json_encode(__('confirm_leave_project')) ?>;
    if (!confirm(msg)) return;
    try {
        const res = await fetch('/api/project', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ project_id: PROJECT_ID })
        });
        const data = await res.json();
        if (data.success) window.location.href = '/student/dashboard';
        else alert(data.error);
    } catch (err) { alert(err.message); }
}

// Delete project (leader)
async function deleteProject() {
    const msg = <?= json_encode(__('confirm_delete_project')) ?>;
    if (!confirm(msg)) return;
    try {
        const res = await fetch('/api/project', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ project_id: PROJECT_ID })
        });
        const data = await res.json();
        if (data.success) window.location.href = '/student/dashboard';
        else alert(data.error);
    } catch (err) { alert(err.message); }
}

// Submit project — show modal with confirmation checkbox
function submitProject() {
    const modal = new bootstrap.Modal(document.getElementById('submitConfirmModal'));
    const checkbox = document.getElementById('submitConfirmCheckbox');
    const btn = document.getElementById('submitConfirmBtn');
    // Reset state each time
    checkbox.checked = false;
    btn.disabled = true;
    modal.show();
}

// Enable/disable confirm button based on checkbox
document.getElementById('submitConfirmCheckbox')?.addEventListener('change', function() {
    document.getElementById('submitConfirmBtn').disabled = !this.checked;
});

// Actual submit when confirmed
document.getElementById('submitConfirmBtn')?.addEventListener('click', async function() {
    const btn = this;
    btn.disabled = true;
    try {
        const res = await fetch('/api/submit', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ project_id: PROJECT_ID })
        });
        const data = await res.json();
        if (data.success) location.reload();
        else {
            alert(data.error || JSON.stringify(data));
            btn.disabled = false;
        }
    } catch (err) {
        alert(err.message);
        btn.disabled = false;
    }
});

// Cancel invitation
async function cancelInvitation(invitationId) {
    const msg = <?= json_encode(__('confirm_cancel_invitation')) ?>;
    if (!confirm(msg)) return;
    try {
        const res = await fetch('/api/invitations', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ invitation_id: invitationId })
        });
        const data = await res.json();
        if (data.success) location.reload();
        else alert(data.error);
    } catch (err) { alert(err.message); }
}

// Resend invitation (refresh token & expiry)
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
</script>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
