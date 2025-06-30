<?php
/**
 * Login Controller
 * Handles user login form submission
 */

// Start session first
session_start();

// Include required files
require_once '../includes/db_connect.php';
require_once '../includes/user_functions.php';

// Only process POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../views/login_view.php?error=method_not_allowed');
    exit;
}

// Get form data
$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

// Validate required fields
if (empty($username) || empty($password)) {
    header('Location: ../views/login_view.php?error=missing_fields');
    exit;
}

// Attempt login
$login_result = login_user($pdo, $username, $password);

if ($login_result) {
    // Login successful - redirect to dashboard
    header('Location: ../dashboard.php');
    exit;
} else {
    // Login failed - redirect back to login with error
    header('Location: ../views/login_view.php?error=invalid_credentials');
    exit;
}
?>
