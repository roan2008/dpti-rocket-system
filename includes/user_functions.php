<?php
/**
 * User Functions
 * Contains all user-related logic and database operations
 */

/**
 * Login user function
 * 
 * @param PDO $pdo Database connection
 * @param string $username Username
 * @param string $password Plain text password
 * @return bool True on successful login, false on failure
 */
function login_user($pdo, $username, $password) {
    try {
        // Prepare statement to get user by username
        $stmt = $pdo->prepare("SELECT user_id, username, password_hash, full_name, role FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        // Check if user exists
        if (!$user) {
            return false;
        }
        
        // Verify password
        if (!password_verify($password, $user['password_hash'])) {
            return false;
        }
        
        // Password is correct - set session variables
        // Session should already be started by the calling script
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['logged_in'] = true;
        
        return true;
        
    } catch (PDOException $e) {
        // Log error but don't expose details
        error_log("Login error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get user by ID
 * 
 * @param PDO $pdo Database connection
 * @param int $user_id User ID
 * @return array|false User data or false if not found
 */
function get_user_by_id($pdo, $user_id) {
    try {
        $stmt = $pdo->prepare("SELECT user_id, username, full_name, role, created_at FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Get user error: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if user is logged in
 * 
 * @return bool True if logged in, false otherwise
 */
function is_logged_in() {
    // Session should already be started by the calling script
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

/**
 * Check if user has specific role
 * 
 * @param string $required_role Required role (admin, engineer, staff)
 * @return bool True if user has role, false otherwise
 */
function has_role($required_role) {
    if (!is_logged_in()) {
        return false;
    }
    return isset($_SESSION['role']) && $_SESSION['role'] === $required_role;
}

/**
 * Logout user
 * 
 * @return void
 */
function logout_user() {
    // Session should already be started by the calling script
    
    // Destroy session
    $_SESSION = array();
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
}
?>
