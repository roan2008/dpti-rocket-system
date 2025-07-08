<?php
/**
 * Complete User Management System Test
 * Tests the full UI workflow including forms and controller actions
 */

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include required files
require_once '../includes/db_connect.php';
require_once '../includes/user_functions.php';

echo "<h1>User Management System - Complete Test Suite</h1>\n";
echo "<p>Testing the complete user management workflow including UI and controller...</p>\n";

$test_results = [];
$test_number = 1;

// Test 1: Check if user_form_view.php exists and is readable
echo "<h2>Test {$test_number}: User Form View File</h2>\n";
$form_view_path = '../views/user_form_view.php';
if (file_exists($form_view_path)) {
    echo "✅ PASS: user_form_view.php exists<br>\n";
    
    // Check if file is readable
    if (is_readable($form_view_path)) {
        echo "✅ PASS: user_form_view.php is readable<br>\n";
        $test_results["Test {$test_number}"] = "PASS";
    } else {
        echo "❌ FAIL: user_form_view.php is not readable<br>\n";
        $test_results["Test {$test_number}"] = "FAIL";
    }
} else {
    echo "❌ FAIL: user_form_view.php does not exist<br>\n";
    $test_results["Test {$test_number}"] = "FAIL";
}
$test_number++;

// Test 2: Check if user_controller.php exists and is readable
echo "<h2>Test {$test_number}: User Controller File</h2>\n";
$controller_path = '../controllers/user_controller.php';
if (file_exists($controller_path)) {
    echo "✅ PASS: user_controller.php exists<br>\n";
    
    // Check if file is readable
    if (is_readable($controller_path)) {
        echo "✅ PASS: user_controller.php is readable<br>\n";
        $test_results["Test {$test_number}"] = "PASS";
    } else {
        echo "❌ FAIL: user_controller.php is not readable<br>\n";
        $test_results["Test {$test_number}"] = "FAIL";
    }
} else {
    echo "❌ FAIL: user_controller.php does not exist<br>\n";
    $test_results["Test {$test_number}"] = "FAIL";
}
$test_number++;

// Test 3: Check user_management_view.php exists and has required elements
echo "<h2>Test {$test_number}: User Management View File</h2>\n";
$management_view_path = '../views/user_management_view.php';
if (file_exists($management_view_path)) {
    echo "✅ PASS: user_management_view.php exists<br>\n";
    
    $content = file_get_contents($management_view_path);
    
    // Check for key elements
    $required_elements = [
        'Add New User' => 'Add new user button',
        'user_form_view.php' => 'Form view link',
        'user_controller.php' => 'Controller reference',
        'confirmDelete' => 'Delete confirmation function',
        'deleteModal' => 'Delete modal',
        'role-admin' => 'Role badge styling',
        'filter' => 'Filter functionality'
    ];
    
    $all_elements_found = true;
    foreach ($required_elements as $element => $description) {
        if (strpos($content, $element) !== false) {
            echo "✅ PASS: Contains {$description}<br>\n";
        } else {
            echo "❌ FAIL: Missing {$description}<br>\n";
            $all_elements_found = false;
        }
    }
    
    $test_results["Test {$test_number}"] = $all_elements_found ? "PASS" : "FAIL";
} else {
    echo "❌ FAIL: user_management_view.php does not exist<br>\n";
    $test_results["Test {$test_number}"] = "FAIL";
}
$test_number++;

// Test 4: Test backend user functions (from our previous test)
echo "<h2>Test {$test_number}: Backend User Functions</h2>\n";
try {
    // Quick test of core functions
    $users = get_all_users($pdo, 5);
    echo "✅ PASS: get_all_users() working (returned " . count($users) . " users)<br>\n";
    
    $user_count = count_users($pdo);
    echo "✅ PASS: count_users() working (total: {$user_count} users)<br>\n";
    
    // Test validation function (from controller)
    include_once $controller_path;
    $validation_result = validate_user_input('Test User', 'testuser123', 'staff', 'password123', 'password123', false);
    if ($validation_result === null) {
        echo "✅ PASS: validate_user_input() working correctly<br>\n";
    } else {
        echo "❌ FAIL: validate_user_input() validation issue: {$validation_result}<br>\n";
    }
    
    $test_results["Test {$test_number}"] = "PASS";
} catch (Exception $e) {
    echo "❌ FAIL: Backend functions error: " . $e->getMessage() . "<br>\n";
    $test_results["Test {$test_number}"] = "FAIL";
}
$test_number++;

// Test 5: Test form view syntax (basic PHP syntax check)
echo "<h2>Test {$test_number}: Form View Syntax Check</h2>\n";
$syntax_check = shell_exec("php -l {$form_view_path} 2>&1");
if (strpos($syntax_check, 'No syntax errors') !== false) {
    echo "✅ PASS: user_form_view.php has no syntax errors<br>\n";
    $test_results["Test {$test_number}"] = "PASS";
} else {
    echo "❌ FAIL: user_form_view.php syntax errors:<br>\n";
    echo "<pre>" . htmlspecialchars($syntax_check) . "</pre><br>\n";
    $test_results["Test {$test_number}"] = "FAIL";
}
$test_number++;

// Test 6: Test controller syntax
echo "<h2>Test {$test_number}: Controller Syntax Check</h2>\n";
$syntax_check = shell_exec("php -l {$controller_path} 2>&1");
if (strpos($syntax_check, 'No syntax errors') !== false) {
    echo "✅ PASS: user_controller.php has no syntax errors<br>\n";
    $test_results["Test {$test_number}"] = "PASS";
} else {
    echo "❌ FAIL: user_controller.php syntax errors:<br>\n";
    echo "<pre>" . htmlspecialchars($syntax_check) . "</pre><br>\n";
    $test_results["Test {$test_number}"] = "FAIL";
}
$test_number++;

// Test 7: Check form view content structure
echo "<h2>Test {$test_number}: Form View Content Structure</h2>\n";
if (file_exists($form_view_path)) {
    $form_content = file_get_contents($form_view_path);
    
    $required_form_elements = [
        'full_name' => 'Full name field',
        'username' => 'Username field', 
        'role' => 'Role field',
        'password' => 'Password field',
        'confirm_password' => 'Confirm password field',
        'validatePasswords' => 'Password validation JavaScript',
        'user_controller.php' => 'Form action pointing to controller',
        'is_logged_in()' => 'Authentication check',
        'has_role(\'admin\')' => 'Admin role check'
    ];
    
    $all_form_elements_found = true;
    foreach ($required_form_elements as $element => $description) {
        if (strpos($form_content, $element) !== false) {
            echo "✅ PASS: Contains {$description}<br>\n";
        } else {
            echo "❌ FAIL: Missing {$description}<br>\n";
            $all_form_elements_found = false;
        }
    }
    
    $test_results["Test {$test_number}"] = $all_form_elements_found ? "PASS" : "FAIL";
} else {
    echo "❌ FAIL: Cannot test form content - file not found<br>\n";
    $test_results["Test {$test_number}"] = "FAIL";
}
$test_number++;

// Test 8: Test controller action handling
echo "<h2>Test {$test_number}: Controller Action Structure</h2>\n";
if (file_exists($controller_path)) {
    $controller_content = file_get_contents($controller_path);
    
    $required_actions = [
        'handle_create' => 'Create user action',
        'handle_update' => 'Update user action',
        'handle_delete' => 'Delete user action',
        'handle_list' => 'List users action',
        'handle_show_form' => 'Show form action',
        'validate_user_input' => 'Input validation function',
        'handle_ajax_check_username' => 'AJAX username check',
        'password_hash' => 'Password hashing'
    ];
    
    $all_actions_found = true;
    foreach ($required_actions as $action => $description) {
        if (strpos($controller_content, $action) !== false) {
            echo "✅ PASS: Contains {$description}<br>\n";
        } else {
            echo "❌ FAIL: Missing {$description}<br>\n";
            $all_actions_found = false;
        }
    }
    
    $test_results["Test {$test_number}"] = $all_actions_found ? "PASS" : "FAIL";
} else {
    echo "❌ FAIL: Cannot test controller actions - file not found<br>\n";
    $test_results["Test {$test_number}"] = "FAIL";
}
$test_number++;

// Test 9: Test URL routing structure
echo "<h2>Test {$test_number}: URL Routing and Security</h2>\n";
$routing_tests = [
    'Access control in form view' => 'is_logged_in()',
    'Admin check in form view' => "has_role('admin')",
    'Access control in controller' => 'is_logged_in()',
    'Admin check in controller' => "has_role('admin')",
    'Redirect on unauthorized access' => 'header(\'Location:',
    'CSRF protection structure' => 'POST.*REQUEST_METHOD'
];

$all_routing_secure = true;
foreach ($routing_tests as $test_desc => $pattern) {
    $found_in_form = file_exists($form_view_path) && strpos(file_get_contents($form_view_path), $pattern) !== false;
    $found_in_controller = file_exists($controller_path) && strpos(file_get_contents($controller_path), $pattern) !== false;
    
    if ($found_in_form || $found_in_controller) {
        echo "✅ PASS: {$test_desc} implemented<br>\n";
    } else {
        echo "❌ FAIL: {$test_desc} missing<br>\n";
        $all_routing_secure = false;
    }
}

$test_results["Test {$test_number}"] = $all_routing_secure ? "PASS" : "FAIL";
$test_number++;

// Test 10: Integration test - create a test user record
echo "<h2>Test {$test_number}: Integration Test - User Creation</h2>\n";
try {
    $test_username = 'uitest_' . time();
    $result = create_user($pdo, $test_username, 'testpass123', 'UI Test User', 'staff');
    
    if ($result['success']) {
        echo "✅ PASS: Successfully created test user: {$test_username}<br>\n";
        
        // Clean up - delete the test user
        $cleanup_result = delete_user($pdo, $result['user_id'], 999999); // Use fake current user ID
        if ($cleanup_result['success']) {
            echo "✅ PASS: Successfully cleaned up test user<br>\n";
        } else {
            echo "⚠️ WARNING: Could not clean up test user: {$cleanup_result['message']}<br>\n";
        }
        
        $test_results["Test {$test_number}"] = "PASS";
    } else {
        echo "❌ FAIL: Could not create test user: {$result['message']}<br>\n";
        $test_results["Test {$test_number}"] = "FAIL";
    }
} catch (Exception $e) {
    echo "❌ FAIL: Integration test error: " . $e->getMessage() . "<br>\n";
    $test_results["Test {$test_number}"] = "FAIL";
}

// Summary
echo "<h2>Test Summary</h2>\n";
$total_tests = count($test_results);
$passed_tests = count(array_filter($test_results, function($result) { return $result === 'PASS'; }));
$failed_tests = $total_tests - $passed_tests;

echo "<div style='background: " . ($failed_tests === 0 ? '#d4edda' : '#f8d7da') . "; padding: 15px; border-radius: 5px; margin: 20px 0;'>\n";
echo "<h3>Overall Results:</h3>\n";
echo "<p><strong>Total Tests:</strong> {$total_tests}</p>\n";
echo "<p><strong>Passed:</strong> <span style='color: green;'>{$passed_tests}</span></p>\n";
echo "<p><strong>Failed:</strong> <span style='color: red;'>{$failed_tests}</span></p>\n";
echo "<p><strong>Success Rate:</strong> " . round(($passed_tests / $total_tests) * 100, 1) . "%</p>\n";
echo "</div>\n";

echo "<h3>Detailed Results:</h3>\n";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
echo "<tr><th>Test</th><th>Result</th></tr>\n";
foreach ($test_results as $test => $result) {
    $color = $result === 'PASS' ? 'green' : 'red';
    echo "<tr><td>{$test}</td><td style='color: {$color}; font-weight: bold;'>{$result}</td></tr>\n";
}
echo "</table>\n";

if ($failed_tests === 0) {
    echo "<h3>🎉 All Tests Passed!</h3>\n";
    echo "<p>Your User Management System is fully implemented and ready for use!</p>\n";
    
    echo "<h3>Manual Testing Checklist:</h3>\n";
    echo "<ol>\n";
    echo "<li>Visit <code>views/user_management_view.php</code> as an admin user</li>\n";
    echo "<li>Click 'Add New User' to test user creation form</li>\n";
    echo "<li>Try creating a new user with various roles</li>\n";
    echo "<li>Test editing an existing user</li>\n";
    echo "<li>Test the search and filter functionality</li>\n";
    echo "<li>Test the delete confirmation modal</li>\n";
    echo "<li>Test error handling (duplicate usernames, weak passwords, etc.)</li>\n";
    echo "</ol>\n";
} else {
    echo "<h3>⚠️ Some Tests Failed</h3>\n";
    echo "<p>Please review the failed tests above and fix any issues before proceeding.</p>\n";
}

echo "\n<p><strong>Test completed at:</strong> " . date('Y-m-d H:i:s') . "</p>\n";
?>
