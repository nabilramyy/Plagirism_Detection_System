<?php
/**
 * AJAX Endpoint - Delete Course
 * Access: Admin only
 */

declare(strict_types=1);

require_once __DIR__ . '/../app/Controllers/CourseController.php';
require_once __DIR__ . '/../app/Helpers/SessionManager.php';
require_once __DIR__ . '/../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../app/Helpers/Csrf.php';

use Controllers\CourseController;
use Helpers\SessionManager;
use Middleware\AuthMiddleware;
use Helpers\Csrf;

header('Content-Type: application/json; charset=utf-8');

// ----------------------------------------------------
// 1. Authentication / authorization (admin only)
// ----------------------------------------------------
$session = SessionManager::getInstance();
$auth    = new AuthMiddleware();   // kept for consistency / future middleware use

if (!$session->isLoggedIn() || $session->getUserRole() !== 'admin') {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized'
    ]);
    exit;
}

// ----------------------------------------------------
// 2. HTTP method check
// ----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}



// ----------------------------------------------------
// 4. Get and validate course ID
// ----------------------------------------------------
$courseId = isset($_POST['course_id']) ? (int) $_POST['course_id'] : 0;

if ($courseId <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid course ID'
    ]);
    exit;
}

// ----------------------------------------------------
// 5. Delegate to controller
// ----------------------------------------------------
try {
    $controller = new CourseController();
    $result     = $controller->deleteCourse($courseId);

    // $result should be something like:
    // ['success' => true/false, 'message' => '...', 'deleted_id' => $courseId]
    echo json_encode($result);
} catch (\Throwable $e) {
    echo json_encode([
        'success' => false,
        'message' => 'An unexpected error occurred while deleting the course',
        // 'error'   => $e->getMessage() // enable only in debug mode
    ]);
}
