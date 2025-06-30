<?php
/**
 * Test Rocket Detail Functionality
 * Tests the rocket detail view and edit operations
 */

// Start session and setup
session_start();

// Include required files
require_once '../includes/db_connect.php';
require_once '../includes/user_functions.php';
require_once '../includes/rocket_functions.php';

echo "=== ROCKET DETAIL FUNCTIONALITY TEST ===\n\n";

// Test 1: Get existing rockets to test with
echo "1. Checking for existing rockets:\n";
$rockets = get_all_rockets($pdo);
echo "   ✓ Found " . count($rockets) . " rockets in database\n";

if (empty($rockets)) {
    echo "   Creating test rocket for testing...\n";
    $test_rocket_id = create_rocket($pdo, "DETAIL-TEST-" . date('YmdHis'), "Detail Test Rocket", 'New');
    if ($test_rocket_id) {
        echo "   ✓ Test rocket created with ID: $test_rocket_id\n";
        $test_rocket = get_rocket_by_id($pdo, $test_rocket_id);
    } else {
        echo "   ✗ Failed to create test rocket\n";
        exit;
    }
} else {
    $test_rocket = $rockets[0];
    $test_rocket_id = $test_rocket['rocket_id'];
    echo "   ✓ Using existing rocket: " . $test_rocket['serial_number'] . " (ID: $test_rocket_id)\n";
}

// Test 2: Test get_rocket_by_id function
echo "\n2. Testing rocket retrieval by ID:\n";
$retrieved_rocket = get_rocket_by_id($pdo, $test_rocket_id);
if ($retrieved_rocket) {
    echo "   ✓ Successfully retrieved rocket:\n";
    echo "     - ID: " . $retrieved_rocket['rocket_id'] . "\n";
    echo "     - Serial: " . $retrieved_rocket['serial_number'] . "\n";
    echo "     - Project: " . $retrieved_rocket['project_name'] . "\n";
    echo "     - Status: " . $retrieved_rocket['current_status'] . "\n";
    echo "     - Created: " . $retrieved_rocket['created_at'] . "\n";
} else {
    echo "   ✗ Failed to retrieve rocket\n";
}

// Test 3: Test rocket update functionality
echo "\n3. Testing rocket update:\n";
$original_project_name = $retrieved_rocket['project_name'];
$new_project_name = "Updated Test Project " . date('H:i:s');

echo "   Updating project name from '$original_project_name' to '$new_project_name'\n";
$update_result = update_rocket($pdo, $test_rocket_id, $retrieved_rocket['serial_number'], $new_project_name, $retrieved_rocket['current_status']);

if ($update_result) {
    echo "   ✓ Rocket updated successfully\n";
    
    // Verify the update
    $updated_rocket = get_rocket_by_id($pdo, $test_rocket_id);
    if ($updated_rocket['project_name'] === $new_project_name) {
        echo "   ✓ Update verified - project name changed correctly\n";
        
        // Restore original name
        update_rocket($pdo, $test_rocket_id, $retrieved_rocket['serial_number'], $original_project_name, $retrieved_rocket['current_status']);
        echo "   ✓ Original project name restored\n";
    } else {
        echo "   ✗ Update verification failed\n";
    }
} else {
    echo "   ✗ Failed to update rocket\n";
}

// Test 4: Test status update functionality
echo "\n4. Testing status update:\n";
$original_status = $retrieved_rocket['current_status'];
$new_status = ($original_status === 'New') ? 'Planning' : 'New';

echo "   Updating status from '$original_status' to '$new_status'\n";
$status_update_result = update_rocket_status($pdo, $test_rocket_id, $new_status);

if ($status_update_result) {
    echo "   ✓ Status updated successfully\n";
    
    // Verify the status update
    $status_updated_rocket = get_rocket_by_id($pdo, $test_rocket_id);
    if ($status_updated_rocket['current_status'] === $new_status) {
        echo "   ✓ Status update verified\n";
        
        // Restore original status
        update_rocket_status($pdo, $test_rocket_id, $original_status);
        echo "   ✓ Original status restored\n";
    } else {
        echo "   ✗ Status update verification failed\n";
    }
} else {
    echo "   ✗ Failed to update status\n";
}

// Test 5: Check if rocket detail view file exists
echo "\n5. Checking rocket detail view file:\n";
$detail_view_path = '../views/rocket_detail_view.php';
if (file_exists($detail_view_path)) {
    echo "   ✓ Rocket detail view file exists\n";
    echo "   ✓ File size: " . filesize($detail_view_path) . " bytes\n";
} else {
    echo "   ✗ Rocket detail view file missing\n";
}

// Test 6: Test invalid rocket ID handling
echo "\n6. Testing invalid rocket ID handling:\n";
$invalid_rocket = get_rocket_by_id($pdo, 99999);
if ($invalid_rocket === false) {
    echo "   ✓ Invalid rocket ID correctly returns false\n";
} else {
    echo "   ✗ Invalid rocket ID should return false\n";
}

// Test 7: Clean up test data if we created it
if (isset($test_rocket_id) && $test_rocket['serial_number'] && strpos($test_rocket['serial_number'], 'DETAIL-TEST-') === 0) {
    echo "\n7. Cleaning up test data:\n";
    $delete_result = delete_rocket($pdo, $test_rocket_id);
    if ($delete_result) {
        echo "   ✓ Test rocket deleted successfully\n";
    } else {
        echo "   ✗ Failed to delete test rocket\n";
    }
}

echo "\n=== TEST COMPLETE ===\n";
echo "Rocket detail functionality is ready for use!\n";
echo "\nNEXT STEPS:\n";
echo "1. Visit http://localhost/dpti-rocket-system/dashboard.php\n";
echo "2. Click 'View' button on any rocket\n";
echo "3. Test the edit functionality (if admin/engineer)\n";
echo "4. Test the quick status update feature\n";
echo "5. Test the delete functionality (if admin)\n";
?>
