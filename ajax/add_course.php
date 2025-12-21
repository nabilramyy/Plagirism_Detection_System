<?php
/**
 * AJAX Endpoint - Add New Course
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
$auth    = new AuthMiddleware();   // If you later move checks into middleware

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
// 3. Collect and sanitize input
// ----------------------------------------------------
$csrfToken     = $_POST['_csrf']        ?? '';
$name          = trim($_POST['name']    ?? '');
$description   = trim($_POST['description'] ?? '');
$instructorId  = $_POST['instructor_id']    ?? 0;

// Optional simple casting
$instructorId = (int) $instructorId;



// Prepare data array for the controller
$data = [
    'name'          => $name,
    'description'   => $description,
    'instructor_id' => $instructorId
];

// ----------------------------------------------------
// 5. Delegate to controller
// ----------------------------------------------------
try {
    $controller = new CourseController();
    $result     = $controller->addCourse($data);

    // Expecting $result to be an array like:
    // ['success' => true/false, 'message' => '...', 'course_id' => 123]
    echo json_encode($result);
} catch (\Throwable $e) {
    // Fallback error response
    echo json_encode([
        'success' => false,
        'message' => 'An unexpected error occurred',
        // 'error'   => $e->getMessage() // enable only in debug mode
    ]);
}
