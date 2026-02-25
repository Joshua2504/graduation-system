<?php
/**
 * Shared helper functions
 */

require_once __DIR__ . '/db.php';

/**
 * Sanitize input string
 */
function sanitize(?string $str): string {
    return htmlspecialchars(trim($str ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitize HTML content — allow only safe tags for rich text descriptions
 */
function sanitizeHtml(?string $html): string {
    if (empty($html)) return '';
    $allowed = '<b><i><u><strong><em><ul><ol><li><a><br><p><div><span><img>';
    $clean = strip_tags(trim($html), $allowed);
    // Remove event handlers and javascript: URLs
    $clean = preg_replace('/\bon\w+\s*=\s*["\'][^"\']*["\']\s*/i', '', $clean);
    $clean = preg_replace('/href\s*=\s*["\']javascript:[^"\']*["\']\s*/i', 'href="#"', $clean);
    // Only allow img src from our file API
    $clean = preg_replace_callback('/<img\s[^>]*>/i', function($match) {
        $tag = $match[0];
        // Extract src (browser innerHTML may have &amp; instead of &)
        if (preg_match('/src\s*=\s*["\'](\/api\/file\.php\?[^"\']+)["\']/', $tag, $srcMatch)) {
            // Decode first to normalize, then re-encode once
            $src = html_entity_decode($srcMatch[1], ENT_QUOTES, 'UTF-8');
            $src = htmlspecialchars($src, ENT_QUOTES, 'UTF-8');
            // Extract alt if present
            $alt = '';
            if (preg_match('/alt\s*=\s*["\']([^"\']*)["\']/', $tag, $altMatch)) {
                $alt = htmlspecialchars(html_entity_decode($altMatch[1], ENT_QUOTES, 'UTF-8'), ENT_QUOTES, 'UTF-8');
            }
            // Preserve width/height if set
            $style = '';
            if (preg_match('/width\s*=\s*["\']?(\d+(%|px)?)["\']?/', $tag, $wMatch)) {
                $style .= 'width:' . htmlspecialchars($wMatch[1], ENT_QUOTES, 'UTF-8') . (strpos($wMatch[1], '%') === false && strpos($wMatch[1], 'px') === false ? 'px' : '') . ';';
            }
            if (preg_match('/style\s*=\s*["\']([^"\']*)["\']/', $tag, $sMatch)) {
                // Extract only width/height from inline style
                if (preg_match('/width\s*:\s*([\d.]+(?:px|%))/', $sMatch[1], $sw)) {
                    $style .= 'width:' . htmlspecialchars($sw[1], ENT_QUOTES, 'UTF-8') . ';';
                }
            }
            $styleAttr = $style ? ' style="' . $style . '"' : '';
            return '<img src="' . $src . '" alt="' . $alt . '" class="desc-img"' . $styleAttr . '>';
        }
        return ''; // Strip img tags with non-API sources
    }, $clean);
    return $clean;
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
    return $stmt->fetch() ?: ['registration_open' => 1, 'min_team_size' => 2, 'max_team_size' => 7];
}

/**
 * Generate a unique join code for a project (6-char alphanumeric uppercase)
 */
function generateJoinCode(): string {
    $pdo = getDB();
    do {
        $code = strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM projects WHERE join_code = ?");
        $stmt->execute([$code]);
    } while ((int)$stmt->fetchColumn() > 0);
    return $code;
}

/**
 * Assign the next group number to an accepted project (transaction-safe)
 */
function assignGroupNumber(int $projectId): int {
    $pdo = getDB();
    $pdo->beginTransaction();
    try {
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
 * Get a project by ID (with leader info from project_members + users)
 */
function getProject(int $id): ?array {
    $pdo = getDB();
    $stmt = $pdo->prepare("
        SELECT p.*, u.name AS leader_name, u.email AS leader_email, u.student_code AS leader_code, u.id AS leader_id
        FROM projects p
        LEFT JOIN project_members pm ON pm.project_id = p.id AND pm.role = 'leader'
        LEFT JOIN users u ON u.id = pm.user_id
        WHERE p.id = ?
    ");
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

/**
 * Get all members for a project (with user profile data)
 */
function getProjectMembers(int $projectId): array {
    $pdo = getDB();
    $stmt = $pdo->prepare("
        SELECT u.*, pm.role AS member_role, pm.joined_at
        FROM project_members pm
        JOIN users u ON u.id = pm.user_id
        WHERE pm.project_id = ?
        ORDER BY pm.role = 'leader' DESC, pm.joined_at ASC
    ");
    $stmt->execute([$projectId]);
    return $stmt->fetchAll();
}

/**
 * Count members for a project
 */
function countProjectMembers(int $projectId): int {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM project_members WHERE project_id = ?");
    $stmt->execute([$projectId]);
    return (int)$stmt->fetchColumn();
}

/**
 * Get all projects a user belongs to
 */
function getUserProjects(int $userId): array {
    $pdo = getDB();
    $stmt = $pdo->prepare("
        SELECT p.*, pm.role AS my_role,
            (SELECT COUNT(*) FROM project_members WHERE project_id = p.id) AS member_count,
            (SELECT u.name FROM project_members pm2 JOIN users u ON u.id = pm2.user_id 
             WHERE pm2.project_id = p.id AND pm2.role = 'leader' LIMIT 1) AS leader_name
        FROM projects p
        JOIN project_members pm ON pm.project_id = p.id AND pm.user_id = ?
        ORDER BY p.updated_at DESC
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

/**
 * Check if user is leader of a project
 */
function isProjectLeader(int $projectId, int $userId): bool {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM project_members WHERE project_id = ? AND user_id = ? AND role = 'leader'");
    $stmt->execute([$projectId, $userId]);
    return (int)$stmt->fetchColumn() > 0;
}

/**
 * Check if user is a member of a project
 */
function isProjectMember(int $projectId, int $userId): bool {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM project_members WHERE project_id = ? AND user_id = ?");
    $stmt->execute([$projectId, $userId]);
    return (int)$stmt->fetchColumn() > 0;
}

/**
 * Get user profile by ID
 */
function getUserProfile(int $userId): ?array {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetch() ?: null;
}

/**
 * Check if user profile is complete (all required fields + 3 images)
 */
function isProfileComplete(array $user): bool {
    $required = ['name', 'student_code', 'gender', 'national_id', 'birth_date', 'governorate', 'address', 'phone', 'section'];
    foreach ($required as $field) {
        if (empty($user[$field])) return false;
    }
    $images = ['card_image', 'national_id_image', 'receipt_image'];
    foreach ($images as $img) {
        if (empty($user[$img])) return false;
    }
    return true;
}

/**
 * Get pending invitations for a project (not yet accepted)
 */
function getProjectPendingInvitations(int $projectId): array {
    $pdo = getDB();
    $stmt = $pdo->prepare("
        SELECT i.*, 
               u_invited.name AS invited_name, u_invited.email AS invited_email, u_invited.student_code AS invited_student_code,
               u_by.name AS invited_by_name
        FROM invitations i
        LEFT JOIN users u_invited ON u_invited.id = i.invited_user_id
        JOIN users u_by ON u_by.id = i.invited_by
        WHERE i.project_id = ? AND i.status = 'pending' AND i.expires_at > NOW()
        ORDER BY i.created_at DESC
    ");
    $stmt->execute([$projectId]);
    return $stmt->fetchAll();
}

/**
 * Get pending invitations received by a user (direct invites)
 */
function getPendingInvitations(int $userId): array {
    $pdo = getDB();
    $stmt = $pdo->prepare("
        SELECT i.*, p.title AS project_title, p.type AS project_type,
               u.name AS invited_by_name
        FROM invitations i
        JOIN projects p ON p.id = i.project_id
        JOIN users u ON u.id = i.invited_by
        WHERE i.invited_user_id = ? AND i.status = 'pending' AND i.expires_at > NOW()
        ORDER BY i.created_at DESC
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
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
 * Generate a secure URL for serving an uploaded file through the authenticated endpoint.
 * 
 * @param int    $userId   The owner's user ID
 * @param string $filename The filename stored in the database
 * @return string The URL path to the secure file endpoint
 */
function secureFileUrl(int $userId, string $filename): string {
    return '/api/file?user=' . urlencode($userId) . '&file=' . urlencode($filename);
}

/**
 * Validate file upload: MIME type + size
 * Returns error string or null if valid
 */
function validateUploadedFile(array $file): ?string {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return 'فشل رفع الملف';
    }
    if ($file['size'] > 5 * 1024 * 1024) {
        return 'حجم الملف يتجاوز 5 ميجابايت';
    }
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
