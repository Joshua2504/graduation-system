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
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

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
    require_role('student', true);
    
    $input = json_decode(file_get_contents('php://input'), true);
    $title = trim($input['title'] ?? '');
    $type = trim($input['type'] ?? '');
    
    if (empty($title)) {
        jsonResponse(['error' => 'اسم المشروع مطلوب'], 400);
    }
    
    $userId = current_user_id();
    $joinCode = generateJoinCode();
    
    // Create project
    $stmt = $pdo->prepare("INSERT INTO projects (title, type, join_code, status) VALUES (?, ?, ?, 'draft')");
    $stmt->execute([$title, $type, $joinCode]);
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

// ─── PUT: Update project ───
if ($method === 'PUT') {
    require_role('student', true);
    
    $input = json_decode(file_get_contents('php://input'), true);
    $projectId = (int)($input['project_id'] ?? 0);
    
    if ($projectId === 0) {
        jsonResponse(['error' => 'معرف المشروع مطلوب'], 400);
    }
    
    $userId = current_user_id();
    
    if (!isProjectLeader($projectId, $userId)) {
        jsonResponse(['error' => 'فقط قائد الفريق يمكنه تعديل المشروع'], 403);
    }
    
    $project = getProject($projectId);
    if (!$project || $project['status'] !== 'draft') {
        jsonResponse(['error' => 'لا يمكن تعديل المشروع في الحالة الحالية'], 400);
    }
    
    $title = trim($input['title'] ?? $project['title']);
    $type = trim($input['type'] ?? $project['type']);
    
    $stmt = $pdo->prepare("UPDATE projects SET title = ?, type = ? WHERE id = ?");
    $stmt->execute([$title, $type, $projectId]);
    
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

jsonResponse(['error' => 'Method not allowed'], 405);
