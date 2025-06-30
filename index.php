<?php
/**
 * Index Page - Entry point for DPTI Rocket System
 * Redirects users to appropriate page based on login status
 */

// Include required files
require_once 'includes/user_functions.php';

// Check if user is already logged in
if (is_logged_in()) {
    // Redirect to dashboard
    header('Location: dashboard.php');
    exit;
} else {
    // Redirect to login page
    header('Location: views/login_view.php');
    exit;
}
?>
