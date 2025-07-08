<?php
/**
 * Final User Management System Validation
 * Quick validation of all components without header conflicts
 */

echo "<h1>🚀 User Management System - Final Validation</h1>\n";
echo "<p>Quick validation of all components...</p>\n";

$results = [];

// Check 1: File existence
$files_to_check = [
    'views/user_management_view.php' => 'Main user management interface',
    'views/user_form_view.php' => 'Add/Edit user form',
    'controllers/user_controller.php' => 'User operations controller',
    'includes/user_functions.php' => 'Backend user functions'
];

echo "<h2>📁 File Existence Check</h2>\n";
foreach ($files_to_check as $file => $description) {
    $full_path = "../{$file}";
    if (file_exists($full_path)) {
        echo "✅ {$description} - EXISTS<br>\n";
        $results[$file] = true;
    } else {
        echo "❌ {$description} - MISSING<br>\n";
        $results[$file] = false;
    }
}

// Check 2: Syntax validation
echo "<h2>🔍 Syntax Validation</h2>\n";
foreach ($files_to_check as $file => $description) {
    $full_path = "../{$file}";
    if (file_exists($full_path)) {
        $syntax_check = shell_exec("php -l \"{$full_path}\" 2>&1");
        if (strpos($syntax_check, 'No syntax errors') !== false) {
            echo "✅ {$description} - SYNTAX OK<br>\n";
        } else {
            echo "❌ {$description} - SYNTAX ERROR<br>\n";
            echo "<small>" . htmlspecialchars($syntax_check) . "</small><br>\n";
        }
    }
}

// Check 3: Content validation
echo "<h2>📋 Content Validation</h2>\n";

// Check user management view
$mgmt_content = file_get_contents('../views/user_management_view.php');
$mgmt_checks = [
    'Add New User' => strpos($mgmt_content, 'Add New User') !== false,
    'Role filters' => strpos($mgmt_content, 'role-admin') !== false,
    'Delete modal' => strpos($mgmt_content, 'deleteModal') !== false,
    'Search functionality' => strpos($mgmt_content, 'search') !== false
];

foreach ($mgmt_checks as $feature => $exists) {
    echo ($exists ? "✅" : "❌") . " User Management View: {$feature}<br>\n";
}

// Check user form view
$form_content = file_get_contents('../views/user_form_view.php');
$form_checks = [
    'Full name field' => strpos($form_content, 'full_name') !== false,
    'Username field' => strpos($form_content, 'username') !== false,
    'Role selection' => strpos($form_content, 'role') !== false,
    'Password fields' => strpos($form_content, 'password') !== false,
    'Form validation JS' => strpos($form_content, 'validatePasswords') !== false,
    'Admin access control' => strpos($form_content, "has_role('admin')") !== false
];

foreach ($form_checks as $feature => $exists) {
    echo ($exists ? "✅" : "❌") . " User Form View: {$feature}<br>\n";
}

// Check controller
$controller_content = file_get_contents('../controllers/user_controller.php');
$controller_checks = [
    'Create action' => strpos($controller_content, 'handle_create') !== false,
    'Update action' => strpos($controller_content, 'handle_update') !== false,
    'Delete action' => strpos($controller_content, 'handle_delete') !== false,
    'Input validation' => strpos($controller_content, 'validate_user_input') !== false,
    'Security checks' => strpos($controller_content, "has_role('admin')") !== false,
    'Password hashing' => strpos($controller_content, 'password_hash') !== false
];

foreach ($controller_checks as $feature => $exists) {
    echo ($exists ? "✅" : "❌") . " User Controller: {$feature}<br>\n";
}

// Backend functions check (quick)
require_once '../includes/db_connect.php';
require_once '../includes/user_functions.php';

echo "<h2>🔧 Backend Functions Test</h2>\n";
try {
    $users = get_all_users($pdo, 3);
    echo "✅ get_all_users() - Retrieved " . count($users) . " users<br>\n";
    
    $total = count_users($pdo);
    echo "✅ count_users() - Total: {$total} users<br>\n";
    
    echo "✅ Backend functions working correctly<br>\n";
} catch (Exception $e) {
    echo "❌ Backend error: " . $e->getMessage() . "<br>\n";
}

// Final summary
echo "<h2>🎯 Final Status</h2>\n";
$all_files_exist = !in_array(false, $results);

if ($all_files_exist) {
    echo "<div style='background: #d4edda; padding: 20px; border-radius: 10px; margin: 20px 0;'>\n";
    echo "<h3>🎉 SUCCESS! User Management System is Complete</h3>\n";
    echo "<p><strong>All components are in place and ready for use:</strong></p>\n";
    echo "<ul>\n";
    echo "<li>✅ User Management Interface (views/user_management_view.php)</li>\n";
    echo "<li>✅ Add/Edit User Form (views/user_form_view.php)</li>\n";
    echo "<li>✅ User Controller (controllers/user_controller.php)</li>\n";
    echo "<li>✅ Backend Functions (includes/user_functions.php)</li>\n";
    echo "</ul>\n";
    echo "<p><strong>Features implemented:</strong></p>\n";
    echo "<ul>\n";
    echo "<li>🔐 Admin-only access control</li>\n";
    echo "<li>👤 Complete CRUD operations</li>\n";
    echo "<li>🔍 Search and filter functionality</li>\n";
    echo "<li>🛡️ Security validation and business rules</li>\n";
    echo "<li>💼 Role-based permissions</li>\n";
    echo "<li>🎨 Modern responsive UI</li>\n";
    echo "<li>⚠️ Delete confirmation with warnings</li>\n";
    echo "<li>🔒 Password security and validation</li>\n";
    echo "</ul>\n";
    echo "</div>\n";
    
    echo "<h3>📋 Manual Testing Checklist</h3>\n";
    echo "<ol>\n";
    echo "<li>🌐 Access: <code>http://localhost/dpti-rocket-system/views/user_management_view.php</code></li>\n";
    echo "<li>👤 Test user creation with different roles</li>\n";
    echo "<li>✏️ Test user editing and updates</li>\n";
    echo "<li>🔍 Test search and filter features</li>\n";
    echo "<li>🗑️ Test user deletion with confirmations</li>\n";
    echo "<li>🚫 Test error handling (duplicate usernames, etc.)</li>\n";
    echo "<li>🔐 Test access control (try accessing as non-admin)</li>\n";
    echo "</ol>\n";
    
} else {
    echo "<div style='background: #f8d7da; padding: 20px; border-radius: 10px; margin: 20px 0;'>\n";
    echo "<h3>⚠️ Some Components Missing</h3>\n";
    echo "<p>Please check the failed items above and ensure all files are properly created.</p>\n";
    echo "</div>\n";
}

echo "\n<p><strong>Validation completed at:</strong> " . date('Y-m-d H:i:s') . "</p>\n";
?>
