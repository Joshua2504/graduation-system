<?php
/**
 * API: Doctor reviews a project (accept / reject)
 * 
 * POST /api/review.php
 * Body: { project_id, action: "accept"|"reject", doctor_note }
 */
require_once dirname(__DIR__) . '/includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
require_role('doctor', true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);
$projectId  = (int)($input['project_id'] ?? 0);
$action     = trim($input['action'] ?? '');
$doctorNote = trim($input['doctor_note'] ?? '');

if ($projectId === 0) {
    jsonResponse(['error' => 'معرف المشروع مطلوب'], 400);
}

if (!in_array($action, ['accept', 'reject'])) {
    jsonResponse(['error' => 'الإجراء غير صالح'], 400);
}

$pdo = getDB();
$project = getProject($projectId);

if (!$project) {
    jsonResponse(['error' => 'المشروع غير موجود'], 404);
}

if ($project['status'] !== 'under_review') {
    jsonResponse(['error' => 'المشروع ليس قيد المراجعة'], 400);
}

if ($action === 'accept') {
    // Assign group number and accept
    $groupNumber = assignGroupNumber($projectId);
    
    // Update doctor note
    if (!empty($doctorNote)) {
        $stmt = $pdo->prepare("UPDATE projects SET doctor_note = ? WHERE id = ?");
        $stmt->execute([$doctorNote, $projectId]);
    }
    
    jsonResponse([
        'success' => true,
        'action' => 'accepted',
        'group_number' => $groupNumber,
        'message' => 'تم قبول المشروع وتعيين رقم المجموعة: ' . $groupNumber
    ]);
} else {
    // Reject
    $stmt = $pdo->prepare("UPDATE projects SET status = 'rejected', doctor_note = ? WHERE id = ?");
    $stmt->execute([$doctorNote ?: null, $projectId]);
    
    jsonResponse([
        'success' => true,
        'action' => 'rejected',
        'message' => 'تم رفض المشروع'
    ]);
}
