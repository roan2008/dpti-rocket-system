<?php
/**
 * CLI Test for Production Step Delete Function
 * Tests both scenarios: unapproved step (should succeed) and approved step (should fail)
 */

// Include required files
require_once '../includes/db_connect.php';
require_once '../includes/production_functions.php';
require_once '../includes/approval_functions.php';

echo "=== PRODUCTION STEP DELETE TEST ===\n\n";

// Test Configuration - UPDATE THESE VALUES
$test_unapproved_step_id = 8; // Change to an existing unapproved step ID
$test_approved_step_id = 0;   // Change to an existing approved step ID (or 0 if none)

echo "📋 TEST PLAN:\n";
echo "1. Test deleting unapproved step (should succeed)\n";
echo "2. Test deleting approved step (should fail)\n";
echo "3. Verify business logic and data integrity\n\n";

// Test 1: Delete unapproved step
echo "=== TEST 1: DELETE UNAPPROVED STEP ===\n";
echo "Step ID: $test_unapproved_step_id\n";

// Check if step exists
$step = getProductionStepById($pdo, $test_unapproved_step_id);
if (!$step) {
    echo "❌ SKIP: Step ID $test_unapproved_step_id not found\n";
    echo "Please update \$test_unapproved_step_id with a valid step ID\n\n";
} else {
    echo "✅ Step found: {$step['step_name']}\n";
    
    // Check approval status
    $approval_status = getStepApprovalStatus($pdo, $test_unapproved_step_id);
    if ($approval_status !== false) {
        echo "⚠️  WARNING: This step has approvals. Test may fail.\n";
        echo "Approval Status: " . print_r($approval_status, true) . "\n";
    } else {
        echo "✅ Step is unapproved (good for delete test)\n";
    }
    
    // Perform delete
    echo "Attempting delete...\n";
    $result = deleteProductionStep($pdo, $test_unapproved_step_id);
    
    if ($result['success']) {
        echo "✅ DELETE SUCCESSFUL\n";
        echo "Message: {$result['message']}\n";
        
        // Verify deletion
        $verify_step = getProductionStepById($pdo, $test_unapproved_step_id);
        if (!$verify_step) {
            echo "✅ VERIFICATION: Step successfully removed from database\n";
        } else {
            echo "❌ VERIFICATION FAILED: Step still exists in database\n";
        }
    } else {
        echo "❌ DELETE FAILED\n";
        echo "Error: {$result['error']}\n";
        echo "Message: {$result['message']}\n";
    }
}

echo "\n" . str_repeat("=", 50) . "\n\n";

// Test 2: Delete approved step (if available)
if ($test_approved_step_id > 0) {
    echo "=== TEST 2: DELETE APPROVED STEP ===\n";
    echo "Step ID: $test_approved_step_id\n";
    
    // Check if step exists
    $step = getProductionStepById($pdo, $test_approved_step_id);
    if (!$step) {
        echo "❌ SKIP: Step ID $test_approved_step_id not found\n";
        echo "Please update \$test_approved_step_id with a valid approved step ID\n\n";
    } else {
        echo "✅ Step found: {$step['step_name']}\n";
        
        // Check approval status
        $approval_status = getStepApprovalStatus($pdo, $test_approved_step_id);
        if ($approval_status === false) {
            echo "⚠️  WARNING: This step has no approvals. Expected failure test may not work.\n";
        } else {
            echo "✅ Step has approvals (good for failure test)\n";
            echo "Approval Info: " . print_r($approval_status, true) . "\n";
        }
        
        // Perform delete (should fail)
        echo "Attempting delete (expecting failure)...\n";
        $result = deleteProductionStep($pdo, $test_approved_step_id);
        
        if (!$result['success']) {
            echo "✅ DELETE CORRECTLY FAILED (as expected)\n";
            echo "Error: {$result['error']}\n";
            echo "Message: {$result['message']}\n";
            
            if ($result['error'] === 'has_approvals') {
                echo "✅ BUSINESS LOGIC: Correctly prevented deletion of approved step\n";
            }
            
            // Verify step still exists
            $verify_step = getProductionStepById($pdo, $test_approved_step_id);
            if ($verify_step) {
                echo "✅ VERIFICATION: Step correctly preserved in database\n";
            } else {
                echo "❌ VERIFICATION FAILED: Step was incorrectly deleted\n";
            }
        } else {
            echo "❌ DELETE SUCCEEDED (this should not happen for approved steps)\n";
            echo "⚠️  BUSINESS LOGIC ERROR: Approved step was allowed to be deleted\n";
        }
    }
} else {
    echo "=== TEST 2: SKIPPED ===\n";
    echo "No approved step ID provided for testing.\n";
    echo "To test approval logic, set \$test_approved_step_id to an approved step.\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "=== TEST SUMMARY ===\n";
echo "✅ Test 1: Unapproved step deletion\n";
if ($test_approved_step_id > 0) {
    echo "✅ Test 2: Approved step deletion protection\n";
} else {
    echo "⚠️  Test 2: Skipped (no approved step provided)\n";
}
echo "\n🔍 Check the results above to verify business logic is working correctly.\n";
echo "=== TEST COMPLETED ===\n";
?>
