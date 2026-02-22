<?php
/**
 * API: Student data management
 * 
 * POST /api/student.php — Create or update a student record for a project step
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

// Required fields
$projectId    = (int)($input['project_id'] ?? 0);
$studentIndex = (int)($input['student_index'] ?? -1);
$name         = trim($input['name'] ?? '');
$studentCode  = trim($input['student_code'] ?? '');
$gender       = trim($input['gender'] ?? '');
$nationalId   = trim($input['national_id'] ?? '');
$birthDate    = trim($input['birth_date'] ?? '');
$governorate  = trim($input['governorate'] ?? '');
$address      = trim($input['address'] ?? '');
$phone        = trim($input['phone'] ?? '');
$section      = trim($input['section'] ?? '');
$cardImage    = trim($input['card_image'] ?? '');
$nationalIdImage = trim($input['national_id_image'] ?? '');
$receiptImage = trim($input['receipt_image'] ?? '');

// Validate project ownership
$pdo = getDB();
$project = getProject($projectId);
if (!$project || (int)$project['user_id'] !== current_user_id()) {
    jsonResponse(['error' => 'غير مصرح'], 403);
}

if (!in_array($project['status'], ['draft', 'rejected'])) {
    jsonResponse(['error' => 'لا يمكن تعديل بيانات الطلاب في الحالة الحالية'], 400);
}

// Validate required fields
$errors = [];
if ($projectId === 0) $errors[] = 'project_id';
if ($studentIndex < 0 || $studentIndex > 6) $errors[] = 'student_index';
if (empty($name)) $errors[] = 'name';
if (empty($studentCode)) $errors[] = 'student_code';
if (!in_array($gender, ['male', 'female'])) $errors[] = 'gender';
if (empty($nationalId)) $errors[] = 'national_id';
if (empty($birthDate)) $errors[] = 'birth_date';
if (empty($governorate)) $errors[] = 'governorate';
if (empty($address)) $errors[] = 'address';
if (empty($phone)) $errors[] = 'phone';
if (empty($section)) $errors[] = 'section';
if (empty($cardImage)) $errors[] = 'card_image';
if (empty($nationalIdImage)) $errors[] = 'national_id_image';
if (empty($receiptImage)) $errors[] = 'receipt_image';

if (!empty($errors)) {
    jsonResponse(['error' => 'الحقول التالية مطلوبة: ' . implode(', ', $errors), 'missing_fields' => $errors], 400);
}

// Check student_code uniqueness (excluding this student's own record)
$stmt = $pdo->prepare("SELECT id FROM students WHERE student_code = ? AND NOT (project_id = ? AND student_index = ?)");
$stmt->execute([$studentCode, $projectId, $studentIndex]);
if ($stmt->fetch()) {
    jsonResponse(['error' => 'كود الطالب مسجل بالفعل في مشروع آخر', 'field' => 'student_code'], 400);
}

// Also check student_code doesn't match the users table (other registered team leaders)
// but allow it if it belongs to the current user
$stmt = $pdo->prepare("SELECT id FROM users WHERE student_code = ? AND id != ?");
$stmt->execute([$studentCode, current_user_id()]);
if ($stmt->fetch()) {
    // It's okay — another team leader's code. But we should check if this studentIndex=0 
    // and the code belongs to the team leader themselves
    // Actually the student_code in students table is independent of the users table
    // Just skip this check — the spec says student_code in students table must be unique within students
}

// Verify uploaded images exist on disk
$uploadDir = dirname(__DIR__) . '/uploads/project_' . $projectId;
foreach ([$cardImage, $nationalIdImage, $receiptImage] as $img) {
    if (!empty($img) && !file_exists($uploadDir . '/' . $img)) {
        jsonResponse(['error' => "الصورة $img غير موجودة. يرجى إعادة الرفع."], 400);
    }
}

// Insert or update (upsert based on project_id + student_index)
$stmt = $pdo->prepare("SELECT id FROM students WHERE project_id = ? AND student_index = ?");
$stmt->execute([$projectId, $studentIndex]);
$existing = $stmt->fetch();

if ($existing) {
    // Update
    $stmt = $pdo->prepare("UPDATE students SET 
        name = ?, student_code = ?, gender = ?, national_id = ?, birth_date = ?,
        governorate = ?, address = ?, phone = ?, section = ?,
        card_image = ?, national_id_image = ?, receipt_image = ?
        WHERE id = ?");
    $stmt->execute([
        $name, $studentCode, $gender, $nationalId, $birthDate,
        $governorate, $address, $phone, $section,
        $cardImage, $nationalIdImage, $receiptImage,
        $existing['id']
    ]);
    $studentId = $existing['id'];
} else {
    // Insert
    $stmt = $pdo->prepare("INSERT INTO students 
        (project_id, student_index, name, student_code, gender, national_id, birth_date,
         governorate, address, phone, section, card_image, national_id_image, receipt_image)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $projectId, $studentIndex, $name, $studentCode, $gender, $nationalId, $birthDate,
        $governorate, $address, $phone, $section,
        $cardImage, $nationalIdImage, $receiptImage
    ]);
    $studentId = (int)$pdo->lastInsertId();
}

jsonResponse([
    'success' => true,
    'student_id' => $studentId,
    'message' => 'تم حفظ بيانات الطالب'
]);
