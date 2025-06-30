<?php
/**
 * Web-based Login Test Page
 * Access this page in browser to test login functionality
 */

// Include required files
require_once '../includes/db_connect.php';
require_once '../includes/user_functions.php';

// Handle login test
if ($_POST && isset($_POST['test_login'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (!empty($username) && !empty($password)) {
        $login_result = login_user($pdo, $username, $password);
        if ($login_result) {
            $success_message = "Login successful! Session data: " . print_r($_SESSION, true);
        } else {
            $error_message = "Login failed! Check credentials.";
        }
    } else {
        $error_message = "Please fill in both username and password.";
    }
}

// Check current session status
$session_info = [
    'Session Status' => session_status(),
    'Session ID' => session_id(),
    'Is Logged In' => is_logged_in() ? 'YES' : 'NO',
    'Session Data' => $_SESSION ?? []
];

?>
<!DOCTYPE html>
<html>
<head>
    <title>Login Test - DPTI Rocket System</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        .form-group { margin: 10px 0; }
        .form-group label { display: block; margin-bottom: 5px; }
        .form-group input { padding: 8px; width: 200px; }
        .btn { padding: 10px 20px; background: #007cba; color: white; border: none; cursor: pointer; }
        .success { background: #d4edda; color: #155724; padding: 10px; margin: 10px 0; border-radius: 4px; }
        .error { background: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0; border-radius: 4px; }
        .info { background: #d1ecf1; color: #0c5460; padding: 10px; margin: 10px 0; border-radius: 4px; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>DPTI Rocket System - Login Test</h1>
        
        <?php if (isset($success_message)): ?>
            <div class="success"><?php echo htmlspecialchars($success_message); ?></div>
            <p><a href="../dashboard.php">Go to Dashboard</a></p>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        
        <div class="info">
            <h3>Current Session Status:</h3>
            <pre><?php echo htmlspecialchars(print_r($session_info, true)); ?></pre>
        </div>
        
        <h3>Test Login:</h3>
        <form method="POST">
            <div class="form-group">
                <label>Username:</label>
                <input type="text" name="username" value="admin" required>
            </div>
            <div class="form-group">
                <label>Password:</label>
                <input type="password" name="password" value="admin123" required>
            </div>
            <div class="form-group">
                <button type="submit" name="test_login" class="btn">Test Login</button>
            </div>
        </form>
        
        <h3>Quick Links:</h3>
        <ul>
            <li><a href="../views/login_view.php">Official Login Page</a></li>
            <li><a href="../dashboard.php">Dashboard</a></li>
            <li><a href="../controllers/logout_controller.php">Logout</a></li>
        </ul>
        
        <h3>Database Users:</h3>
        <?php
        try {
            $stmt = $pdo->prepare("SELECT user_id, username, full_name, role FROM users");
            $stmt->execute();
            $users = $stmt->fetchAll();
            echo "<pre>";
            foreach ($users as $user) {
                echo "ID: {$user['user_id']}, Username: '{$user['username']}', Name: '{$user['full_name']}', Role: '{$user['role']}'\n";
            }
            echo "</pre>";
        } catch (Exception $e) {
            echo "<div class='error'>Error fetching users: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
        ?>
    </div>
</body>
</html>
