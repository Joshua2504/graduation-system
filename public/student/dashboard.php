<?php
/**
 * Student Dashboard — projects list, invitations, join code
 */
require_once dirname(__DIR__) . '/includes/bootstrap.php';

require_role('student');

$userId = current_user_id();
$user = getUserProfile($userId);
$projects = getUserProjects($userId);
$pendingInvitations = getPendingInvitations($userId);
$profileComplete = isProfileComplete($user);
$isAr = getLang() === 'ar';
$settings = getSettings();

$statusLabels = getStatusLabels();
$statusColors = getStatusColors();

$pageTitle = __('dashboard');
require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/includes/navbar.php';
?>

<div class="container">
    <!-- Profile completion warning -->
    <?php if (!$profileComplete): ?>
        <div class="alert alert-warning d-flex align-items-center">
            <i class="bi bi-exclamation-triangle-fill fs-4 me-3"></i>
            <div>
                <strong><?= __('profile_incomplete') ?></strong><br>
                <small><?= __('profile_incomplete_msg') ?></small>
            </div>
            <a href="/student/profile" class="btn btn-warning btn-sm ms-auto">
                <i class="bi bi-person-circle me-1"></i><?= __('complete_profile') ?>
            </a>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Left Column: Projects -->
        <div class="col-lg-8 mb-4">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h4 class="mb-0"><i class="bi bi-folder2-open me-2"></i><?= __('my_projects') ?></h4>
                <?php if (!empty($settings['student_project_creation'])): ?>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createProjectModal">
                        <i class="bi bi-plus-circle me-1"></i><?= __('create_project') ?>
                    </button>
                <?php endif; ?>
            </div>

            <?php if (empty($projects)): ?>
                <div class="card shadow">
                    <div class="card-body text-center py-5">
                        <i class="bi bi-folder-plus text-muted" style="font-size: 4rem; opacity: 0.3;"></i>
                        <h5 class="text-muted mt-3"><?= __('no_projects_yet') ?></h5>
                        <p class="text-muted mb-0">
                            <?= __('no_projects_msg') ?>
                        </p>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($projects as $p): ?>
                    <div class="card shadow-sm mb-3">
                        <div class="card-body">
                            <div class="d-flex align-items-start justify-content-between">
                                <div>
                                    <h5 class="mb-1"><?= sanitize($p['title']) ?></h5>
                                    <p class="text-muted mb-2 small">
                                        <?php if ($p['type']): ?>
                                            <i class="bi bi-tag me-1"></i><?= sanitize($p['type']) ?> &middot;
                                        <?php endif; ?>
                                        <i class="bi bi-people me-1"></i><?= $p['member_count'] ?>/<?= $settings['max_team_size'] ?>
                                        <?= __('team_members') ?>
                                        &middot;
                                        <span class="badge bg-<?= $p['my_role'] === 'leader' ? 'primary' : 'info' ?>">
                                            <?= $p['my_role'] === 'leader' ? __('leader') : __('member') ?>
                                        </span>
                                    </p>
                                    <?php if ($p['status'] === 'accepted' && $p['group_number']): ?>
                                        <span class="badge bg-success fs-6">
                                            <?= __('group_number') ?>: <?= $p['group_number'] ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($p['status'] === 'rejected' && !empty($p['doctor_note'])): ?>
                                        <div class="alert alert-secondary py-1 px-2 mt-1 small mb-0">
                                            <i class="bi bi-chat-left-text me-1"></i><?= sanitize($p['doctor_note']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-<?= $statusColors[$p['status']] ?> mb-2">
                                        <?= $statusLabels[$p['status']] ?>
                                    </span>
                                    <br>
                                    <a href="/student/project?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye me-1"></i><?= __('view_project') ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Right Column: Invites & Join -->
        <div class="col-lg-4">
            <!-- Join by Code -->
            <div class="card shadow-sm mb-3">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-link-45deg me-1"></i><?= __('join_project') ?></h6>
                </div>
                <div class="card-body">
                    <div class="input-group">
                        <input type="text" class="form-control" id="joinCodeInput" 
                               placeholder="<?= __('enter_join_code') ?>" maxlength="8"
                               style="text-transform: uppercase; letter-spacing: 2px; font-weight: bold;">
                        <button class="btn btn-primary" onclick="joinByCode()">
                            <i class="bi bi-box-arrow-in-right"></i>
                        </button>
                    </div>
                    <div id="joinAlert" class="alert d-none mt-2 mb-0 py-1 small"></div>
                </div>
            </div>

            <!-- Pending Invitations -->
            <?php if (!empty($pendingInvitations)): ?>
                <div class="card shadow-sm mb-3 border-warning">
                    <div class="card-header bg-warning text-dark">
                        <h6 class="mb-0"><i class="bi bi-envelope-open me-1"></i><?= __('pending_invitations') ?> (<?= count($pendingInvitations) ?>)</h6>
                    </div>
                    <div class="card-body p-0">
                        <?php foreach ($pendingInvitations as $inv): ?>
                            <div class="p-3 border-bottom" id="inv-<?= $inv['id'] ?>">
                                <strong><?= sanitize($inv['project_title']) ?></strong>
                                <br>
                                <small class="text-muted">
                                    <?= __('from') ?>: <?= sanitize($inv['invited_by_name']) ?>
                                    &middot; <?= $inv['member_count'] ?> <?= __('team_members') ?>
                                </small>
                                <div class="mt-2 d-flex gap-2">
                                    <button class="btn btn-sm btn-success flex-fill" onclick="respondInvite(<?= $inv['id'] ?>, 'accept')">
                                        <i class="bi bi-check-lg me-1"></i><?= __('accept_invite') ?>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger flex-fill" onclick="respondInvite(<?= $inv['id'] ?>, 'decline')">
                                        <i class="bi bi-x-lg me-1"></i><?= __('decline_invite') ?>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
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
                <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i><?= __('create_project') ?></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="createProjectForm">
                    <div class="mb-3">
                        <label class="form-label fw-bold"><?= __('project_name') ?> <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="newTitle" required maxlength="500">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold"><?= __('project_type') ?></label>
                        <input type="text" class="form-control" id="newType" maxlength="255"
                               placeholder="<?= __('project_type_placeholder') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold"><?= __('project_description') ?></label>
                        <div class="simple-editor-toolbar">
                            <button type="button" class="btn" onclick="editorCmd('bold', 'newDescription')" title="<?= __('bold') ?>"><i class="bi bi-type-bold"></i></button>
                            <button type="button" class="btn" onclick="editorCmd('italic', 'newDescription')" title="<?= __('italic') ?>"><i class="bi bi-type-italic"></i></button>
                            <button type="button" class="btn" onclick="editorCmd('underline', 'newDescription')" title="<?= __('underline_text') ?>"><i class="bi bi-type-underline"></i></button>
                            <div class="vr mx-1 opacity-25"></div>
                            <button type="button" class="btn" onclick="editorCmd('insertUnorderedList', 'newDescription')" title="<?= __('bullet_list') ?>"><i class="bi bi-list-ul"></i></button>
                            <button type="button" class="btn" onclick="editorCmd('insertOrderedList', 'newDescription')" title="<?= __('numbered_list') ?>"><i class="bi bi-list-ol"></i></button>
                            <div class="vr mx-1 opacity-25"></div>
                            <button type="button" class="btn" onclick="editorInsertLink('newDescription')" title="<?= __('insert_link') ?>"><i class="bi bi-link-45deg"></i></button>
                        </div>
                        <div id="newDescription" class="simple-editor-content" contenteditable="true" data-placeholder="<?= __('description_placeholder') ?>"></div>
                    </div>
                    <div id="createAlert" class="alert d-none"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= __('cancel') ?></button>
                <button type="button" class="btn btn-primary" onclick="createProject()">
                    <i class="bi bi-plus-circle me-1"></i><?= __('create_project') ?>
                </button>
            </div>
        </div>
    </div>
</div>

<script src="/assets/js/editor.js"></script>
<script>
async function createProject() {
    const title = document.getElementById('newTitle').value.trim();
    const type = document.getElementById('newType').value.trim();
    const descEl = document.getElementById('newDescription');
    const description = descEl ? descEl.innerHTML.trim() : '';
    const alertEl = document.getElementById('createAlert');
    alertEl.classList.add('d-none');

    if (!title) return;
    
    try {
        const res = await fetch('/api/project', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ title, type, description })
        });
        const data = await res.json();
        if (data.success) {
            window.location.href = '/student/project?id=' + data.project_id;
        } else {
            throw new Error(data.error);
        }
    } catch (err) {
        alertEl.className = 'alert alert-danger';
        alertEl.textContent = err.message;
        alertEl.classList.remove('d-none');
    }
}

async function joinByCode() {
    const code = document.getElementById('joinCodeInput').value.trim().toUpperCase();
    const alertEl = document.getElementById('joinAlert');
    if (!code) return;
    
    try {
        const res = await fetch('/api/invitations', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'accept', join_code: code })
        });
        const data = await res.json();
        if (data.success) {
            alertEl.className = 'alert alert-success mt-2 mb-0 py-1 small';
            alertEl.textContent = data.message;
            alertEl.classList.remove('d-none');
            setTimeout(() => location.reload(), 1000);
        } else {
            throw new Error(data.error);
        }
    } catch (err) {
        alertEl.className = 'alert alert-danger mt-2 mb-0 py-1 small';
        alertEl.textContent = err.message;
        alertEl.classList.remove('d-none');
    }
}

async function respondInvite(invitationId, action) {
    try {
        const res = await fetch('/api/invitations', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ invitation_id: invitationId, action })
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
</script>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
