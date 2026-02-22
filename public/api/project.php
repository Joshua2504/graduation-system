<?php
/**
 * API: Project management
 * 
 * POST   /api/project.php          — Create new project (draft)
 * GET    /api/project.php?id=X     — Get project + students
 * PUT    /api/project.php          — Update project (autosave)
 */
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];

// ─── GET: Fetch project with students ───
if ($method === 'GET') {
    require_login(true);
    
    $projectId = (int)($_GET['id'] ?? 0);
    
    // If no id provided, get current user's project
    if ($projectId === 0) {
        $project = getUserProject(current_user_id());
        if (!$project) {
            jsonResponse(['project' => null]);
        }
        $projectId = $project['id'];
    } else {
        $project = getProject($projectId);
    }
    
    if (!$project) {
        jsonResponse(['error' => 'المشروع غير موجود'], 404);
    }
    
    // Students can only see their own project; doctors can see all
    if (current_role() === 'student' && (int)$project['user_id'] !== current_user_id()) {
        jsonResponse(['error' => 'غير مصرح'], 403);
    }
    
    $students = getProjectStudents($projectId);
    
    jsonResponse([
        'project' => $project,
        'students' => $students,
        'student_count' => count($students)
    ]);
}

// ─── POST: Create new project ───
if ($method === 'POST') {
    require_role('student', true);
    
    $input = json_decode(file_get_contents('php://input'), true);
    $title = trim($input['title'] ?? '');
    $type = trim($input['type'] ?? '');
    $studentNames = $input['student_names'] ?? [];
    
    if (empty($title)) {
        jsonResponse(['error' => 'اسم المشروع مطلوب'], 400);
    }
    
    if (!is_array($studentNames) || count($studentNames) !== 7) {
        jsonResponse(['error' => 'يجب إدخال أسماء 7 طلاب'], 400);
    }
    
    foreach ($studentNames as $name) {
        if (empty(trim($name))) {
            jsonResponse(['error' => 'جميع أسماء الطلاب مطلوبة'], 400);
        }
    }
    
    $pdo = getDB();
    $userId = current_user_id();
    
    // Check if user already has a project
    $existing = getUserProject($userId);
    if ($existing && $existing['status'] !== 'rejected') {
        jsonResponse(['error' => 'لديك مشروع بالفعل', 'project_id' => $existing['id']], 400);
    }
    
    // If rejected, update existing project
    if ($existing && $existing['status'] === 'rejected') {
        $stmt = $pdo->prepare("UPDATE projects SET title = ?, type = ?, status = 'draft', doctor_note = NULL, submission_date = NULL WHERE id = ?");
        $stmt->execute([$title, $type, $existing['id']]);
        
        // Delete existing students (will be re-entered)
        $stmt = $pdo->prepare("DELETE FROM students WHERE project_id = ?");
        $stmt->execute([$existing['id']]);
        
        // Delete existing uploaded files
        $uploadDir = dirname(__DIR__) . '/uploads/project_' . $existing['id'];
        if (is_dir($uploadDir)) {
            array_map('unlink', glob("$uploadDir/*"));
        }
        
        jsonResponse([
            'success' => true,
            'project_id' => $existing['id'],
            'student_names' => $studentNames,
            'message' => 'تم تحديث المشروع'
        ]);
    }
    
    // Create new project
    $stmt = $pdo->prepare("INSERT INTO projects (user_id, title, type, status) VALUES (?, ?, ?, 'draft')");
    $stmt->execute([$userId, $title, $type]);
    $projectId = (int)$pdo->lastInsertId();
    
    jsonResponse([
        'success' => true,
        'project_id' => $projectId,
        'student_names' => $studentNames,
        'message' => 'تم إنشاء المشروع'
    ]);
}

// ─── PUT: Update project (autosave) ───
if ($method === 'PUT') {
    require_role('student', true);
    
    $input = json_decode(file_get_contents('php://input'), true);
    $projectId = (int)($input['project_id'] ?? 0);
    
    if ($projectId === 0) {
        jsonResponse(['error' => 'معرف المشروع مطلوب'], 400);
    }
    
    $pdo = getDB();
    $project = getProject($projectId);
    
    if (!$project || (int)$project['user_id'] !== current_user_id()) {
        jsonResponse(['error' => 'غير مصرح'], 403);
    }
    
    if ($project['status'] !== 'draft') {
        jsonResponse(['error' => 'لا يمكن تعديل المشروع في الحالة الحالية'], 400);
    }
    
    $title = trim($input['title'] ?? $project['title']);
    $type = trim($input['type'] ?? $project['type']);
    
    $stmt = $pdo->prepare("UPDATE projects SET title = ?, type = ? WHERE id = ?");
    $stmt->execute([$title, $type, $projectId]);
    
    jsonResponse(['success' => true, 'message' => 'تم الحفظ']);
}

jsonResponse(['error' => 'Method not allowed'], 405);
