<?php
/**
 * CLI Test for Production Step Update Function
 * Tests the updateProductionStep function directly
 */

// Include required files
require_once '../includes/db_connect.php';
require_once '../includes/production_functions.php';

echo "=== PRODUCTION STEP UPDATE TEST ===\n\n";

// Test data
$test_step_id = 20; // Change this to an existing step ID
$test_step_name = "TEST Updated Step Name";
$test_data_json = json_encode([
    'test_field' => 'updated_value',
    'updated_at' => date('Y-m-d H:i:s'),
    'test_notes' => 'This is a test update from CLI'
]);

echo "Testing updateProductionStep function...\n";
echo "Step ID: $test_step_id\n";
echo "New Step Name: $test_step_name\n";
echo "New Data JSON: $test_data_json\n\n";

// First, check if step exists
$existing_step = getProductionStepById($pdo, $test_step_id);
if (!$existing_step) {
    echo "❌ FAILED: Step ID $test_step_id not found\n";
    echo "Please update \$test_step_id variable with a valid step ID\n";
    exit;
}

echo "✅ Step found:\n";
echo "  Current Name: {$existing_step['step_name']}\n";
echo "  Current Data: {$existing_step['data_json']}\n\n";

// Perform the update
echo "Performing update...\n";
$result = updateProductionStep($pdo, $test_step_id, $test_step_name, $test_data_json);

if ($result) {
    echo "✅ UPDATE SUCCESSFUL\n\n";
    
    // Verify the update
    $updated_step = getProductionStepById($pdo, $test_step_id);
    if ($updated_step) {
        echo "✅ VERIFICATION SUCCESSFUL:\n";
        echo "  New Name: {$updated_step['step_name']}\n";
        echo "  New Data: {$updated_step['data_json']}\n\n";
        
        // Check if data matches
        if ($updated_step['step_name'] === $test_step_name && $updated_step['data_json'] === $test_data_json) {
            echo "✅ DATA MATCHES - Test completed successfully!\n";
        } else {
            echo "❌ DATA MISMATCH - Test failed!\n";
        }
    } else {
        echo "❌ VERIFICATION FAILED - Could not retrieve updated step\n";
    }
} else {
    echo "❌ UPDATE FAILED\n";
}

echo "\n=== TEST COMPLETED ===\n";
?>
