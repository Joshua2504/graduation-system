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
$isAutosave = !empty($input['autosave']);

// Fields
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

// Basic validation (always required: project_id, student_index, and at least a name for autosave)
if ($projectId === 0 || $studentIndex < 0 || $studentIndex > 6) {
    jsonResponse(['error' => 'بيانات غير صالحة'], 400);
}

if ($isAutosave) {
    // Autosave mode: skip strict validation, save whatever is filled
    if (empty($name) && empty($studentCode)) {
        jsonResponse(['error' => 'لا توجد بيانات كافية للحفظ التلقائي'], 400);
    }
    // Format validations only if the field has data
    if (!empty($studentCode) && !preg_match('/^[A-Za-z0-9]{1,30}$/', $studentCode)) {
        $studentCode = ''; // silently skip invalid partial input during autosave
    }
    if (!empty($nationalId) && !preg_match('/^[0-9]{14}$/', $nationalId)) {
        $nationalId = '';
    }
    if (!empty($phone) && !preg_match('/^[0-9]{11}$/', $phone)) {
        $phone = '';
    }
    if (!empty($gender) && !in_array($gender, ['male', 'female'])) {
        $gender = '';
    }
} else {
    // Full save mode: all fields required
    $errors = [];
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

    // Format validations
    if (!preg_match('/^[A-Za-z0-9]{1,30}$/', $studentCode)) {
        jsonResponse(['error' => 'كود الطالب يجب أن يكون أحرف وأرقام بحد أقصى 30 حرف', 'field' => 'student_code'], 400);
    }
    if (!preg_match('/^[0-9]{14}$/', $nationalId)) {
        jsonResponse(['error' => 'الرقم القومي يجب أن يكون 14 رقم', 'field' => 'national_id'], 400);
    }
    if (!preg_match('/^[0-9]{11}$/', $phone)) {
        jsonResponse(['error' => 'رقم الهاتف يجب أن يكون 11 رقم', 'field' => 'phone'], 400);
    }
}

// Check student_code uniqueness (only if student_code is filled)
if (!empty($studentCode)) {
    $stmt = $pdo->prepare("SELECT id FROM students WHERE student_code = ? AND NOT (project_id = ? AND student_index = ?)");
    $stmt->execute([$studentCode, $projectId, $studentIndex]);
    if ($stmt->fetch()) {
        if ($isAutosave) {
            $studentCode = ''; // silently clear on autosave
        } else {
            jsonResponse(['error' => 'كود الطالب مسجل بالفعل في مشروع آخر', 'field' => 'student_code'], 400);
        }
    }
}

// Verify uploaded images exist on disk (only for filled image fields)
$uploadDir = dirname(__DIR__) . '/uploads/project_' . $projectId;
foreach (['cardImage' => $cardImage, 'nationalIdImage' => $nationalIdImage, 'receiptImage' => $receiptImage] as $key => $img) {
    if (!empty($img) && !file_exists($uploadDir . '/' . $img)) {
        if ($isAutosave) {
            $$key = ''; // clear missing images silently on autosave
        } else {
            jsonResponse(['error' => "الصورة $img غير موجودة. يرجى إعادة الرفع."], 400);
        }
    }
}

// Insert or update (upsert based on project_id + student_index)
// Convert empty strings to NULL for nullable columns
$studentCodeDb  = $studentCode !== '' ? $studentCode : null;
$genderDb       = $gender !== '' ? $gender : null;
$nationalIdDb   = $nationalId !== '' ? $nationalId : null;
$birthDateDb    = $birthDate !== '' ? $birthDate : null;
$governorateDb  = $governorate !== '' ? $governorate : null;
$addressDb      = $address !== '' ? $address : null;
$phoneDb        = $phone !== '' ? $phone : null;
$sectionDb      = $section !== '' ? $section : null;
$cardImageDb    = $cardImage !== '' ? $cardImage : null;
$nationalIdImgDb = $nationalIdImage !== '' ? $nationalIdImage : null;
$receiptImgDb   = $receiptImage !== '' ? $receiptImage : null;

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
        $name, $studentCodeDb, $genderDb, $nationalIdDb, $birthDateDb,
        $governorateDb, $addressDb, $phoneDb, $sectionDb,
        $cardImageDb, $nationalIdImgDb, $receiptImgDb,
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
        $projectId, $studentIndex, $name, $studentCodeDb, $genderDb, $nationalIdDb, $birthDateDb,
        $governorateDb, $addressDb, $phoneDb, $sectionDb,
        $cardImageDb, $nationalIdImgDb, $receiptImgDb
    ]);
    $studentId = (int)$pdo->lastInsertId();
}

jsonResponse([
    'success' => true,
    'student_id' => $studentId,
    'message' => 'تم حفظ بيانات الطالب'
]);
