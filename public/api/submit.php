<?php
/**
 * API: Submit project for review
 * 
 * POST /api/submit.php — Change project status to under_review
 */
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

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

$pdo = getDB();
$project = getProject($projectId);

if (!$project || (int)$project['user_id'] !== current_user_id()) {
    jsonResponse(['error' => 'غير مصرح'], 403);
}

if (!in_array($project['status'], ['draft', 'rejected'])) {
    jsonResponse(['error' => 'لا يمكن تقديم المشروع في الحالة الحالية'], 400);
}

// Verify all 7 students exist with complete data
$studentCount = countProjectStudents($projectId);
if ($studentCount < 7) {
    jsonResponse(['error' => "تم إدخال بيانات $studentCount طلاب فقط من أصل 7"], 400);
}

// Verify all 21 images exist on disk AND all required fields are filled
$students = getProjectStudents($projectId);
$uploadDir = dirname(__DIR__) . '/uploads/project_' . $projectId;
$missingImages = [];
$incompleteStudents = [];

foreach ($students as $student) {
    // Check required fields are filled (not null/empty from autosave)
    $requiredFields = ['name', 'student_code', 'gender', 'national_id', 'birth_date', 'governorate', 'address', 'phone', 'section'];
    foreach ($requiredFields as $field) {
        if (empty($student[$field])) {
            $incompleteStudents[] = ($student['name'] ?: 'طالب ' . ($student['student_index'] + 1)) . ' - ' . $field;
        }
    }

    foreach (['card_image', 'national_id_image', 'receipt_image'] as $imgField) {
        $imgFile = $student[$imgField] ?? '';
        if (empty($imgFile) || !file_exists($uploadDir . '/' . $imgFile)) {
            $missingImages[] = ($student['name'] ?: 'طالب ' . ($student['student_index'] + 1)) . ' - ' . $imgField;
        }
    }
}

if (!empty($incompleteStudents)) {
    jsonResponse([
        'error' => 'بعض بيانات الطلاب غير مكتملة. يرجى إكمال جميع الحقول.',
        'missing' => $incompleteStudents
    ], 400);
}

foreach ($students as $student) {
    foreach (['card_image', 'national_id_image', 'receipt_image'] as $imgField) {
        $imgFile = $student[$imgField] ?? '';
        if (empty($imgFile) || !file_exists($uploadDir . '/' . $imgFile)) {
            $missingImages[] = $student['name'] . ' - ' . $imgField;
        }
    }
}

if (!empty($missingImages)) {
    jsonResponse([
        'error' => 'بعض الصور مفقودة. يرجى إعادة رفعها.',
        'missing' => $missingImages
    ], 400);
}

// Update status
$stmt = $pdo->prepare("UPDATE projects SET status = 'under_review', submission_date = NOW() WHERE id = ?");
$stmt->execute([$projectId]);

jsonResponse([
    'success' => true,
    'message' => 'تم تقديم المشروع بنجاح وهو الآن قيد المراجعة'
]);
