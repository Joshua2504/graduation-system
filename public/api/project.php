<?php
/**
 * API: Project management
 * 
 * POST   /api/project.php          — Create new project
 * GET    /api/project.php?id=X     — Get project details + members
 * GET    /api/project.php          — Get current user's projects
 * PUT    /api/project.php          — Update project (title/type, leader only)
 * DELETE /api/project.php          — Leave project / remove member
 */
require_once dirname(__DIR__) . '/includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];
$pdo = getDB();

// ─── GET: Fetch project(s) ───
if ($method === 'GET') {
    require_login(true);
    
    $projectId = (int)($_GET['id'] ?? 0);
    
    if ($projectId > 0) {
        // Fetch single project
        $project = getProject($projectId);
        if (!$project) {
            jsonResponse(['error' => 'المشروع غير موجود'], 404);
        }
        
        // Students can only see projects they belong to; doctors can see all
        if (current_role() === 'student' && !isProjectMember($projectId, current_user_id())) {
            jsonResponse(['error' => 'غير مصرح'], 403);
        }
        
        $members = getProjectMembers($projectId);
        // Strip sensitive data from members
        foreach ($members as &$m) {
            unset($m['password'], $m['verification_token'], $m['token_expires_at']);
        }
        
        $settings = getSettings();
        
        jsonResponse([
            'project' => $project,
            'members' => $members,
            'member_count' => count($members),
            'max_members' => (int)$settings['max_team_size'],
            'min_members' => (int)$settings['min_team_size']
        ]);
    } else {
        // Fetch all user's projects
        $projects = getUserProjects(current_user_id());
        jsonResponse(['projects' => $projects]);
    }
}

// ─── POST: Create new project ───
if ($method === 'POST') {
    require_login(true);
    $role = current_role();
    
    $input = json_decode(file_get_contents('php://input'), true);
    $title = trim($input['title'] ?? '');
    $type = trim($input['type'] ?? '');
    $description = sanitizeHtml($input['description'] ?? '');
    
    if (empty($title)) {
        jsonResponse(['error' => 'اسم المشروع مطلوب'], 400);
    }
    
    $joinCode = generateJoinCode();
    
    if ($role === 'doctor' || $role === 'admin') {
        // Doctor/admin creates project — optionally with assigned students
        $stmt = $pdo->prepare("INSERT INTO projects (title, type, description, join_code, status) VALUES (?, ?, ?, ?, 'draft')");
        $stmt->execute([$title, $type, $description ?: null, $joinCode]);
        $projectId = (int)$pdo->lastInsertId();
        
        // Assign students if provided
        $students = $input['students'] ?? [];
        $leaderId = (int)($input['leader_id'] ?? 0);
        
        foreach ($students as $sid) {
            $sid = (int)$sid;
            if ($sid === 0) continue;
            $memberRole = ($sid === $leaderId) ? 'leader' : 'member';
            $stmt = $pdo->prepare("INSERT IGNORE INTO project_members (project_id, user_id, role) VALUES (?, ?, ?)");
            $stmt->execute([$projectId, $sid, $memberRole]);
        }
        
        jsonResponse([
            'success' => true,
            'project_id' => $projectId,
            'join_code' => $joinCode,
            'message' => 'تم إنشاء المشروع'
        ]);
    } else {
        // Student creates project
        $settings = getSettings();
        if (empty($settings['student_project_creation'])) {
            jsonResponse(['error' => 'إنشاء المشاريع بواسطة الطلاب معطل حالياً'], 403);
        }
        $userId = current_user_id();
        
        $stmt = $pdo->prepare("INSERT INTO projects (title, type, description, join_code, status) VALUES (?, ?, ?, ?, 'draft')");
        $stmt->execute([$title, $type, $description ?: null, $joinCode]);
        $projectId = (int)$pdo->lastInsertId();
        
        // Add creator as leader
        $stmt = $pdo->prepare("INSERT INTO project_members (project_id, user_id, role) VALUES (?, ?, 'leader')");
        $stmt->execute([$projectId, $userId]);
        
        jsonResponse([
            'success' => true,
            'project_id' => $projectId,
            'join_code' => $joinCode,
            'message' => 'تم إنشاء المشروع'
        ]);
    }
}

// ─── PUT: Update project ───
if ($method === 'PUT') {
    require_login(true);
    $role = current_role();
    
    $input = json_decode(file_get_contents('php://input'), true);
    $projectId = (int)($input['project_id'] ?? 0);
    
    if ($projectId === 0) {
        jsonResponse(['error' => 'معرف المشروع مطلوب'], 400);
    }
    
    $project = getProject($projectId);
    if (!$project) {
        jsonResponse(['error' => 'المشروع غير موجود'], 404);
    }
    
    if ($role === 'student') {
        $userId = current_user_id();
        if (!isProjectLeader($projectId, $userId)) {
            jsonResponse(['error' => 'فقط قائد الفريق يمكنه تعديل المشروع'], 403);
        }
        if (!in_array($project['status'], ['draft', 'rejected'])) {
            jsonResponse(['error' => 'لا يمكن تعديل المشروع في الحالة الحالية'], 400);
        }
        // Block edits on rejected projects if resubmission not allowed
        if ($project['status'] === 'rejected' && empty($project['allow_resubmit'])) {
            jsonResponse(['error' => __('resubmit_not_allowed')], 403);
        }
    }
    // Doctors can update any project
    
    $title = trim($input['title'] ?? $project['title']);
    $type = trim($input['type'] ?? $project['type']);
    $description = array_key_exists('description', $input) ? sanitizeHtml($input['description'] ?? '') : $project['description'];
    
    $stmt = $pdo->prepare("UPDATE projects SET title = ?, type = ?, description = ? WHERE id = ?");
    $stmt->execute([$title, $type, $description ?: null, $projectId]);
    
    jsonResponse(['success' => true, 'message' => 'تم تحديث المشروع']);
}

// ─── DELETE: Leave project or remove member ───
if ($method === 'DELETE') {
    require_role('student', true);
    
    $input = json_decode(file_get_contents('php://input'), true);
    $projectId = (int)($input['project_id'] ?? 0);
    $removeUserId = (int)($input['remove_user_id'] ?? 0);
    $userId = current_user_id();
    
    if ($projectId === 0) {
        jsonResponse(['error' => 'معرف المشروع مطلوب'], 400);
    }
    
    $project = getProject($projectId);
    if (!$project || $project['status'] !== 'draft') {
        jsonResponse(['error' => 'لا يمكن تعديل الفريق في حالة المشروع الحالية'], 400);
    }
    
    if ($removeUserId > 0 && $removeUserId !== $userId) {
        // Leader removing a member
        if (!isProjectLeader($projectId, $userId)) {
            jsonResponse(['error' => 'فقط قائد الفريق يمكنه إزالة الأعضاء'], 403);
        }
        
        // Can't remove yourself as leader this way
        $stmt = $pdo->prepare("SELECT role FROM project_members WHERE project_id = ? AND user_id = ?");
        $stmt->execute([$projectId, $removeUserId]);
        $member = $stmt->fetch();
        
        if (!$member) {
            jsonResponse(['error' => 'العضو غير موجود في المشروع'], 404);
        }
        if ($member['role'] === 'leader') {
            jsonResponse(['error' => 'لا يمكن إزالة قائد الفريق'], 400);
        }
        
        $stmt = $pdo->prepare("DELETE FROM project_members WHERE project_id = ? AND user_id = ?");
        $stmt->execute([$projectId, $removeUserId]);
        
        jsonResponse(['success' => true, 'message' => 'تم إزالة العضو']);
    } else {
        // Self leave
        if (isProjectLeader($projectId, $userId)) {
            // Leader leaving = delete project entirely
            $stmt = $pdo->prepare("DELETE FROM projects WHERE id = ?");
            $stmt->execute([$projectId]);
            jsonResponse(['success' => true, 'message' => 'تم حذف المشروع', 'project_deleted' => true]);
        }
        
        $stmt = $pdo->prepare("DELETE FROM project_members WHERE project_id = ? AND user_id = ?");
        $stmt->execute([$projectId, $userId]);
        
        jsonResponse(['success' => true, 'message' => 'تم مغادرة المشروع']);
    }
}

// ─── PATCH: Doctor or team leader manages project members ───
if ($method === 'PATCH') {
    require_login(true);
    
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    $projectId = (int)($input['project_id'] ?? 0);
    
    if ($projectId === 0) {
        jsonResponse(['error' => 'معرف المشروع مطلوب'], 400);
    }
    
    $project = getProject($projectId);
    if (!$project) {
        jsonResponse(['error' => 'المشروع غير موجود'], 404);
    }
    
    $settings = getSettings();
    $maxSize = (int)$settings['max_team_size'];
    $isDoctor = current_role() === 'doctor' || current_role() === 'admin';
    $isLeaderOfProject = !$isDoctor && isProjectLeader($projectId, current_user_id());
    
    // Team leader can only use set_leader action (when setting is enabled)
    if (!$isDoctor) {
        if ($action !== 'set_leader') {
            jsonResponse(['error' => 'غير مصرح'], 403);
        }
        if (!$isLeaderOfProject) {
            jsonResponse(['error' => 'فقط قائد الفريق يمكنه نقل القيادة'], 403);
        }
        if (empty($settings['leader_transfer'])) {
            jsonResponse(['error' => __('leader_transfer_disabled')], 403);
        }
    }
    
    // Search students
    if ($action === 'search_students') {
        $query = trim($input['query'] ?? '');
        if (strlen($query) < 1) {
            jsonResponse(['students' => []]);
        }
        $like = "%$query%";
        $stmt = $pdo->prepare("
            SELECT u.id, u.name, u.email, u.student_code 
            FROM users u 
            WHERE u.role = 'student' AND u.account_enabled = 1
              AND (u.name LIKE ? OR u.email LIKE ? OR u.student_code LIKE ?)
              AND u.id NOT IN (SELECT user_id FROM project_members WHERE project_id = ?)
            ORDER BY u.name ASC LIMIT 10
        ");
        $stmt->execute([$like, $like, $like, $projectId]);
        jsonResponse(['students' => $stmt->fetchAll()]);
    }
    
    // Add student
    if ($action === 'add_student') {
        $studentId = (int)($input['student_id'] ?? 0);
        $asLeader = !empty($input['as_leader']);
        
        if ($studentId === 0) {
            jsonResponse(['error' => 'معرف الطالب مطلوب'], 400);
        }
        
        // Check if already a member
        if (isProjectMember($projectId, $studentId)) {
            jsonResponse(['error' => 'الطالب عضو بالفعل في هذا المشروع'], 400);
        }
        
        // Check team size
        $memberCount = countProjectMembers($projectId);
        if ($memberCount >= $maxSize) {
            jsonResponse(['error' => 'الفريق مكتمل العدد'], 400);
        }
        
        $role = 'member';
        if ($asLeader) {
            // Demote current leader if exists
            $pdo->prepare("UPDATE project_members SET role = 'member' WHERE project_id = ? AND role = 'leader'")->execute([$projectId]);
            $role = 'leader';
        }
        
        $stmt = $pdo->prepare("INSERT INTO project_members (project_id, user_id, role) VALUES (?, ?, ?)");
        $stmt->execute([$projectId, $studentId, $role]);
        
        jsonResponse(['success' => true, 'message' => 'تم إضافة الطالب بنجاح']);
    }
    
    // Remove student
    if ($action === 'remove_student') {
        $studentId = (int)($input['student_id'] ?? 0);
        
        if ($studentId === 0) {
            jsonResponse(['error' => 'معرف الطالب مطلوب'], 400);
        }
        
        $stmt = $pdo->prepare("DELETE FROM project_members WHERE project_id = ? AND user_id = ?");
        $stmt->execute([$projectId, $studentId]);
        
        jsonResponse(['success' => true, 'message' => 'تم إزالة الطالب']);
    }
    
    // Set leader
    if ($action === 'set_leader') {
        $studentId = (int)($input['student_id'] ?? 0);
        
        if ($studentId === 0) {
            jsonResponse(['error' => 'معرف الطالب مطلوب'], 400);
        }
        
        // Demote current leader
        $pdo->prepare("UPDATE project_members SET role = 'member' WHERE project_id = ? AND role = 'leader'")->execute([$projectId]);
        // Promote new leader
        $pdo->prepare("UPDATE project_members SET role = 'leader' WHERE project_id = ? AND user_id = ?")->execute([$projectId, $studentId]);
        
        jsonResponse(['success' => true, 'message' => 'تم تغيير قائد الفريق']);
    }
    
    // Toggle allow_resubmit on rejected projects (doctor only)
    if ($action === 'toggle_resubmit') {
        if (!$isDoctor) {
            jsonResponse(['error' => 'غير مصرح'], 403);
        }
        if ($project['status'] !== 'rejected') {
            jsonResponse(['error' => 'المشروع ليس مرفوضاً'], 400);
        }
        $newValue = !empty($input['allow_resubmit']) ? 1 : 0;
        $stmt = $pdo->prepare("UPDATE projects SET allow_resubmit = ? WHERE id = ?");
        $stmt->execute([$newValue, $projectId]);
        
        jsonResponse(['success' => true, 'allow_resubmit' => $newValue, 'message' => __($newValue ? 'allow_resubmit_enabled' : 'allow_resubmit_disabled')]);
    }
    
    jsonResponse(['error' => 'إجراء غير معروف'], 400);
}

jsonResponse(['error' => 'Method not allowed'], 405);
