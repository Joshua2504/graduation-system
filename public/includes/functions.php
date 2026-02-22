<?php
/**
 * Shared helper functions
 */

require_once __DIR__ . '/db.php';

/**
 * Sanitize input string
 */
function sanitize(string $str): string {
    return htmlspecialchars(trim($str), ENT_QUOTES, 'UTF-8');
}

/**
 * Send JSON response and exit
 */
function jsonResponse(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Redirect to a URL
 */
function redirect(string $url): void {
    header("Location: $url");
    exit;
}

/**
 * Get system settings
 */
function getSettings(): array {
    $pdo = getDB();
    $stmt = $pdo->query("SELECT * FROM settings WHERE id = 1");
    return $stmt->fetch() ?: ['registration_open' => 1];
}

/**
 * Assign the next group number to an accepted project (transaction-safe)
 */
function assignGroupNumber(int $projectId): int {
    $pdo = getDB();
    $pdo->beginTransaction();
    try {
        // Lock the projects table to prevent race conditions
        $stmt = $pdo->query("SELECT COALESCE(MAX(group_number), 0) + 1 AS next_num FROM projects FOR UPDATE");
        $nextNum = (int)$stmt->fetch()['next_num'];

        $stmt = $pdo->prepare("UPDATE projects SET group_number = ?, status = 'accepted' WHERE id = ?");
        $stmt->execute([$nextNum, $projectId]);

        $pdo->commit();
        return $nextNum;
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * Get a project by ID
 */
function getProject(int $id): ?array {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT p.*, u.name AS leader_name, u.email AS leader_email, u.student_code AS leader_code 
                           FROM projects p 
                           JOIN users u ON p.user_id = u.id 
                           WHERE p.id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

/**
 * Get all students for a project
 */
function getProjectStudents(int $projectId): array {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM students WHERE project_id = ? ORDER BY student_index ASC");
    $stmt->execute([$projectId]);
    return $stmt->fetchAll();
}

/**
 * Get the team leader's project
 */
function getUserProject(int $userId): ?array {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM projects WHERE user_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$userId]);
    return $stmt->fetch() ?: null;
}

/**
 * Count students saved for a project
 */
function countProjectStudents(int $projectId): int {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE project_id = ?");
    $stmt->execute([$projectId]);
    return (int)$stmt->fetchColumn();
}

/**
 * Check if a project title is a duplicate (case-insensitive), excluding the given project ID
 */
function findDuplicateProjects(int $excludeProjectId, string $title): array {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT id, title, status FROM projects WHERE LOWER(title) = LOWER(?) AND id != ?");
    $stmt->execute([$title, $excludeProjectId]);
    return $stmt->fetchAll();
}

/**
 * Validate file upload: MIME type + size
 * Returns error string or null if valid
 */
function validateUploadedFile(array $file): ?string {
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return 'فشل رفع الملف';
    }

    // Max 5MB
    if ($file['size'] > 5 * 1024 * 1024) {
        return 'حجم الملف يتجاوز 5 ميجابايت';
    }

    // Check MIME type via finfo (magic bytes)
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $allowed = ['image/jpeg', 'image/png'];
    if (!in_array($mime, $allowed)) {
        return 'نوع الملف غير مسموح - يجب أن يكون JPG أو PNG';
    }

    return null;
}

/**
 * Get Egyptian governorates list
 */
function getGovernorates(): array {
    return [
        'القاهرة', 'الجيزة', 'الإسكندرية', 'الدقهلية', 'البحر الأحمر',
        'البحيرة', 'الفيوم', 'الغربية', 'الإسماعيلية', 'المنوفية',
        'المنيا', 'القليوبية', 'الوادي الجديد', 'السويس', 'أسوان',
        'أسيوط', 'بني سويف', 'بورسعيد', 'دمياط', 'الشرقية',
        'جنوب سيناء', 'كفر الشيخ', 'مطروح', 'الأقصر', 'قنا',
        'شمال سيناء', 'سوهاج'
    ];
}

/**
 * Get project types
 */
function getProjectTypes(): array {
    return [
        ['ar' => 'تطبيق ويب', 'en' => 'Web Application'],
        ['ar' => 'تطبيق موبايل', 'en' => 'Mobile Application'],
        ['ar' => 'نظام ذكاء اصطناعي', 'en' => 'AI System'],
        ['ar' => 'نظام إدارة', 'en' => 'Management System'],
        ['ar' => 'نظام شبكات', 'en' => 'Network System'],
        ['ar' => 'أخرى', 'en' => 'Other'],
    ];
}
