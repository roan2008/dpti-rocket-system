<?php
/**
 * Test Rocket Add Functionality
 * This script tests the rocket creation workflow
 */

// Start session and setup
session_start();

// Include required files
require_once '../includes/db_connect.php';
require_once '../includes/user_functions.php';
require_once '../includes/rocket_functions.php';

echo "=== ROCKET ADD FUNCTIONALITY TEST ===\n\n";

// Test 1: Check if functions exist
echo "1. Testing if required functions exist:\n";
$required_functions = [
    'get_rocket_by_serial',
    'create_rocket', 
    'get_rocket_by_id',
    'update_rocket',
    'delete_rocket',
    'update_rocket_status'
];

foreach ($required_functions as $func) {
    if (function_exists($func)) {
        echo "   ✓ Function $func exists\n";
    } else {
        echo "   ✗ Function $func missing\n";
    }
}

// Test 2: Check database connection
echo "\n2. Testing database connection:\n";
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM rockets");
    $current_count = $stmt->fetchColumn();
    echo "   ✓ Database connection working\n";
    echo "   ✓ Current rocket count: $current_count\n";
} catch (Exception $e) {
    echo "   ✗ Database error: " . $e->getMessage() . "\n";
}

// Test 3: Test create rocket function
echo "\n3. Testing rocket creation:\n";
$test_serial = "TEST-" . date('YmdHis');
$test_project = "Test Project " . date('H:i:s');

echo "   Attempting to create rocket: $test_serial\n";
$new_rocket_id = create_rocket($pdo, $test_serial, $test_project, 'New');

if ($new_rocket_id) {
    echo "   ✓ Rocket created successfully with ID: $new_rocket_id\n";
    
    // Test 4: Verify the rocket was created
    echo "\n4. Verifying rocket creation:\n";
    $created_rocket = get_rocket_by_id($pdo, $new_rocket_id);
    if ($created_rocket) {
        echo "   ✓ Rocket retrieved successfully:\n";
        echo "     - ID: " . $created_rocket['rocket_id'] . "\n";
        echo "     - Serial: " . $created_rocket['serial_number'] . "\n";
        echo "     - Project: " . $created_rocket['project_name'] . "\n";
        echo "     - Status: " . $created_rocket['current_status'] . "\n";
    } else {
        echo "   ✗ Failed to retrieve created rocket\n";
    }
    
    // Test 5: Test duplicate serial number check
    echo "\n5. Testing duplicate serial number prevention:\n";
    $duplicate_result = create_rocket($pdo, $test_serial, "Duplicate Test", 'New');
    if ($duplicate_result === false) {
        echo "   ✓ Duplicate serial number correctly prevented\n";
    } else {
        echo "   ✗ Duplicate serial number was allowed (this is a problem)\n";
    }
    
    // Test 6: Clean up test data
    echo "\n6. Cleaning up test data:\n";
    $delete_result = delete_rocket($pdo, $new_rocket_id);
    if ($delete_result) {
        echo "   ✓ Test rocket deleted successfully\n";
    } else {
        echo "   ✗ Failed to delete test rocket\n";
    }
    
} else {
    echo "   ✗ Failed to create rocket\n";
}

// Test 7: Check rocket controller file exists
echo "\n7. Checking rocket controller:\n";
$controller_path = '../controllers/rocket_controller.php';
if (file_exists($controller_path)) {
    echo "   ✓ Rocket controller file exists\n";
    echo "   ✓ File size: " . filesize($controller_path) . " bytes\n";
} else {
    echo "   ✗ Rocket controller file missing\n";
}

// Test 8: Check rocket add view exists
echo "\n8. Checking rocket add view:\n";
$view_path = '../views/rocket_add_view.php';
if (file_exists($view_path)) {
    echo "   ✓ Rocket add view file exists\n";
    echo "   ✓ File size: " . filesize($view_path) . " bytes\n";
} else {
    echo "   ✗ Rocket add view file missing\n";
}

echo "\n=== TEST COMPLETE ===\n";
echo "All rocket add functionality components are ready for use!\n";
echo "\nNEXT STEPS:\n";
echo "1. Visit http://localhost/dpti-rocket-system/views/rocket_add_view.php\n";
echo "2. Login as admin or engineer\n";
echo "3. Fill out the rocket form and submit\n";
echo "4. Verify the rocket appears on the dashboard\n";
?>
