<?php
namespace Helpers;

/**
 * SessionManager - Centralized session handling and authentication
 * Follows Single Responsibility Principle
 * Updated with active session ban check
 */
class SessionManager {
    
    private static $instance = null;
    private $sessionTimeout = 3600; // 1 hour default
    private $db = null;
    
    private function __construct() {
        $this->initSession();
        $this->initDatabase();
    }
    
    /**
     * Singleton pattern - ensures one instance
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Initialize database connection for ban checks
     */
    private function initDatabase() {
        $host = "localhost";
        $user = "root";
        $pass = "";
        $dbname = "pal";
        
        $this->db = new \mysqli($host, $user, $pass, $dbname);
        
        if ($this->db->connect_error) {
            error_log("SessionManager DB connection failed: " . $this->db->connect_error);
            $this->db = null;
        } else {
            $this->db->set_charset("utf8");
        }
    }
    
    /**
     * Initialize secure session
     */
    private function initSession() {
        if (session_status() === PHP_SESSION_NONE) {
            // Secure session configuration
            ini_set('session.cookie_httponly', 1);
            ini_set('session.use_only_cookies', 1);
            ini_set('session.cookie_secure', 0); // Set to 1 in production with HTTPS
            ini_set('session.cookie_samesite', 'Strict');
            
            session_start();
            
            // Regenerate session ID to prevent fixation attacks
            if (!isset($_SESSION['initiated'])) {
                session_regenerate_id(true);
                $_SESSION['initiated'] = true;
                $_SESSION['created_at'] = time();
            }
            
            // Check for session hijacking
            $this->validateSession();
        }
    }
    
    /**
     * Validate session integrity and check if user is banned
     */
    private function validateSession() {
        // Check user agent consistency
        if (isset($_SESSION['user_agent'])) {
            if ($_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
                $this->destroy();
                return false;
            }
        } else if (isset($_SESSION['user_id'])) {
            $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
        }
        
        // Check session timeout
        if (isset($_SESSION['last_activity'])) {
            if (time() - $_SESSION['last_activity'] > $this->sessionTimeout) {
                $this->destroy();
                return false;
            }
        }
        
        // **NEW: Check if logged-in user is banned**
        if ($this->isLoggedIn()) {
            if ($this->checkUserBanned()) {
                // User was banned while logged in - destroy session
                $this->destroy();
                
                // Set error message for login page
                session_start(); // Restart session to show message
                $_SESSION['auth_error'] = 'Your account has been banned. Please contact the administrator.';
                
                // Redirect to login
                header("Location: /Plagirism_Detection_System/signup.php");
                exit();
            }
        }
        
        $_SESSION['last_activity'] = time();
        return true;
    }
    
    /**
     * Check if current user is banned in database
     */
    private function checkUserBanned() {
        if (!$this->db || !isset($_SESSION['user_id'])) {
            return false;
        }
        
        $userId = intval($_SESSION['user_id']);
        
        $stmt = $this->db->prepare("SELECT status FROM users WHERE id = ?");
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $stmt->close();
            return ($row['status'] === 'banned');
        }
        
        $stmt->close();
        return false;
    }
    
    /**
     * Set user session after successful login
     */
    public function setUserSession($userId, $userName, $userEmail, $userRole) {
        // Regenerate session ID on login
        session_regenerate_id(true);
        
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_name'] = $userName;
        $_SESSION['user_email'] = $userEmail;
        $_SESSION['user_role'] = $userRole;
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
        $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
    }
    
    /**
     * Check if user is logged in
     */
    public function isLoggedIn() {
        return isset($_SESSION['logged_in']) && 
               $_SESSION['logged_in'] === true && 
               isset($_SESSION['user_id']);
    }
    
    /**
     * Get current user ID
     */
    public function getUserId() {
        return $_SESSION['user_id'] ?? null;
    }
    
    /**
     * Get current user role
     */
    public function getUserRole() {
        return $_SESSION['user_role'] ?? null;
    }
    
    /**
     * Get current user name
     */
    public function getUserName() {
        return $_SESSION['user_name'] ?? null;
    }
    
    /**
     * Get current user email
     */
    public function getUserEmail() {
        return $_SESSION['user_email'] ?? null;
    }
    
    /**
     * Check if user has specific role
     */
    public function hasRole($role) {
        return $this->isLoggedIn() && $this->getUserRole() === $role;
    }
    
    /**
     * Set session timeout (in seconds)
     */
    public function setSessionTimeout($seconds) {
        $this->sessionTimeout = $seconds;
    }
    
    /**
     * Destroy session (logout)
     */
    public function destroy() {
        $_SESSION = array();
        
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        session_destroy();
    }
    
    /**
     * Get all session data (for debugging - remove in production)
     */
    public function getSessionData() {
        return $_SESSION;
    }
    
    /**
     * Cleanup database connection
     */
    public function __destruct() {
        if ($this->db) {
            $this->db->close();
        }
    }
}
?>