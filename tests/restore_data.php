<?php
// Restore test data
require_once '../includes/db_connect.php';
require_once '../includes/production_functions.php';

// Restore step 20 to original data
$original_data = json_encode([
    'template_id' => '12',
    'step_name' => 'Component Assembly',
    'recorded_at' => '2025-07-08T03:54:42.048Z'
]);

$result = updateProductionStep($pdo, 20, 'Component Assembly', $original_data);
if ($result) {
    echo "Step 20 restored to original data\n";
} else {
    echo "Failed to restore step 20\n";
}
?>
