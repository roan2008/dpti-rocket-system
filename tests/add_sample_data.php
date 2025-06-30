<?php
/**
 * Sample Data Insertion Script
 * This script adds sample rockets to the database for testing
 */

// Include required files
require_once '../includes/db_connect.php';
require_once '../includes/rocket_functions.php';

echo "=== Adding Sample Data to DPTI Rocket System ===\n";

// Sample rockets data
$sample_rockets = [
    ['RKT-001', 'Apollo Mission Test', 'In Production'],
    ['RKT-002', 'Mars Explorer Alpha', 'Testing'],
    ['RKT-003', 'Lunar Surveyor Beta', 'Completed'],
    ['RKT-004', 'Deep Space Probe', 'New'],
    ['RKT-005', 'Communication Satellite', 'On Hold']
];

try {
    echo "Adding sample rockets...\n";
    
    foreach ($sample_rockets as $rocket_data) {
        $serial = $rocket_data[0];
        $project = $rocket_data[1];
        $status = $rocket_data[2];
        
        // Check if rocket already exists
        $existing = get_rocket_by_serial($pdo, $serial);
        if ($existing) {
            echo "- Rocket $serial already exists, skipping...\n";
            continue;
        }
        
        $rocket_id = create_rocket($pdo, $serial, $project, $status);
        if ($rocket_id) {
            echo "- Created rocket: $serial ($project) - $status\n";
        } else {
            echo "- Failed to create rocket: $serial\n";
        }
    }
    
    echo "\nSample data insertion completed!\n";
    echo "You can now view the dashboard at: http://localhost/dpti-rocket-system/dashboard.php\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
