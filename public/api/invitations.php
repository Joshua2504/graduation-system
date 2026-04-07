<?php
/**
 * API: Invitations management
 * 
 * GET    /api/invitations.php                   — List invitations (sent or received)
 * POST   /api/invitations.php                   — Create invitation (token link, direct invite)
 * PUT    /api/invitations.php                   — Accept or decline invitation
 * DELETE /api/invitations.php                   — Cancel/delete invitation
 */
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/mailer.php';

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];
$pdo = getDB();

// ─── GET: List invitations ───
if ($method === 'GET') {
    require_role('student', true);
    $userId = current_user_id();
    $type = $_GET['type'] ?? 'received'; // 'received' or 'sent'
    $projectId = (int)($_GET['project_id'] ?? 0);
    
    if ($type === 'sent' && $projectId > 0) {
        // Sent invitations for a project (leader only)
        if (!isProjectLeader($projectId, $userId)) {
            jsonResponse(['error' => __('unauthorized')], 403);
        }
        $stmt = $pdo->prepare("
            SELECT i.*, u.name AS invited_name, u.email AS invited_email
            FROM invitations i
            LEFT JOIN users u ON u.id = i.invited_user_id
            WHERE i.project_id = ? AND i.invited_by = ?
            ORDER BY i.created_at DESC
        ");
        $stmt->execute([$projectId, $userId]);
    } else {
        // Received invitations (direct invites to this user)
        $stmt = $pdo->prepare("
            SELECT i.*, p.title AS project_title, p.type AS project_type,
                   u.name AS invited_by_name,
                   (SELECT COUNT(*) FROM project_members WHERE project_id = i.project_id) AS member_count
            FROM invitations i
            JOIN projects p ON p.id = i.project_id
            JOIN users u ON u.id = i.invited_by
            WHERE i.invited_user_id = ? AND i.status = 'pending' AND i.expires_at > NOW()
            ORDER BY i.created_at DESC
        ");
        $stmt->execute([$userId]);
    }
    
    jsonResponse(['invitations' => $stmt->fetchAll()]);
}

// ─── POST: Create invitation ───
if ($method === 'POST') {
    require_role('student', true);
    
    $input = json_decode(file_get_contents('php://input'), true);
    $projectId = (int)($input['project_id'] ?? 0);
    $inviteType = $input['invite_type'] ?? 'link'; // 'link' or 'direct'
    $userId = current_user_id();
    
    if ($projectId === 0) {
        jsonResponse(['error' => __('project_id_required')], 400);
    }

    // Must be leader
    if (!isProjectLeader($projectId, $userId)) {
        jsonResponse(['error' => __('leader_only_invite')], 403);
    }

    // Project must be in draft or rejected with resubmit allowed
    $project = getProject($projectId);
    if (!$project || !projectAcceptsInvitations($project)) {
        jsonResponse(['error' => __('project_status_no_invite')], 400);
    }

    // Check member count
    $settings = getSettings();
    $memberCount = countProjectMembers($projectId);
    if ($memberCount >= (int)$settings['max_team_size']) {
        jsonResponse(['error' => __('team_full')], 400);
    }
    
    // Generate invitation token
    $token = bin2hex(random_bytes(32));
    $expiresAt = date('Y-m-d H:i:s', strtotime('+7 days'));
    
    if ($inviteType === 'direct') {
        // Direct invite by email or student_code
        $search = trim($input['search'] ?? '');
        if (empty($search)) {
            jsonResponse(['error' => __('email_or_code_required')], 400);
        }

        // Find student
        $stmt = $pdo->prepare("SELECT id, name, email FROM users WHERE role = 'student' AND (email = ? OR student_code = ?) AND id != ?");
        $stmt->execute([$search, $search, $userId]);
        $invitee = $stmt->fetch();

        if (!$invitee) {
            jsonResponse(['error' => __('student_not_found')], 404);
        }

        // Check if already a member
        if (isProjectMember($projectId, $invitee['id'])) {
            jsonResponse(['error' => __('student_already_in_project')], 400);
        }

        // Check if invitation already pending
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM invitations WHERE project_id = ? AND invited_user_id = ? AND status = 'pending' AND expires_at > NOW()");
        $stmt->execute([$projectId, $invitee['id']]);
        if ((int)$stmt->fetchColumn() > 0) {
            jsonResponse(['error' => __('pending_invitation_exists')], 400);
        }

        $stmt = $pdo->prepare("INSERT INTO invitations (project_id, invited_by, invited_user_id, token, expires_at) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$projectId, $userId, $invitee['id'], $token, $expiresAt]);

        // Send invitation email
        $inviterProfile = getUserProfile($userId);
        $lang = $_GET['lang'] ?? ($_COOKIE['lang'] ?? 'ar');
        sendInvitationEmail(
            $invitee['email'],
            $invitee['name'],
            $inviterProfile['name'] ?? '',
            $project['title'],
            $token,
            $lang
        );

        jsonResponse([
            'success' => true,
            'invitation_id' => (int)$pdo->lastInsertId(),
            'invited_name' => $invitee['name'],
            'message' => __('invitation_sent_success')
        ]);
    } else {
        // Generate token link (no specific user)
        // Use a transaction to atomically replace any existing pending link invitation for this
        // project, preventing duplicate "Invite link" rows in the team members list.
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("DELETE FROM invitations WHERE project_id = ? AND invited_user_id IS NULL AND status = 'pending'");
            $stmt->execute([$projectId]);

            $stmt = $pdo->prepare("INSERT INTO invitations (project_id, invited_by, token, expires_at) VALUES (?, ?, ?, ?)");
            $stmt->execute([$projectId, $userId, $token, $expiresAt]);
            $newId = (int)$pdo->lastInsertId();
            $pdo->commit();
        } catch (\Exception $e) {
            $pdo->rollBack();
            jsonResponse(['error' => __('internal_error')], 500);
        }

        jsonResponse([
            'success' => true,
            'invitation_id' => $newId,
            'token' => $token,
            'join_url' => '/join?token=' . $token,
            'expires_at' => $expiresAt,
            'message' => __('invite_link_created')
        ]);
    }
}

// ─── PUT: Accept or decline invitation ───
if ($method === 'PUT') {
    require_role('student', true);
    
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? ''; // 'accept' or 'decline'
    $userId = current_user_id();
    
    // Accept can come from token, join_code, or invitation_id
    $token = trim($input['token'] ?? '');
    $joinCode = trim($input['join_code'] ?? '');
    $invitationId = (int)($input['invitation_id'] ?? 0);
    
    if (!in_array($action, ['accept', 'decline'])) {
        jsonResponse(['error' => __('invalid_action')], 400);
    }
    
    $invitation = null;
    $projectId = 0;
    
    if (!empty($token)) {
        // Join via token link
        $stmt = $pdo->prepare("SELECT i.*, p.status AS project_status FROM invitations i JOIN projects p ON p.id = i.project_id WHERE i.token = ?");
        $stmt->execute([$token]);
        $invitation = $stmt->fetch();
        
        if (!$invitation) {
            jsonResponse(['error' => __('invalid_invitation_token')], 404);
        }
        if ($invitation['status'] !== 'pending') {
            jsonResponse(['error' => __('invitation_already_used')], 400);
        }
        if (strtotime($invitation['expires_at']) < time()) {
            jsonResponse(['error' => __('invitation_token_expired')], 400);
        }
        if ($invitation['project_status'] !== 'draft') {
            jsonResponse(['error' => __('project_not_accepting')], 400);
        }
        // If it's a direct invite, ensure it's for this user
        if ($invitation['invited_user_id'] && (int)$invitation['invited_user_id'] !== $userId) {
            jsonResponse(['error' => __('invitation_not_for_you')], 403);
        }
        $projectId = (int)$invitation['project_id'];
        
    } elseif (!empty($joinCode)) {
        // Join via project join code
        $stmt = $pdo->prepare("SELECT * FROM projects WHERE join_code = ?");
        $stmt->execute([$joinCode]);
        $project = $stmt->fetch();
        
        if (!$project) {
            jsonResponse(['error' => __('invalid_join_code')], 404);
        }
        if ($project['status'] !== 'draft') {
            jsonResponse(['error' => __('project_not_accepting')], 400);
        }
        $projectId = (int)$project['id'];
        // No invitation record for join code — action must be accept
        if ($action === 'decline') {
            jsonResponse(['error' => __('cannot_decline_join_code')], 400);
        }
        
    } elseif ($invitationId > 0) {
        // Accept/decline by invitation ID (direct invite)
        $stmt = $pdo->prepare("SELECT i.*, p.status AS project_status FROM invitations i JOIN projects p ON p.id = i.project_id WHERE i.id = ? AND i.invited_user_id = ?");
        $stmt->execute([$invitationId, $userId]);
        $invitation = $stmt->fetch();
        
        if (!$invitation) {
            jsonResponse(['error' => __('invitation_not_found')], 404);
        }
        if ($invitation['status'] !== 'pending') {
            jsonResponse(['error' => __('invitation_already_responded')], 400);
        }
        if ($invitation['project_status'] !== 'draft') {
            jsonResponse(['error' => __('project_not_accepting')], 400);
        }
        $projectId = (int)$invitation['project_id'];
    } else {
        jsonResponse(['error' => __('token_or_code_required')], 400);
    }
    
    if ($action === 'decline' && $invitation) {
        $stmt = $pdo->prepare("UPDATE invitations SET status = 'declined' WHERE id = ?");
        $stmt->execute([$invitation['id']]);
        jsonResponse(['success' => true, 'message' => __('invitation_declined')]);
    }
    
    // Accept: add user to project
    if (isProjectMember($projectId, $userId)) {
        jsonResponse(['error' => __('already_in_project')], 400);
    }

    // Students are limited to 1 project
    if (countUserProjects($userId) >= 1) {
        jsonResponse(['error' => __('project_limit_reached_msg')], 403);
    }
    // Locked if they have an accepted project
    if (isStudentProjectLocked($userId)) {
        jsonResponse(['error' => __('project_accepted_locked_msg')], 403);
    }

    $settings = getSettings();
    $memberCount = countProjectMembers($projectId);
    if ($memberCount >= (int)$settings['max_team_size']) {
        jsonResponse(['error' => __('team_full')], 400);
    }

    // Add as member
    $stmt = $pdo->prepare("INSERT INTO project_members (project_id, user_id, role) VALUES (?, ?, 'member')");
    $stmt->execute([$projectId, $userId]);
    
    // Mark invitation as accepted (if one exists)
    if ($invitation) {
        $stmt = $pdo->prepare("UPDATE invitations SET status = 'accepted' WHERE id = ?");
        $stmt->execute([$invitation['id']]);
    }
    
    $project = getProject($projectId);
    
    jsonResponse([
        'success' => true,
        'project_id' => $projectId,
        'project_title' => $project['title'] ?? '',
        'message' => __('joined_project_success')
    ]);
}

// ─── PATCH: Resend invitation (refresh token & expiry) ───
if ($method === 'PATCH') {
    require_login(true);

    $input = json_decode(file_get_contents('php://input'), true);
    $invitationId = (int)($input['invitation_id'] ?? 0);
    $userId = current_user_id();
    $role = current_role();

    if ($invitationId === 0) {
        jsonResponse(['error' => __('invitation_id_required')], 400);
    }

    // Doctors/admins can resend any invitation, students can only resend their own
    if ($role === 'doctor' || $role === 'admin') {
        $stmt = $pdo->prepare("SELECT i.*, p.status AS project_status, p.allow_resubmit FROM invitations i JOIN projects p ON p.id = i.project_id WHERE i.id = ? AND i.status = 'pending'");
        $stmt->execute([$invitationId]);
    } else {
        $stmt = $pdo->prepare("SELECT i.*, p.status AS project_status, p.allow_resubmit FROM invitations i JOIN projects p ON p.id = i.project_id WHERE i.id = ? AND i.invited_by = ? AND i.status = 'pending'");
        $stmt->execute([$invitationId, $userId]);
    }
    $invitation = $stmt->fetch();

    if (!$invitation) {
        jsonResponse(['error' => __('invitation_not_resendable')], 404);
    }
    if (!projectAcceptsInvitations(['status' => $invitation['project_status'], 'allow_resubmit' => $invitation['allow_resubmit']])) {
        jsonResponse(['error' => __('project_not_accepting')], 400);
    }

    // Refresh token and extend expiry by 7 days
    $newToken = bin2hex(random_bytes(32));
    $newExpiry = date('Y-m-d H:i:s', strtotime('+7 days'));

    $stmt = $pdo->prepare("UPDATE invitations SET token = ?, expires_at = ? WHERE id = ?");
    $stmt->execute([$newToken, $newExpiry, $invitationId]);

    // Resend email if it's a direct invite
    if (!empty($invitation['invited_user_id'])) {
        $invitee = getUserProfile((int)$invitation['invited_user_id']);
        $inviter = getUserProfile((int)$invitation['invited_by']);
        $project = getProject((int)$invitation['project_id']);
        $lang = $_GET['lang'] ?? ($_COOKIE['lang'] ?? 'ar');
        if ($invitee && $inviter && $project) {
            sendInvitationEmail(
                $invitee['email'],
                $invitee['name'],
                $inviter['name'],
                $project['title'],
                $newToken,
                $lang
            );
        }
    }

    $response = [
        'success' => true,
        'expires_at' => $newExpiry,
        'message' => __('invitation_resent')
    ];

    // If it's a link invitation (no invited_user_id), return the new join URL
    if (empty($invitation['invited_user_id'])) {
        $response['token'] = $newToken;
        $response['join_url'] = '/join?token=' . $newToken;
    }

    jsonResponse($response);
}

// ─── DELETE: Cancel invitation ───
if ($method === 'DELETE') {
    require_role('student', true);
    
    $input = json_decode(file_get_contents('php://input'), true);
    $invitationId = (int)($input['invitation_id'] ?? 0);
    $userId = current_user_id();
    
    if ($invitationId === 0) {
        jsonResponse(['error' => __('invitation_id_required')], 400);
    }

    $stmt = $pdo->prepare("SELECT * FROM invitations WHERE id = ? AND invited_by = ? AND status = 'pending'");
    $stmt->execute([$invitationId, $userId]);
    $invitation = $stmt->fetch();
    
    if (!$invitation) {
        jsonResponse(['error' => __('invitation_not_cancellable')], 404);
    }
    
    $stmt = $pdo->prepare("DELETE FROM invitations WHERE id = ?");
    $stmt->execute([$invitationId]);
    
    jsonResponse(['success' => true, 'message' => __('invitation_cancelled')]);
}

jsonResponse(['error' => 'Method not allowed'], 405);
