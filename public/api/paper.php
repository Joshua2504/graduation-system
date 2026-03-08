<?php
/**
 * API: Student paper submission toggle
 *
 * POST /api/paper.php — Toggle current student's paper_submitted flag for a project
 *                       Students can submit or withdraw their paper (draft/rejected only)
 */
require_once dirname(__DIR__) . '/includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
require_role('student', true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);
$projectId = (int)($input['project_id'] ?? 0);

if ($projectId === 0) {
    jsonResponse(['error' => 'معرف المشروع مطلوب'], 400);
}

$userId = current_user_id();

// Must be a member of the project
if (!isProjectMember($projectId, $userId)) {
    jsonResponse(['error' => 'أنت لست عضواً في هذا المشروع'], 403);
}

$pdo = getDB();
$project = getProject($projectId);

if (!$project) {
    jsonResponse(['error' => 'المشروع غير موجود'], 404);
}

// Only allowed when project is in draft or rejected-resubmittable state
$canModify = $project['status'] === 'draft' ||
             ($project['status'] === 'rejected' && !empty($project['allow_resubmit']));

if (!$canModify) {
    jsonResponse(['error' => 'لا يمكن تعديل حالة الورقة بعد تقديم المشروع'], 400);
}

// Get current state and toggle
$stmt = $pdo->prepare("SELECT paper_submitted FROM project_members WHERE project_id = ? AND user_id = ?");
$stmt->execute([$projectId, $userId]);
$row = $stmt->fetch();

if (!$row) {
    jsonResponse(['error' => 'العضوية غير موجودة'], 404);
}

$newValue = $row['paper_submitted'] ? 0 : 1;
$stmt = $pdo->prepare("UPDATE project_members SET paper_submitted = ? WHERE project_id = ? AND user_id = ?");
$stmt->execute([$newValue, $projectId, $userId]);

// Check if all members have now submitted
$allSubmitted = allMembersPapersSubmitted($projectId);

jsonResponse([
    'success' => true,
    'paper_submitted' => (bool)$newValue,
    'all_submitted' => $allSubmitted,
    'message' => $newValue ? 'تم تأكيد تقديم ورقتك' : 'تم إلغاء تأكيد تقديم ورقتك'
]);
