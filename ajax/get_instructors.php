<?php
declare(strict_types=1);

/**
 * AJAX Endpoint - Get All Instructors
 * Access: Admin only
 */

require_once __DIR__ . '/../app/Controllers/CourseController.php';
require_once __DIR__ . '/../app/Helpers/SessionManager.php';
require_once __DIR__ . '/../app/Middleware/AuthMiddleware.php';

use Controllers\CourseController;
use Helpers\SessionManager;
use Middleware\AuthMiddleware;

header('Content-Type: application/json; charset=utf-8');

// 1. Authentication (admin only)
$session = SessionManager::getInstance();
$auth    = new AuthMiddleware();

if (!$session->isLoggedIn() || $session->getUserRole() !== 'admin') {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized'
    ]);
    exit;
}

// 2. Delegate to controller
try {
    $controller = new CourseController();   // uses includes/db.php and $conn
    $result     = $controller->getInstructors(); // returns ['success'=>true,'data'=>[...]]

    echo json_encode($result);
} catch (\Throwable $e) {
    echo json_encode([
        'success' => false,
        'message' => 'An unexpected error occurred while fetching instructors'
        // 'error' => $e->getMessage() // enable in debug
    ]);
}
