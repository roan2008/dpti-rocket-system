<?php
// Simple script to check and create test data
require_once '../includes/db_connect.php';
require_once '../includes/production_functions.php';

echo "=== AVAILABLE PRODUCTION STEPS FOR TESTING ===\n";
try {
    $steps = getAllProductionSteps($pdo);
    if (empty($steps)) {
        echo "No production steps found. Creating test data...\n";
        
        // Create a test step
        require_once '../includes/rocket_functions.php';
        $rockets = get_all_rockets($pdo);
        if (!empty($rockets)) {
            $rocket_id = $rockets[0]['rocket_id'];
            $test_data = json_encode(['test' => 'data', 'created_for' => 'testing']);
            $step_id = addProductionStep($pdo, $rocket_id, 'Test Step for CRUD', $test_data, 5);
            if ($step_id) {
                echo "Created test step with ID: $step_id\n";
                $steps = getAllProductionSteps($pdo);
            }
        }
    }
    
    foreach ($steps as $step) {
        echo "Step ID: {$step['step_id']} | Name: {$step['step_name']} | Rocket: {$step['rocket_serial']}\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
