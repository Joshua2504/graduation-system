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
    // Remove event handlers (both quoted and unquoted values)
    $clean = preg_replace('/\bon\w+\s*=\s*["\'][^"\']*["\']\s*/i', '', $clean);
    $clean = preg_replace('/\bon\w+\s*=\s*[^\s>]+/i', '', $clean);
    // Block dangerous URI schemes in href (javascript:, data:, vbscript:)
    $clean = preg_replace('/href\s*=\s*["\']\s*(javascript|data|vbscript):[^"\']*["\']\s*/i', 'href="#"', $clean);
    $clean = preg_replace('/href\s*=\s*(javascript|data|vbscript):[^\s>]*/i', 'href="#"', $clean);
    // Remove style attributes to prevent CSS injection/UI redressing
    $clean = preg_replace('/\bstyle\s*=\s*["\'][^"\']*["\']\s*/i', '', $clean);
    $clean = preg_replace('/\bstyle\s*=\s*[^\s>]+/i', '', $clean);
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
 * Get all departments from the database (for dropdown lists)
 */
function getDepartments(): array {
    $pdo = getDB();
    $stmt = $pdo->query("SELECT id, name FROM departments ORDER BY name ASC");
    return $stmt->fetchAll();
}

/**
 * Get system settings
 */
function getSettings(): array {
    $pdo = getDB();
    $stmt = $pdo->query("SELECT * FROM settings WHERE id = 1");
    return $stmt->fetch() ?: ['registration_open' => 1, 'min_team_size' => 2, 'max_team_size' => 7, 'login_methods' => 'both'];
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
 * Generates a 4-char alphanumeric code like WG01, WG02, ..., WG99, then WG100, etc.
 */
function assignGroupNumber(int $projectId): string {
    $pdo = getDB();
    $prefix = 'WG';
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->query("SELECT group_number FROM projects WHERE group_number IS NOT NULL ORDER BY CAST(SUBSTRING(group_number, 3) AS UNSIGNED) DESC LIMIT 1 FOR UPDATE");
        $lastCode = $stmt->fetchColumn();
        if ($lastCode && preg_match('/^[A-Z]{2}(\d+)$/', $lastCode, $m)) {
            $nextSeq = (int)$m[1] + 1;
        } else {
            $nextSeq = 1;
        }
        $groupNumber = $prefix . str_pad($nextSeq, 2, '0', STR_PAD_LEFT);

        $stmt = $pdo->prepare("UPDATE projects SET group_number = ?, status = 'accepted' WHERE id = ?");
        $stmt->execute([$groupNumber, $projectId]);

        $pdo->commit();
        return $groupNumber;
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
        SELECT p.*, u.name AS leader_name, u.email AS leader_email, u.student_code AS leader_code, u.id AS leader_id,
               reviewer.name AS reviewer_name
        FROM projects p
        LEFT JOIN project_members pm ON pm.project_id = p.id AND pm.role = 'leader'
        LEFT JOIN users u ON u.id = pm.user_id
        LEFT JOIN users reviewer ON reviewer.id = p.reviewed_by
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
             WHERE pm2.project_id = p.id AND pm2.role = 'leader' LIMIT 1) AS leader_name,
            reviewer.name AS reviewer_name
        FROM projects p
        JOIN project_members pm ON pm.project_id = p.id AND pm.user_id = ?
        LEFT JOIN users reviewer ON reviewer.id = p.reviewed_by
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
    $required = ['name', 'student_code', 'gender', 'national_id', 'birth_date', 'governorate', 'address', 'phone', 'year', 'section'];
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
 * Get status labels (translated) for project statuses
 */
function getStatusLabels(): array {
    return [
        'draft' => __('status_draft'),
        'under_review' => __('status_under_review'),
        'accepted' => __('status_accepted'),
        'rejected' => __('status_rejected'),
    ];
}

/**
 * Get Bootstrap color classes for project statuses
 */
function getStatusColors(): array {
    return [
        'draft' => 'secondary',
        'under_review' => 'warning',
        'accepted' => 'success',
        'rejected' => 'danger',
    ];
}

/**
 * Get all review history entries for a project (newest first)
 */
function getProjectReviews(int $projectId): array {
    $pdo = getDB();
    $stmt = $pdo->prepare("
        SELECT pr.*, u.name AS reviewer_name
        FROM project_reviews pr
        LEFT JOIN users u ON u.id = pr.reviewer_id
        WHERE pr.project_id = ?
        ORDER BY pr.created_at DESC
    ");
    $stmt->execute([$projectId]);
    return $stmt->fetchAll();
}

/**
 * Validate phone number (11 digits)
 * Returns error string or null if valid
 */
function validatePhone(string $phone): ?string {
    if (!empty($phone) && !preg_match('/^\d{11}$/', $phone)) {
        return 'رقم الهاتف يجب أن يكون 11 رقم';
    }
    return null;
}

/**
 * Validate national ID (14 digits)
 * Returns error string or null if valid
 */
function validateNationalId(string $nationalId): ?string {
    if (!empty($nationalId) && !preg_match('/^\d{14}$/', $nationalId)) {
        return 'الرقم القومي يجب أن يكون 14 رقم';
    }
    return null;
}

/**
 * Handle file upload: validate, move to destination, update DB, check profile completion.
 * 
 * @param int    $userId    The user who owns the file
 * @param string $type      The image type (card, national_id, receipt, profile_picture)
 * @param array  $file      The $_FILES['file'] array
 * @param bool   $checkProfile  Whether to check/update profile_completed
 * @return array Response array with success/error
 */
function handleFileUpload(int $userId, string $type, array $file, bool $checkProfile = true): array {
    // Whitelist allowed type values to prevent SQL injection via dynamic column name
    $allowedTypes = [
        'card'            => 'card_image',
        'national_id'     => 'national_id_image',
        'receipt'         => 'receipt_image',
        'profile_picture' => 'profile_picture',
    ];
    if (!isset($allowedTypes[$type])) {
        return ['error' => 'نوع الملف غير صالح', 'code' => 400];
    }

    $validationError = validateUploadedFile($file);
    if ($validationError) {
        return ['error' => $validationError, 'code' => 400];
    }

    $uploadDir = dirname(__DIR__) . '/uploads/user_' . $userId;
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0775, true);
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $ext = $mime === 'image/png' ? 'png' : 'jpg';

    $dbField = $allowedTypes[$type];
    $filename = $userId . '_' . $type . '.' . $ext;
    $destPath = $uploadDir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        return ['error' => 'فشل في حفظ الملف', 'code' => 500];
    }

    chmod($destPath, 0644);

    $pdo = getDB();
    $stmt = $pdo->prepare("UPDATE users SET `$dbField` = ? WHERE id = ?");
    $stmt->execute([$filename, $userId]);

    $profileComplete = 0;
    if ($checkProfile) {
        $user = getUserProfile($userId);
        $profileComplete = isProfileComplete($user) ? 1 : 0;
        $stmt = $pdo->prepare("UPDATE users SET profile_completed = ? WHERE id = ?");
        $stmt->execute([$profileComplete, $userId]);
    }

    return [
        'success' => true,
        'filename' => $filename,
        'path' => secureFileUrl($userId, $filename),
        'profile_completed' => $profileComplete,
        'message' => 'تم رفع الملف بنجاح'
    ];
}
