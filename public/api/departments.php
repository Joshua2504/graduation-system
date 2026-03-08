<?php
/**
 * API: Department management (admin-only CRUD)
 * 
 * GET    /api/departments.php       — List all departments (any authenticated user)
 * POST   /api/departments.php       — Create department (admin only)
 * PUT    /api/departments.php       — Update department (admin only)
 * DELETE /api/departments.php       — Delete department (admin only)
 */
require_once dirname(__DIR__) . '/includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];
$pdo = getDB();

// ─── GET: List departments (any authenticated user) ───
if ($method === 'GET') {
    require_login(true);
    $stmt = $pdo->query("SELECT id, name FROM departments ORDER BY name ASC");
    jsonResponse(['departments' => $stmt->fetchAll()]);
}

// All write operations require admin
require_admin(true);

// ─── POST: Create department ───
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $name = trim($input['name'] ?? '');

    if (empty($name)) {
        jsonResponse(['error' => __('department_name_required')], 400);
    }

    // Check uniqueness
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM departments WHERE name = ?");
    $stmt->execute([$name]);
    if ((int)$stmt->fetchColumn() > 0) {
        jsonResponse(['error' => __('department_exists')], 400);
    }

    $stmt = $pdo->prepare("INSERT INTO departments (name) VALUES (?)");
    $stmt->execute([$name]);

    jsonResponse([
        'success' => true,
        'department' => ['id' => (int)$pdo->lastInsertId(), 'name' => $name],
        'message' => __('department_created')
    ]);
}

// ─── PUT: Update department ───
if ($method === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = (int)($input['id'] ?? 0);
    $name = trim($input['name'] ?? '');

    if ($id === 0 || empty($name)) {
        jsonResponse(['error' => __('department_name_required')], 400);
    }

    // Check uniqueness (exclude self)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM departments WHERE name = ? AND id != ?");
    $stmt->execute([$name, $id]);
    if ((int)$stmt->fetchColumn() > 0) {
        jsonResponse(['error' => __('department_exists')], 400);
    }

    // Update the department name + update all users who had the old name
    $stmt = $pdo->prepare("SELECT name FROM departments WHERE id = ?");
    $stmt->execute([$id]);
    $oldName = $stmt->fetchColumn();

    $stmt = $pdo->prepare("UPDATE departments SET name = ? WHERE id = ?");
    $stmt->execute([$name, $id]);

    if ($oldName) {
        $stmt = $pdo->prepare("UPDATE users SET section = ? WHERE section = ?");
        $stmt->execute([$name, $oldName]);
    }

    jsonResponse([
        'success' => true,
        'message' => __('department_updated')
    ]);
}

// ─── DELETE: Delete department ───
if ($method === 'DELETE') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = (int)($input['id'] ?? 0);

    if ($id === 0) {
        jsonResponse(['error' => 'ID required'], 400);
    }

    // Get name before deleting (to clear from users)
    $stmt = $pdo->prepare("SELECT name FROM departments WHERE id = ?");
    $stmt->execute([$id]);
    $deptName = $stmt->fetchColumn();

    $stmt = $pdo->prepare("DELETE FROM departments WHERE id = ?");
    $stmt->execute([$id]);

    // Clear section for users who had this department
    if ($deptName) {
        $stmt = $pdo->prepare("UPDATE users SET section = NULL WHERE section = ?");
        $stmt->execute([$deptName]);
    }

    jsonResponse([
        'success' => true,
        'message' => __('department_deleted')
    ]);
}

jsonResponse(['error' => 'Method not allowed'], 405);
