<?php
/**
 * Logout Handler
 * Destroys session and redirects to login page
 */

require_once __DIR__ . '/Helpers/SessionManager.php';

use Helpers\SessionManager;

// Get session instance
$session = SessionManager::getInstance();

// Destroy session (SessionManager already handles cookies/session)
$session->destroy();

// Redirect through the main router so all paths stay consistent
header("Location: /Plagirism_Detection_System/logout");
exit();
?>