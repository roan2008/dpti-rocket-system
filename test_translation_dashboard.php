<?php
/**
 * Test Translation System for Dashboard
 * Quick test to verify all translation keys work properly
 */

session_start();

// Include localization system
require_once 'includes/localization.php';

// Load Thai language
load_language('th');

echo "<h1>Testing Translation System</h1>";
echo "<h2>Navigation Tests:</h2>";
echo "<ul>";
echo "<li>Dashboard: " . t('nav_dashboard') . "</li>";
echo "<li>Production: " . t('nav_production') . "</li>";
echo "<li>Templates: " . t('nav_templates') . "</li>";
echo "<li>Approvals: " . t('nav_approvals') . "</li>";
echo "<li>Admin: " . t('nav_admin') . "</li>";
echo "<li>Logout: " . t('nav_logout') . "</li>";
echo "</ul>";

echo "<h2>Dashboard Tests:</h2>";
echo "<ul>";
echo "<li>Main Title: " . t('dashboard_main_title') . "</li>";
echo "<li>Description: " . t('dashboard_description') . "</li>";
echo "<li>Button Add New Rocket: " . t('btn_add_new_rocket') . "</li>";
echo "<li>Button Review Approvals: " . t('btn_review_approvals') . "</li>";
echo "</ul>";

echo "<h2>Statistics Tests:</h2>";
echo "<ul>";
echo "<li>Total Rockets: " . t('stat_total_rockets') . "</li>";
echo "<li>In Production: " . t('stat_in_production') . "</li>";
echo "<li>Completed: " . t('stat_completed') . "</li>";
echo "<li>Pending Approvals: " . t('stat_pending_approvals') . "</li>";
echo "</ul>";

echo "<h2>Table Headers Tests:</h2>";
echo "<ul>";
echo "<li>Serial Number: " . t('table_header_serial_number') . "</li>";
echo "<li>Project Name: " . t('table_header_project_name') . "</li>";
echo "<li>Current Status: " . t('table_header_current_status') . "</li>";
echo "<li>Created Date: " . t('table_header_created_date') . "</li>";
echo "<li>Actions: " . t('table_header_actions') . "</li>";
echo "</ul>";

echo "<h2>Buttons Tests:</h2>";
echo "<ul>";
echo "<li>View: " . t('btn_view') . "</li>";
echo "<li>Steps: " . t('btn_steps') . "</li>";
echo "<li>Edit: " . t('btn_edit') . "</li>";
echo "</ul>";

echo "<h2>Dynamic Replacement Test:</h2>";
echo "<ul>";
echo "<li>Rockets Count (5): " . t('rockets_count_display', ['count' => 5]) . "</li>";
echo "<li>Rockets Count (15): " . t('rockets_count_display', ['count' => 15]) . "</li>";
echo "</ul>";

echo "<h2>Error Messages Tests:</h2>";
echo "<ul>";
echo "<li>Success: " . t('success_message') . "</li>";
echo "<li>Error: " . t('error_message') . "</li>";
echo "<li>Rocket Created: " . t('rocket_created') . "</li>";
echo "<li>Rocket Deleted: " . t('rocket_deleted') . "</li>";
echo "<li>Invalid Action: " . t('error_invalid_action') . "</li>";
echo "</ul>";

echo "<h2>Roles Tests:</h2>";
echo "<ul>";
echo "<li>Admin: " . t('role_admin') . "</li>";
echo "<li>Engineer: " . t('role_engineer') . "</li>";
echo "<li>Staff: " . t('role_staff') . "</li>";
echo "</ul>";

echo "<h2>Page Title Tests:</h2>";
echo "<ul>";
echo "<li>Dashboard Title: " . t('dashboard_title') . "</li>";
echo "</ul>";

echo "<hr>";
echo "<p>Test completed. All translations should be in Thai.</p>";
?>
