<?php
/**
 * AJAX Endpoint - Get All Courses
 * Access: Admin only
 */

declare(strict_types=1);

require_once __DIR__ . '/../app/Controllers/CourseController.php';
require_once __DIR__ . '/../app/Helpers/SessionManager.php';
require_once __DIR__ . '/../app/Middleware/AuthMiddleware.php';

use Controllers\CourseController;
use Helpers\SessionManager;
use Middleware\AuthMiddleware;

header('Content-Type: application/json; charset=utf-8');

// ----------------------------------------------------
// 1. Authentication / authorization (admin only)
// ----------------------------------------------------
$session = SessionManager::getInstance();
$auth    = new AuthMiddleware();   // kept for consistency with your MVC setup

if (!$session->isLoggedIn() || $session->getUserRole() !== 'admin') {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized'
    ]);
    exit;
}

// ----------------------------------------------------
// 2. Read and normalize query parameters
// ----------------------------------------------------
$page             = isset($_GET['page'])         ? (int) $_GET['page']         : 1;
$limit            = isset($_GET['limit'])        ? (int) $_GET['limit']        : 10;
$search           = isset($_GET['search'])       ? trim((string) $_GET['search']) : '';
$instructorFilter = isset($_GET['instructor_id'])? trim((string) $_GET['instructor_id']) : '';

// Basic guards to avoid invalid paging
if ($page < 1) {
    $page = 1;
}
if ($limit < 1) {
    $limit = 10;
}

// ----------------------------------------------------
// 3. Delegate to controller
// ----------------------------------------------------
try {
    $controller = new CourseController();
    $result     = $controller->getCourses($page, $limit, $search, $instructorFilter);

    // $result is expected to contain:
    // ['success' => true, 'data' => [...], 'pagination' => [...]] or an error structure
    echo json_encode($result);
} catch (\Throwable $e) {
    echo json_encode([
        'success' => false,
        'message' => 'An unexpected error occurred while fetching courses',
        // 'error'   => $e->getMessage() // enable only in debug mode
    ]);
}
