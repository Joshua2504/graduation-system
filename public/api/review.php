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
$allowResubmit = isset($input['allow_resubmit']) ? (int)(bool)$input['allow_resubmit'] : 1;

if ($projectId === 0) {
    jsonResponse(['error' => __('project_id_required')], 400);
}

if (!in_array($action, ['accept', 'reject'])) {
    jsonResponse(['error' => __('invalid_action')], 400);
}

$pdo = getDB();
$project = getProject($projectId);

if (!$project) {
    jsonResponse(['error' => __('project_not_found')], 404);
}

if ($project['status'] !== 'under_review' && !($action === 'reject' && $project['status'] === 'accepted')) {
    jsonResponse(['error' => __('invalid_project_state')], 400);
}

if ($action === 'accept') {
    // Assign group number and accept
    $groupNumber = assignGroupNumber($projectId);
    
    // Update doctor note and reviewer
    $stmt = $pdo->prepare("UPDATE projects SET doctor_note = COALESCE(?, doctor_note), reviewed_by = ? WHERE id = ?");
    $stmt->execute([$doctorNote ?: null, current_user_id(), $projectId]);
    
    // Log review action
    $stmt = $pdo->prepare("INSERT INTO project_reviews (project_id, reviewer_id, action, note) VALUES (?, ?, 'accepted', ?)");
    $stmt->execute([$projectId, current_user_id(), $doctorNote ?: null]);
    
    jsonResponse([
        'success' => true,
        'action' => 'accepted',
        'group_number' => $groupNumber,
        'message' => sprintf(__('review_project_accepted'), $groupNumber)
    ]);
} else {
    // Reject
    $stmt = $pdo->prepare("UPDATE projects SET status = 'rejected', doctor_note = ?, reviewed_by = ?, allow_resubmit = ?, group_number = NULL WHERE id = ?");
    $stmt->execute([$doctorNote ?: null, current_user_id(), $allowResubmit, $projectId]);
    
    // Log review action
    $stmt = $pdo->prepare("INSERT INTO project_reviews (project_id, reviewer_id, action, note, allow_resubmit) VALUES (?, ?, 'rejected', ?, ?)");
    $stmt->execute([$projectId, current_user_id(), $doctorNote ?: null, $allowResubmit]);
    
    jsonResponse([
        'success' => true,
        'action' => 'rejected',
        'message' => __('review_project_rejected')
    ]);
}
