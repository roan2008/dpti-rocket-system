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

/**
 * Get all users with pagination support
 * 
 * @param PDO $pdo Database connection
 * @param int $limit Maximum number of users to return (default: 50)
 * @param int $offset Number of users to skip (default: 0)
 * @return array Array of user data
 */
function get_all_users($pdo, $limit = 50, $offset = 0) {
    try {
        // Use string concatenation for LIMIT/OFFSET to avoid MySQL issues with prepared statements
        $sql = "SELECT user_id, username, full_name, role, created_at 
                FROM users 
                ORDER BY created_at DESC 
                LIMIT " . intval($limit) . " OFFSET " . intval($offset);
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Get all users error: " . $e->getMessage());
        return [];
    }
}

/**
 * Count total number of users
 * 
 * @param PDO $pdo Database connection
 * @return int Total user count
 */
function count_users($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users");
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Count users error: " . $e->getMessage());
        return 0;
    }
}

/**
 * Count total number of admin users
 * 
 * @param PDO $pdo Database connection
 * @return int Total admin user count
 */
function countAdmins($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin'");
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Count admins error: " . $e->getMessage());
        return 0;
    }
}

/**
 * Create a new user
 * 
 * @param PDO $pdo Database connection
 * @param string $username Username (must be unique)
 * @param string $password Plain text password (will be hashed)
 * @param string $full_name Full name of the user
 * @param string $role User role (admin, engineer, staff)
 * @return array Result array with 'success' boolean and 'message' string, plus 'user_id' if successful
 */
function create_user($pdo, $username, $password, $full_name, $role) {
    try {
        // Validate input
        if (empty($username) || empty($password) || empty($full_name) || empty($role)) {
            return ['success' => false, 'message' => 'All fields are required'];
        }
        
        // Validate role
        $valid_roles = ['admin', 'engineer', 'staff'];
        if (!in_array($role, $valid_roles)) {
            return ['success' => false, 'message' => 'Invalid role specified'];
        }
        
        // Validate username format (alphanumeric and underscores only)
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            return ['success' => false, 'message' => 'Username can only contain letters, numbers, and underscores'];
        }
        
        // Validate password strength
        if (strlen($password) < 8) {
            return ['success' => false, 'message' => 'Password must be at least 8 characters long'];
        }
        
        // Check if username already exists
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Username already exists'];
        }
        
        // Hash password
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert new user
        $stmt = $pdo->prepare("
            INSERT INTO users (username, password_hash, full_name, role, created_at) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$username, $password_hash, $full_name, $role]);
        
        $user_id = $pdo->lastInsertId();
        
        return ['success' => true, 'message' => 'User created successfully', 'user_id' => $user_id];
        
    } catch (PDOException $e) {
        error_log("Create user error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Database error occurred'];
    }
}

/**
 * Update user information
 * 
 * @param PDO $pdo Database connection
 * @param int $user_id User ID to update
 * @param string $username New username (must be unique)
 * @param string $full_name New full name
 * @param string $role New role
 * @param string|null $password New password (optional, null to keep existing)
 * @return array Result array with 'success' boolean and 'message' string
 */
function update_user($pdo, $user_id, $username, $full_name, $role, $password = null) {
    try {
        // Validate input
        if (empty($user_id) || empty($username) || empty($full_name) || empty($role)) {
            return ['success' => false, 'message' => 'All required fields must be provided'];
        }
        
        // Validate role
        $valid_roles = ['admin', 'engineer', 'staff'];
        if (!in_array($role, $valid_roles)) {
            return ['success' => false, 'message' => 'Invalid role specified'];
        }
        
        // Validate username format
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            return ['success' => false, 'message' => 'Username can only contain letters, numbers, and underscores'];
        }
        
        // Check if user exists
        $stmt = $pdo->prepare("SELECT user_id, role FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $current_user = $stmt->fetch();
        if (!$current_user) {
            return ['success' => false, 'message' => 'User not found'];
        }
        
        // Critical Business Logic: Prevent self-lockout scenario
        // If the user being edited is currently an admin AND the new role is NOT admin
        if ($current_user['role'] === 'admin' && $role !== 'admin') {
            $admin_count = countAdmins($pdo);
            if ($admin_count <= 1) {
                return ['success' => false, 'message' => 'Cannot change the role of the last administrator'];
            }
        }
        
        // Check if username is taken by another user
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ? AND user_id != ?");
        $stmt->execute([$username, $user_id]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Username already exists'];
        }
        
        // Prepare update query
        if ($password !== null) {
            // Validate password strength if provided
            if (strlen($password) < 8) {
                return ['success' => false, 'message' => 'Password must be at least 8 characters long'];
            }
            
            // Update with password (no updated_at column in schema)
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("
                UPDATE users 
                SET username = ?, password_hash = ?, full_name = ?, role = ? 
                WHERE user_id = ?
            ");
            $stmt->execute([$username, $password_hash, $full_name, $role, $user_id]);
        } else {
            // Update without password (no updated_at column in schema)
            $stmt = $pdo->prepare("
                UPDATE users 
                SET username = ?, full_name = ?, role = ? 
                WHERE user_id = ?
            ");
            $stmt->execute([$username, $full_name, $role, $user_id]);
        }
        
        return ['success' => true, 'message' => 'User updated successfully'];
        
    } catch (PDOException $e) {
        error_log("Update user error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Database error occurred'];
    }
}

/**
 * Delete a user (with business logic protection)
 * 
 * @param PDO $pdo Database connection
 * @param int $user_id User ID to delete
 * @param int $current_user_id Current logged-in user ID (to prevent self-deletion)
 * @return array Result array with 'success' boolean and 'message' string
 */
function delete_user($pdo, $user_id, $current_user_id) {
    try {
        // Validate input
        if (empty($user_id) || empty($current_user_id)) {
            return ['success' => false, 'message' => 'Invalid user ID provided'];
        }
        
        // Business logic: Prevent admin from deleting their own account
        if ($user_id == $current_user_id) {
            return ['success' => false, 'message' => 'You cannot delete your own account'];
        }
        
        // Check if user exists
        $stmt = $pdo->prepare("SELECT user_id, username, role FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return ['success' => false, 'message' => 'User not found'];
        }
        
        // Business logic: Prevent deletion of the last admin
        if ($user['role'] === 'admin') {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin'");
            $stmt->execute();
            $admin_count = (int) $stmt->fetchColumn();
            
            if ($admin_count <= 1) {
                return ['success' => false, 'message' => 'Cannot delete the last admin account'];
            }
        }
        
        // Check for related data (optional: prevent deletion if user has created production steps)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM production_steps WHERE staff_id = ?");
        $stmt->execute([$user_id]);
        $step_count = (int) $stmt->fetchColumn();
        
        if ($step_count > 0) {
            return ['success' => false, 'message' => "Cannot delete user: they have $step_count production step(s) recorded"];
        }
        
        // Perform deletion
        $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        
        return ['success' => true, 'message' => "User '{$user['username']}' deleted successfully"];
        
    } catch (PDOException $e) {
        error_log("Delete user error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Database error occurred'];
    }
}

/**
 * Get user by username
 * 
 * @param PDO $pdo Database connection
 * @param string $username Username to search for
 * @return array|false User data or false if not found
 */
function get_user_by_username($pdo, $username) {
    try {
        $stmt = $pdo->prepare("SELECT user_id, username, full_name, role, created_at FROM users WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Get user by username error: " . $e->getMessage());
        return false;
    }
}

/**
 * Search users by name or username
 * 
 * @param PDO $pdo Database connection
 * @param string $search_term Search term to match against username or full_name
 * @param int $limit Maximum number of results (default: 20)
 * @return array Array of matching users
 */
function search_users($pdo, $search_term, $limit = 20) {
    try {
        $search_pattern = '%' . $search_term . '%';
        // Use string concatenation for LIMIT to avoid MySQL issues
        $sql = "SELECT user_id, username, full_name, role, created_at 
                FROM users 
                WHERE username LIKE ? OR full_name LIKE ? 
                ORDER BY username 
                LIMIT " . intval($limit);
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$search_pattern, $search_pattern]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Search users error: " . $e->getMessage());
        return [];
    }
}

/**
 * Get users by role
 * 
 * @param PDO $pdo Database connection
 * @param string $role Role to filter by (admin, engineer, staff)
 * @return array Array of users with the specified role
 */
function get_users_by_role($pdo, $role) {
    try {
        $stmt = $pdo->prepare("
            SELECT user_id, username, full_name, role, created_at 
            FROM users 
            WHERE role = ? 
            ORDER BY full_name
        ");
        $stmt->execute([$role]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Get users by role error: " . $e->getMessage());
        return [];
    }
}
?>
