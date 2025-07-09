<?php
/**
 * Debug Script for "Back to Rocket" Button Issue
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== BACK TO ROCKET BUTTON DEBUG ===\n\n";

// Test 1: Check current button path issue
echo "TEST 1: Path Analysis\n";
echo "--------------------\n";

echo "PROBLEM URL: http://localhost/dpti-rocket-system/controllers/rocket_detail_view.php?id=2\n";
echo "ERROR: 404 Not Found\n\n";

echo "ANALYSIS:\n";
echo "- URL tries to access rocket_detail_view.php in controllers/ directory\n";
echo "- But rocket_detail_view.php is actually in views/ directory\n";
echo "- This is a path mismatch issue\n\n";

// Test 2: Check file locations
echo "TEST 2: File Location Check\n";
echo "---------------------------\n";

$project_root = dirname(__DIR__);
echo "Project root: $project_root\n\n";

$file_locations = [
    'views/rocket_detail_view.php' => $project_root . '/views/rocket_detail_view.php',
    'controllers/rocket_detail_view.php' => $project_root . '/controllers/rocket_detail_view.php',
    'controllers/rocket_detail_controller.php' => $project_root . '/controllers/rocket_detail_controller.php'
];

echo "Checking file locations:\n";
foreach ($file_locations as $path => $full_path) {
    $exists = file_exists($full_path);
    echo "  $path: " . ($exists ? "✅ EXISTS" : "❌ MISSING") . "\n";
}

echo "\nTEST 3: Find Back Button in Motor Charging Report\n";
echo "-------------------------------------------------\n";

// Check motor charging report view for back button
$motor_report_view = $project_root . '/views/motor_charging_report_view.php';

if (file_exists($motor_report_view)) {
    $content = file_get_contents($motor_report_view);
    
    // Look for back button patterns
    $back_patterns = [
        'back to rocket',
        'rocket_detail_view.php',
        'href="[^"]*rocket[^"]*"'
    ];
    
    echo "Searching for back button in motor_charging_report_view.php:\n";
    
    foreach ($back_patterns as $pattern) {
        if (stripos($content, $pattern) !== false || preg_match('/' . $pattern . '/i', $content)) {
            echo "  ✅ Found pattern: '$pattern'\n";
            
            // Extract the href if it's a link pattern
            if (strpos($pattern, 'href') !== false) {
                if (preg_match('/href="([^"]*rocket[^"]*)"/', $content, $matches)) {
                    echo "    Current href: " . $matches[1] . "\n";
                }
            }
        } else {
            echo "  ❌ Pattern not found: '$pattern'\n";
        }
    }
    
    // Extract actual back button
    if (preg_match('/href="([^"]*rocket_detail[^"]*)"/', $content, $matches)) {
        echo "\n❌ PROBLEMATIC HREF FOUND: " . $matches[1] . "\n";
    }
    
} else {
    echo "❌ motor_charging_report_view.php not found\n";
}

echo "\nTEST 4: Determine Correct Path\n";
echo "------------------------------\n";

echo "Current (WRONG): controllers/rocket_detail_view.php\n";
echo "Correct options from motor_charging_report_view.php:\n\n";

echo "Option 1 (Same directory - views/):\n";
echo "  href=\"rocket_detail_view.php?id=2\"\n\n";

echo "Option 2 (Relative path):\n";
echo "  href=\"../views/rocket_detail_view.php?id=2\"\n\n";

echo "Option 3 (Absolute from project root):\n";
echo "  href=\"/dpti-rocket-system/views/rocket_detail_view.php?id=2\"\n\n";

echo "Option 4 (Use controller - RECOMMENDED):\n";
echo "  href=\"../controllers/rocket_detail_controller.php?id=2\"\n";
echo "  (If rocket_detail_controller.php exists)\n\n";

echo "TEST 5: Check for Rocket Detail Controller\n";
echo "------------------------------------------\n";

$controllers_dir = $project_root . '/controllers';
$controller_files = glob($controllers_dir . '/*rocket*');

echo "Controllers containing 'rocket':\n";
if (empty($controller_files)) {
    echo "  ❌ No rocket controllers found\n";
    echo "  💡 rocket_detail_view.php is accessed directly as a view\n";
} else {
    foreach ($controller_files as $file) {
        echo "  ✅ " . basename($file) . "\n";
    }
}

echo "\nTEST 6: Generate Fix Solutions\n";
echo "------------------------------\n";

echo "SOLUTION 1 (Quick Fix - Same Directory):\n";
echo "Replace in motor_charging_report_view.php:\n";
echo "OLD: href=\"controllers/rocket_detail_view.php?id=<?php echo \$rocket_id; ?>\"\n";
echo "NEW: href=\"rocket_detail_view.php?id=<?php echo \$rocket_id; ?>\"\n\n";

echo "SOLUTION 2 (Proper MVC):\n";
echo "Create rocket_detail_controller.php and update href to:\n";
echo "NEW: href=\"../controllers/rocket_detail_controller.php?id=<?php echo \$rocket_id; ?>\"\n\n";

echo "SOLUTION 3 (Absolute Path):\n";
echo "NEW: href=\"/dpti-rocket-system/views/rocket_detail_view.php?id=<?php echo \$rocket_id; ?>\"\n\n";

echo "=== RECOMMENDED ACTION ===\n";
echo "Use SOLUTION 1 for quick fix (both files are in views/ directory)\n";
echo "Change href from 'controllers/rocket_detail_view.php' to 'rocket_detail_view.php'\n";

echo "\n=== DEBUG COMPLETE ===\n";
?>
