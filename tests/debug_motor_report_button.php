<?php
/**
 * Debug Script for Motor Charging Report Button Issue
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== MOTOR CHARGING REPORT BUTTON DEBUG ===\n\n";

// Test 1: Check file paths
echo "TEST 1: File Path Analysis\n";
echo "---------------------------\n";

$current_dir = __DIR__;
echo "Current script directory: $current_dir\n";

// Check if we're in tests folder
if (strpos($current_dir, 'tests') !== false) {
    $project_root = dirname($current_dir);
    echo "Project root: $project_root\n";
} else {
    $project_root = $current_dir;
    echo "Project root: $project_root\n";
}

// Check paths from rocket detail view perspective
$rocket_detail_path = $project_root . '/views/rocket_detail_view.php';
echo "Rocket detail view path: $rocket_detail_path\n";
echo "Rocket detail exists: " . (file_exists($rocket_detail_path) ? "✅ YES" : "❌ NO") . "\n";

// Check motor charging report paths
$paths_to_check = [
    'motor_charging_report_view.php' => $project_root . '/views/motor_charging_report_view.php',
    '../controllers/motor_charging_report_controller.php' => $project_root . '/controllers/motor_charging_report_controller.php',
    'controllers/motor_charging_report_controller.php' => $project_root . '/controllers/motor_charging_report_controller.php'
];

echo "\nChecking Motor Charging Report files:\n";
foreach ($paths_to_check as $relative_path => $full_path) {
    echo "  $relative_path -> " . (file_exists($full_path) ? "✅ EXISTS" : "❌ MISSING") . "\n";
}

echo "\nTEST 2: URL Structure Analysis\n";
echo "------------------------------\n";

// Simulate where rocket_detail_view.php is accessed from
echo "Rocket detail view is typically accessed from:\n";
echo "  - http://localhost/dpti-rocket-system/views/rocket_detail_view.php?id=2\n";
echo "  - Or via controller redirect\n\n";

echo "Current button href: motor_charging_report_view.php?rocket_id=2\n";
echo "This assumes motor_charging_report_view.php is in the SAME directory as rocket_detail_view.php\n";
echo "But motor_charging_report_view.php is a VIEW, not a CONTROLLER!\n\n";

echo "TEST 3: Correct Path Solutions\n";
echo "------------------------------\n";

echo "PROBLEM: Button links to VIEW instead of CONTROLLER\n";
echo "SOLUTION: Link to controller instead\n\n";

echo "Current (WRONG):\n";
echo '<a href="motor_charging_report_view.php?rocket_id=2">\n\n';

echo "Correct options:\n";
echo "Option 1 (from views/ directory):\n";
echo '<a href="../controllers/motor_charging_report_controller.php?rocket_id=2">' . "\n\n";

echo "Option 2 (absolute from project root):\n";
echo '<a href="/dpti-rocket-system/controllers/motor_charging_report_controller.php?rocket_id=2">' . "\n\n";

echo "Option 3 (relative from document root):\n";
echo '<a href="controllers/motor_charging_report_controller.php?rocket_id=2">' . "\n\n";

echo "TEST 4: Check rocket_detail_view.php button location\n";
echo "----------------------------------------------------\n";

if (file_exists($rocket_detail_path)) {
    $content = file_get_contents($rocket_detail_path);
    
    // Find the button
    if (strpos($content, 'Print Motor Charging Report') !== false) {
        echo "✅ Motor Charging Report button found in rocket_detail_view.php\n";
        
        // Extract the href pattern
        if (preg_match('/href="([^"]*motor_charging_report[^"]*)"/', $content, $matches)) {
            echo "Current href: " . $matches[1] . "\n";
            
            // Analyze the path
            $href = $matches[1];
            if (strpos($href, 'motor_charging_report_view.php') !== false) {
                echo "❌ PROBLEM: Button links to VIEW file instead of CONTROLLER\n";
            } elseif (strpos($href, 'motor_charging_report_controller.php') !== false) {
                echo "✅ Button correctly links to CONTROLLER\n";
            }
        }
    } else {
        echo "❌ Motor Charging Report button not found\n";
    }
} else {
    echo "❌ rocket_detail_view.php not found\n";
}

echo "\nTEST 5: Test URL Generation\n";
echo "---------------------------\n";

$rocket_id = 2;
echo "For rocket_id = $rocket_id:\n";
echo "Correct controller URL: ../controllers/motor_charging_report_controller.php?rocket_id=$rocket_id\n";
echo "Alternative: /dpti-rocket-system/controllers/motor_charging_report_controller.php?rocket_id=$rocket_id\n";

echo "\n=== DEBUG COMPLETE ===\n";
echo "\nRECOMMENDATION:\n";
echo "1. Change button href from 'motor_charging_report_view.php' to '../controllers/motor_charging_report_controller.php'\n";
echo "2. Views should never be accessed directly - always go through controllers\n";
echo "3. Controllers handle business logic and load the appropriate view\n";
?>
