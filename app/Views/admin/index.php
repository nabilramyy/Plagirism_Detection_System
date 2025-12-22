<?php
/**
 * Admin Dashboard Main View
 * Located at: app/Views/admin/index.php
 * Accessed via: /admin
 * 
 * This is the main admin dashboard that loads sub-pages
 */

// This view should only be accessible through index.php
if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

require_once APP_ROOT . '/app/Helpers/SessionManager.php';
require_once APP_ROOT . '/app/Middleware/AuthMiddleware.php';
require_once APP_ROOT . '/app/Helpers/Csrf.php';

use Helpers\SessionManager;
use Middleware\AuthMiddleware;
use Helpers\Csrf;

// Initialize authentication
$session = SessionManager::getInstance();
$auth = new AuthMiddleware();

// Verify admin access
if (!$session->isLoggedIn() || $session->getUserRole() !== 'admin') {
    header('Location: ' . BASE_URL . '/signup');
    exit;
}

// Define constant for sub-pages
define('ADMIN_ACCESS', true);

// Get requested page
$page = $_GET['page'] ?? 'dashboard';

// Allowed admin pages
$allowedPages = [
    'dashboard',
    'user_management',
    'course_management',
    'submissions_overview',
    'system_settings',
];

// Validate page
if (!in_array($page, $allowedPages, true)) {
    $page = 'dashboard';
}

// Get current user info
$currentUser = $auth->getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - <?= ucwords(str_replace('_', ' ', $page)) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php
    // Set user info for header
    $_SESSION['user_name'] = $currentUser['name'];
    $_SESSION['user_email'] = $currentUser['email'];

    // Include header and sidebar
    include APP_ROOT . '/includes/admin_header.php';
    include APP_ROOT . '/includes/admin_sidebar.php';
    ?>

    <main class="main-content" id="mainContent">
        <?php
        // Load requested page
        $pageFile = APP_ROOT . '/app/Views/admin/' . $page . '.php';

        if (file_exists($pageFile)) {
            // Additional access check
            if ($auth->canAccess($page)) {
                include $pageFile;
            } else {
                echo '<div style="padding:40px;text-align:center;color:#dc2626;">';
                echo '<h2>⛔ Access Denied</h2>';
                echo '<p>You don\'t have permission to access this page.</p>';
                echo '</div>';
            }
        } else {
            echo '<div style="padding:40px;text-align:center;color:#666;">';
            echo '<h2>⚠️ Page Not Found</h2>';
            echo '<p>The page <strong>' . htmlspecialchars($page) . '</strong> doesn\'t exist.</p>';
            echo '<a href="' . BASE_URL . '/admin?page=dashboard" class="btn primary">Go to Dashboard</a>';
            echo '</div>';
        }
        ?>
    </main>

    <!-- Add CSRF token to all forms -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const csrfToken = '<?= Csrf::token() ?>';
            
            // Add CSRF token to all forms that don't have it
            document.querySelectorAll('form').forEach(form => {
                if (!form.querySelector('input[name="_csrf"]')) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = '_csrf';
                    input.value = csrfToken;
                    form.appendChild(input);
                }
            });
        });
    </script>

    <!-- JavaScript files -->
    <script src="<?= BASE_URL ?>/assets/js/script.js"></script>
    <script src="<?= BASE_URL ?>/assets/js/admin.js"></script>

    <?php
    // Load page-specific JavaScript
    if ($page === 'dashboard') {
        echo '<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>' . "\n";
        echo '<script src="' . BASE_URL . '/assets/js/admin_dashboard.js"></script>' . "\n";
    } elseif ($page === 'user_management') {
        echo '<script src="' . BASE_URL . '/assets/js/admin_users.js"></script>' . "\n";
    } elseif ($page === 'course_management') {
        echo '<script src="' . BASE_URL . '/assets/js/admin_courses.js"></script>' . "\n";
    } elseif ($page === 'system_settings') {
        echo '<script src="' . BASE_URL . '/assets/js/admin_settings.js"></script>' . "\n";
    }
    ?>
</body>
</html>