<?php
/**
 * Logout Controller
 * Handles user logout
 */

// Start session first
session_start();

// Include required files
require_once '../includes/user_functions.php';

// Logout user
logout_user();

// Redirect to login page
header('Location: ../views/login_view.php');
exit;
?>
