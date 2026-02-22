<?php
/**
 * API: Invitations management
 * 
 * GET    /api/invitations.php                   — List invitations (sent or received)
 * POST   /api/invitations.php                   — Create invitation (token link, direct invite)
 * PUT    /api/invitations.php                   — Accept or decline invitation
 * DELETE /api/invitations.php                   — Cancel/delete invitation
 */
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';
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
            jsonResponse(['error' => 'غير مصرح'], 403);
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
        jsonResponse(['error' => 'معرف المشروع مطلوب'], 400);
    }
    
    // Must be leader
    if (!isProjectLeader($projectId, $userId)) {
        jsonResponse(['error' => 'فقط قائد الفريق يمكنه إرسال الدعوات'], 403);
    }
    
    // Project must be in draft
    $project = getProject($projectId);
    if (!$project || $project['status'] !== 'draft') {
        jsonResponse(['error' => 'لا يمكن إرسال دعوات للمشروع في حالته الحالية'], 400);
    }
    
    // Check member count
    $settings = getSettings();
    $memberCount = countProjectMembers($projectId);
    if ($memberCount >= (int)$settings['max_team_size']) {
        jsonResponse(['error' => 'الفريق مكتمل العدد'], 400);
    }
    
    // Generate invitation token
    $token = bin2hex(random_bytes(32));
    $expiresAt = date('Y-m-d H:i:s', strtotime('+7 days'));
    
    if ($inviteType === 'direct') {
        // Direct invite by email or student_code
        $search = trim($input['search'] ?? '');
        if (empty($search)) {
            jsonResponse(['error' => 'البريد الإلكتروني أو كود الطالب مطلوب'], 400);
        }
        
        // Find student
        $stmt = $pdo->prepare("SELECT id, name, email FROM users WHERE role = 'student' AND (email = ? OR student_code = ?) AND id != ?");
        $stmt->execute([$search, $search, $userId]);
        $invitee = $stmt->fetch();
        
        if (!$invitee) {
            jsonResponse(['error' => 'الطالب غير موجود'], 404);
        }
        
        // Check if already a member
        if (isProjectMember($projectId, $invitee['id'])) {
            jsonResponse(['error' => 'الطالب عضو بالفعل في هذا المشروع'], 400);
        }
        
        // Check if invitation already pending
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM invitations WHERE project_id = ? AND invited_user_id = ? AND status = 'pending' AND expires_at > NOW()");
        $stmt->execute([$projectId, $invitee['id']]);
        if ((int)$stmt->fetchColumn() > 0) {
            jsonResponse(['error' => 'يوجد دعوة معلقة لهذا الطالب بالفعل'], 400);
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
            'message' => 'تم إرسال الدعوة بنجاح'
        ]);
    } else {
        // Generate token link (no specific user)
        $stmt = $pdo->prepare("INSERT INTO invitations (project_id, invited_by, token, expires_at) VALUES (?, ?, ?, ?)");
        $stmt->execute([$projectId, $userId, $token, $expiresAt]);
        
        jsonResponse([
            'success' => true,
            'invitation_id' => (int)$pdo->lastInsertId(),
            'token' => $token,
            'join_url' => '/join.php?token=' . $token,
            'expires_at' => $expiresAt,
            'message' => 'تم إنشاء رابط الدعوة'
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
        jsonResponse(['error' => 'الإجراء غير صالح'], 400);
    }
    
    $invitation = null;
    $projectId = 0;
    
    if (!empty($token)) {
        // Join via token link
        $stmt = $pdo->prepare("SELECT i.*, p.status AS project_status FROM invitations i JOIN projects p ON p.id = i.project_id WHERE i.token = ?");
        $stmt->execute([$token]);
        $invitation = $stmt->fetch();
        
        if (!$invitation) {
            jsonResponse(['error' => 'رابط الدعوة غير صالح'], 404);
        }
        if ($invitation['status'] !== 'pending') {
            jsonResponse(['error' => 'هذه الدعوة تم استخدامها بالفعل'], 400);
        }
        if (strtotime($invitation['expires_at']) < time()) {
            jsonResponse(['error' => 'رابط الدعوة منتهي الصلاحية'], 400);
        }
        if ($invitation['project_status'] !== 'draft') {
            jsonResponse(['error' => 'المشروع لم يعد يقبل أعضاء جدد'], 400);
        }
        // If it's a direct invite, ensure it's for this user
        if ($invitation['invited_user_id'] && (int)$invitation['invited_user_id'] !== $userId) {
            jsonResponse(['error' => 'هذه الدعوة ليست موجهة لك'], 403);
        }
        $projectId = (int)$invitation['project_id'];
        
    } elseif (!empty($joinCode)) {
        // Join via project join code
        $stmt = $pdo->prepare("SELECT * FROM projects WHERE join_code = ?");
        $stmt->execute([$joinCode]);
        $project = $stmt->fetch();
        
        if (!$project) {
            jsonResponse(['error' => 'كود الانضمام غير صالح'], 404);
        }
        if ($project['status'] !== 'draft') {
            jsonResponse(['error' => 'المشروع لم يعد يقبل أعضاء جدد'], 400);
        }
        $projectId = (int)$project['id'];
        // No invitation record for join code — action must be accept
        if ($action === 'decline') {
            jsonResponse(['error' => 'لا يمكن رفض الانضمام بالكود'], 400);
        }
        
    } elseif ($invitationId > 0) {
        // Accept/decline by invitation ID (direct invite)
        $stmt = $pdo->prepare("SELECT i.*, p.status AS project_status FROM invitations i JOIN projects p ON p.id = i.project_id WHERE i.id = ? AND i.invited_user_id = ?");
        $stmt->execute([$invitationId, $userId]);
        $invitation = $stmt->fetch();
        
        if (!$invitation) {
            jsonResponse(['error' => 'الدعوة غير موجودة'], 404);
        }
        if ($invitation['status'] !== 'pending') {
            jsonResponse(['error' => 'هذه الدعوة تم الرد عليها بالفعل'], 400);
        }
        if ($invitation['project_status'] !== 'draft') {
            jsonResponse(['error' => 'المشروع لم يعد يقبل أعضاء جدد'], 400);
        }
        $projectId = (int)$invitation['project_id'];
    } else {
        jsonResponse(['error' => 'يجب تحديد رمز الدعوة أو كود الانضمام'], 400);
    }
    
    if ($action === 'decline' && $invitation) {
        $stmt = $pdo->prepare("UPDATE invitations SET status = 'declined' WHERE id = ?");
        $stmt->execute([$invitation['id']]);
        jsonResponse(['success' => true, 'message' => 'تم رفض الدعوة']);
    }
    
    // Accept: add user to project
    if (isProjectMember($projectId, $userId)) {
        jsonResponse(['error' => 'أنت عضو بالفعل في هذا المشروع'], 400);
    }
    
    $settings = getSettings();
    $memberCount = countProjectMembers($projectId);
    if ($memberCount >= (int)$settings['max_team_size']) {
        jsonResponse(['error' => 'الفريق مكتمل العدد'], 400);
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
        'message' => 'تم الانضمام للمشروع بنجاح'
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
        jsonResponse(['error' => 'معرف الدعوة مطلوب'], 400);
    }

    // Doctors can resend any invitation, students can only resend their own
    if ($role === 'doctor') {
        $stmt = $pdo->prepare("SELECT i.*, p.status AS project_status FROM invitations i JOIN projects p ON p.id = i.project_id WHERE i.id = ? AND i.status = 'pending'");
        $stmt->execute([$invitationId]);
    } else {
        $stmt = $pdo->prepare("SELECT i.*, p.status AS project_status FROM invitations i JOIN projects p ON p.id = i.project_id WHERE i.id = ? AND i.invited_by = ? AND i.status = 'pending'");
        $stmt->execute([$invitationId, $userId]);
    }
    $invitation = $stmt->fetch();

    if (!$invitation) {
        jsonResponse(['error' => 'الدعوة غير موجودة أو لا يمكن إعادة إرسالها'], 404);
    }
    if ($invitation['project_status'] !== 'draft') {
        jsonResponse(['error' => 'المشروع لم يعد يقبل أعضاء جدد'], 400);
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
        'message' => 'تم إعادة إرسال الدعوة بنجاح'
    ];

    // If it's a link invitation (no invited_user_id), return the new join URL
    if (empty($invitation['invited_user_id'])) {
        $response['token'] = $newToken;
        $response['join_url'] = '/join.php?token=' . $newToken;
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
        jsonResponse(['error' => 'معرف الدعوة مطلوب'], 400);
    }
    
    $stmt = $pdo->prepare("SELECT * FROM invitations WHERE id = ? AND invited_by = ? AND status = 'pending'");
    $stmt->execute([$invitationId, $userId]);
    $invitation = $stmt->fetch();
    
    if (!$invitation) {
        jsonResponse(['error' => 'الدعوة غير موجودة أو لا يمكن إلغاؤها'], 404);
    }
    
    $stmt = $pdo->prepare("DELETE FROM invitations WHERE id = ?");
    $stmt->execute([$invitationId]);
    
    jsonResponse(['success' => true, 'message' => 'تم إلغاء الدعوة']);
}

jsonResponse(['error' => 'Method not allowed'], 405);
