<?php
/**
 * Command Line Test Script for Rocket Functions
 * This script tests the rocket-related functions
 */

// Include required files
require_once '../includes/db_connect.php';
require_once '../includes/rocket_functions.php';

echo "=== DPTI Rocket System - Rocket Functions Test ===\n";
echo "Starting tests...\n\n";

// Test data
$test_serial = 'TEST-' . time();
$test_project = 'Test Project ' . time();
$test_status = 'Testing';

try {
    // Test 1: Create rocket
    echo "1. Test: Create new rocket\n";
    $rocket_id = create_rocket($pdo, $test_serial, $test_project, $test_status);
    
    if ($rocket_id) {
        echo "   PASS - Rocket created with ID: $rocket_id\n";
    } else {
        echo "   FAIL - Could not create rocket\n";
        exit(1);
    }
    
    echo "\n";
    
    // Test 2: Get rocket by ID
    echo "2. Test: Get rocket by ID\n";
    $rocket = get_rocket_by_id($pdo, $rocket_id);
    
    if ($rocket && $rocket['serial_number'] === $test_serial) {
        echo "   PASS - Retrieved rocket by ID successfully\n";
    } else {
        echo "   FAIL - Could not retrieve rocket by ID\n";
    }
    
    echo "\n";
    
    // Test 3: Get rocket by serial number
    echo "3. Test: Get rocket by serial number\n";
    $rocket_by_serial = get_rocket_by_serial($pdo, $test_serial);
    
    if ($rocket_by_serial && $rocket_by_serial['rocket_id'] == $rocket_id) {
        echo "   PASS - Retrieved rocket by serial number successfully\n";
    } else {
        echo "   FAIL - Could not retrieve rocket by serial number\n";
    }
    
    echo "\n";
    
    // Test 4: Update rocket status
    echo "4. Test: Update rocket status\n";
    $new_status = 'In Production';
    $update_result = update_rocket_status($pdo, $rocket_id, $new_status);
    
    if ($update_result) {
        // Verify the update
        $updated_rocket = get_rocket_by_id($pdo, $rocket_id);
        if ($updated_rocket['current_status'] === $new_status) {
            echo "   PASS - Rocket status updated successfully\n";
        } else {
            echo "   FAIL - Rocket status not updated correctly\n";
        }
    } else {
        echo "   FAIL - Could not update rocket status\n";
    }
    
    echo "\n";
    
    // Test 5: Get all rockets (should include our test rocket)
    echo "5. Test: Get all rockets\n";
    $all_rockets = get_all_rockets($pdo);
    $found_test_rocket = false;
    
    foreach ($all_rockets as $r) {
        if ($r['rocket_id'] == $rocket_id) {
            $found_test_rocket = true;
            break;
        }
    }
    
    if ($found_test_rocket) {
        echo "   PASS - Test rocket found in all rockets list\n";
    } else {
        echo "   FAIL - Test rocket not found in all rockets list\n";
    }
    
    echo "\n";
    
    // Test 6: Count rockets
    echo "6. Test: Count rockets\n";
    $rocket_count = count_rockets($pdo);
    
    if ($rocket_count >= 1) {
        echo "   PASS - Rocket count: $rocket_count\n";
    } else {
        echo "   FAIL - Rocket count seems incorrect: $rocket_count\n";
    }
    
    echo "\n";
    
    // Summary
    echo "=== TEST SUMMARY ===\n";
    echo "All basic rocket function tests completed.\n";
    echo "If all tests show PASS, the rocket functions are working correctly.\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
} finally {
    // Cleanup - Delete test rocket
    echo "\n7. Cleaning up...\n";
    try {
        if (isset($rocket_id)) {
            $delete_result = delete_rocket($pdo, $rocket_id);
            if ($delete_result) {
                echo "   Test rocket deleted successfully.\n";
            } else {
                echo "   WARNING: Failed to delete test rocket. Manual cleanup may be required.\n";
            }
        }
    } catch (Exception $e) {
        echo "   ERROR during cleanup: " . $e->getMessage() . "\n";
    }
    
    echo "\nTest script completed.\n";
}
?>
