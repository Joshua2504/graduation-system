<?php
/**
 * API: Submit project for review
 * 
 * POST /api/submit.php — Change project status to under_review
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

// Must be leader
if (!isProjectLeader($projectId, $userId)) {
    jsonResponse(['error' => 'فقط قائد الفريق يمكنه تقديم المشروع'], 403);
}

$pdo = getDB();
$project = getProject($projectId);

if (!$project || !in_array($project['status'], ['draft', 'rejected'])) {
    jsonResponse(['error' => 'لا يمكن تقديم المشروع في الحالة الحالية'], 400);
}

// Check if resubmission is allowed for rejected projects
if ($project['status'] === 'rejected' && empty($project['allow_resubmit'])) {
    jsonResponse(['error' => __('resubmit_not_allowed')], 403);
}

// Check member count against settings
$settings = getSettings();
$members = getProjectMembers($projectId);
$memberCount = count($members);
$minSize = (int)$settings['min_team_size'];
$maxSize = (int)$settings['max_team_size'];

if ($memberCount < $minSize) {
    jsonResponse(['error' => "عدد أعضاء الفريق ($memberCount) أقل من الحد الأدنى ($minSize)"], 400);
}

if ($memberCount > $maxSize) {
    jsonResponse(['error' => "عدد أعضاء الفريق ($memberCount) أكثر من الحد الأقصى ($maxSize)"], 400);
}

// Verify all members have completed profiles
$incompleteMembers = [];
foreach ($members as $member) {
    if (!isProfileComplete($member)) {
        $incompleteMembers[] = $member['name'];
    }

    // Also check images exist on disk
    $userUploadDir = dirname(__DIR__) . '/uploads/user_' . $member['id'];
    foreach (['card_image', 'national_id_image', 'receipt_image'] as $imgField) {
        $imgFile = $member[$imgField] ?? '';
        if (empty($imgFile) || !file_exists($userUploadDir . '/' . $imgFile)) {
            if (!in_array($member['name'], $incompleteMembers)) {
                $incompleteMembers[] = $member['name'];
            }
        }
    }
}

if (!empty($incompleteMembers)) {
    jsonResponse([
        'error' => 'بعض أعضاء الفريق لم يكملوا ملفاتهم الشخصية',
        'incomplete_members' => $incompleteMembers
    ], 400);
}

// Verify all members have submitted their papers
$papersNotSubmitted = [];
foreach ($members as $member) {
    if (empty($member['paper_submitted'])) {
        $papersNotSubmitted[] = $member['name'];
    }
}

if (!empty($papersNotSubmitted)) {
    jsonResponse([
        'error' => 'لم يقم جميع أعضاء الفريق بتقديم أوراقهم بعد',
        'members_not_submitted' => $papersNotSubmitted
    ], 400);
}

// If rejected, reset status (keep doctor_note for history)
if ($project['status'] === 'rejected') {
    $stmt = $pdo->prepare("UPDATE projects SET status = 'under_review', submission_date = NOW(), allow_resubmit = 0 WHERE id = ?");
    $stmt->execute([$projectId]);
} else {
    $stmt = $pdo->prepare("UPDATE projects SET status = 'under_review', submission_date = NOW() WHERE id = ?");
    $stmt->execute([$projectId]);
}

jsonResponse([
    'success' => true,
    'message' => 'تم تقديم المشروع بنجاح وهو الآن قيد المراجعة'
]);
