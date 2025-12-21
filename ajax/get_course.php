<?php
/**
 * AJAX Endpoint - Get Single Course
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
// 2. Get and validate course ID (from query string)
// ----------------------------------------------------
$courseId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($courseId <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid course ID'
    ]);
    exit;
}

// ----------------------------------------------------
// 3. Delegate to controller
// ----------------------------------------------------
try {
    $controller = new CourseController();
    $result     = $controller->getCourse($courseId);

    // $result should be something like:
    // ['success' => true/false, 'course' => [...]] or an error message array
    echo json_encode($result);
} catch (\Throwable $e) {
    echo json_encode([
        'success' => false,
        'message' => 'An unexpected error occurred while fetching the course',
        // 'error'   => $e->getMessage() // enable only in debug mode
    ]);
}
