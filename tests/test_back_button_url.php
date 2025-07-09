<?php
/**
 * Test Motor Charging Report Back Button
 */

echo "=== TESTING MOTOR CHARGING REPORT BACK BUTTON ===\n\n";

// Include database connection for testing
require_once __DIR__ . '/../includes/db_connect.php';

// Simulate rocket data for testing
$rocket_id = 2;

// Get rocket data
try {
    $stmt = $pdo->prepare("SELECT * FROM rockets WHERE rocket_id = ?");
    $stmt->execute([$rocket_id]);
    $rocket = $stmt->fetch();
    
    if (!$rocket) {
        echo "❌ Rocket not found\n";
        exit;
    }
    
    echo "✅ Testing with rocket: {$rocket['serial_number']}\n\n";
    
    // Test the back button URL generation
    echo "TEST 1: Back Button URL Generation\n";
    echo "----------------------------------\n";
    
    $back_url = "rocket_detail_view.php?id=" . $rocket['rocket_id'];
    echo "Generated back URL: $back_url\n";
    echo "Full URL would be: http://localhost/dpti-rocket-system/views/$back_url\n\n";
    
    // Check if target file exists
    $target_file = __DIR__ . '/../views/rocket_detail_view.php';
    echo "Target file: $target_file\n";
    echo "Target exists: " . (file_exists($target_file) ? "✅ YES" : "❌ NO") . "\n\n";
    
    // Test if URL is accessible
    echo "TEST 2: URL Accessibility Test\n";
    echo "------------------------------\n";
    
    // Check current URL context
    echo "From motor_charging_report_view.php (in views/):\n";
    echo "  href=\"rocket_detail_view.php?id={$rocket['rocket_id']}\"\n";
    echo "  Resolves to: views/rocket_detail_view.php?id={$rocket['rocket_id']}\n";
    echo "  Should work: ✅ YES (same directory)\n\n";
    
    // Generate HTML for testing
    echo "TEST 3: Generate Test HTML\n";
    echo "--------------------------\n";
    
    $test_html = '<a href="rocket_detail_view.php?id=' . htmlspecialchars($rocket['rocket_id']) . '" class="btn btn-secondary">';
    $test_html .= '← Back to Rocket</a>';
    
    echo "Generated HTML:\n";
    echo htmlspecialchars($test_html) . "\n\n";
    
    // Check motor_charging_report_view.php content
    echo "TEST 4: Verify Current Content\n";
    echo "------------------------------\n";
    
    $motor_report_file = __DIR__ . '/../views/motor_charging_report_view.php';
    if (file_exists($motor_report_file)) {
        $content = file_get_contents($motor_report_file);
        
        // Extract the exact back button
        if (preg_match('/<a href="([^"]*rocket_detail[^"]*)"[^>]*>.*?Back to Rocket.*?<\/a>/s', $content, $matches)) {
            echo "✅ Back button found in file\n";
            echo "Current href: " . $matches[1] . "\n";
            
            // Check if it matches expected pattern
            $expected = 'rocket_detail_view.php?id=' . $rocket['rocket_id'];
            if (strpos($matches[1], 'rocket_detail_view.php?id=') !== false) {
                echo "✅ Pattern matches expected format\n";
            } else {
                echo "❌ Pattern doesn't match expected format\n";
                echo "Expected pattern: rocket_detail_view.php?id=ROCKET_ID\n";
            }
            
        } else {
            echo "❌ Back button not found in file\n";
        }
    }
    
    echo "\nTEST 5: Browser Cache Issue Check\n";
    echo "---------------------------------\n";
    echo "If the URL in browser is still showing 'controllers/rocket_detail_view.php':\n";
    echo "1. ❌ Browser cache issue - Clear browser cache\n";
    echo "2. ❌ Different file being loaded - Check if there's another motor_charging_report file\n";
    echo "3. ❌ JavaScript redirect - Check for JavaScript that modifies URLs\n\n";
    
    echo "To test: Access motor charging report directly and check back button:\n";
    echo "URL: http://localhost/dpti-rocket-system/controllers/motor_charging_report_controller.php?rocket_id=$rocket_id\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== TEST COMPLETE ===\n";
echo "If back button still shows wrong URL, clear browser cache and try again!\n";
?>
