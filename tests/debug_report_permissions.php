<?php
/**
 * Debug and Fix Permission for Motor Charging Report Access
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== MOTOR CHARGING REPORT PERMISSION DEBUG ===\n\n";

// Include required files
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/report_functions.php';

echo "TEST 1: Current Permission Logic Analysis\n";
echo "-----------------------------------------\n";

// Test with different user roles
$test_users = [
    ['user_id' => 3, 'username' => 'admin', 'role' => 'admin'],
    ['user_id' => 4, 'username' => 'engineer', 'role' => 'engineer'], 
    ['user_id' => 5, 'username' => 'staff', 'role' => 'staff']
];

$rocket_id = 2;

foreach ($test_users as $user) {
    echo "\nTesting user: {$user['username']} (Role: {$user['role']})\n";
    
    // Simulate session
    session_start();
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['username'] = $user['username'];
    
    // Test permission check
    try {
        $can_generate = canGenerateMotorChargingReport($pdo, $rocket_id);
        echo "  Can generate report: " . ($can_generate ? "✅ YES" : "❌ NO") . "\n";
        
        if ($can_generate) {
            echo "  Permission granted for {$user['role']}\n";
        } else {
            echo "  Permission denied for {$user['role']}\n";
        }
        
    } catch (Exception $e) {
        echo "  ❌ Error: " . $e->getMessage() . "\n";
    }
}

echo "\nTEST 2: Check Report Functions Permission Logic\n";
echo "-----------------------------------------------\n";

// Read the report functions file to see current permission logic
$report_functions_file = __DIR__ . '/../includes/report_functions.php';

if (file_exists($report_functions_file)) {
    $content = file_get_contents($report_functions_file);
    
    // Look for role-based restrictions
    if (strpos($content, 'admin') !== false || strpos($content, 'engineer') !== false || strpos($content, 'staff') !== false) {
        echo "✅ Role-based logic found in report_functions.php\n";
        
        // Extract role checking patterns
        if (preg_match_all('/role.*?(admin|engineer|staff)/i', $content, $matches)) {
            echo "Role patterns found:\n";
            foreach ($matches[0] as $match) {
                echo "  - " . trim($match) . "\n";
            }
        }
    } else {
        echo "❌ No explicit role restrictions found\n";
        echo "💡 Permission likely based on approved steps only\n";
    }
} else {
    echo "❌ report_functions.php not found\n";
}

echo "\nTEST 3: Check rocket_detail_view.php Button Logic\n";
echo "-------------------------------------------------\n";

$rocket_detail_file = __DIR__ . '/../views/rocket_detail_view.php';

if (file_exists($rocket_detail_file)) {
    $content = file_get_contents($rocket_detail_file);
    
    // Look for role restrictions on the button
    if (preg_match('/if.*?\$can_generate_motor_charging_report.*?:/s', $content, $matches)) {
        echo "✅ Button permission check found\n";
        echo "Current logic: " . trim($matches[0]) . "\n";
        
        // Check if there are additional role restrictions
        if (strpos($content, 'has_role') !== false && strpos($content, 'motor_charging_report') !== false) {
            echo "⚠️ Additional role restrictions may exist\n";
        } else {
            echo "✅ No additional role restrictions on button\n";
        }
    } else {
        echo "❌ Button permission logic not found\n";
    }
} else {
    echo "❌ rocket_detail_view.php not found\n";
}

echo "\nTEST 4: Controller Permission Check\n";
echo "----------------------------------\n";

$controller_file = __DIR__ . '/../controllers/motor_charging_report_controller.php';

if (file_exists($controller_file)) {
    $content = file_get_contents($controller_file);
    
    // Look for role-based access control
    if (strpos($content, 'has_role') !== false) {
        echo "⚠️ Role-based restrictions found in controller\n";
        
        // Extract role checking patterns
        if (preg_match_all('/has_role\([\'"]([^\'"]+)[\'"]\)/i', $content, $matches)) {
            echo "Required roles:\n";
            foreach ($matches[1] as $role) {
                echo "  - $role\n";
            }
        }
    } else {
        echo "✅ No role-based restrictions in controller\n";
        echo "💡 Access based on canGenerateMotorChargingReport() function only\n";
    }
} else {
    echo "❌ motor_charging_report_controller.php not found\n";
}

echo "\nTEST 5: Recommended Changes\n";
echo "---------------------------\n";

echo "CURRENT BEHAVIOR:\n";
echo "- Motor Charging Report access depends on approved production steps\n";
echo "- No explicit role restrictions (good for flexibility)\n\n";

echo "REQUESTED CHANGE:\n";
echo "- Admin and Engineer should be able to print reports\n";
echo "- This is likely already working if the business logic allows it\n\n";

echo "VERIFICATION NEEDED:\n";
echo "1. Check if admin/engineer users can see the button\n";
echo "2. Check if they can access the controller\n";
echo "3. Check if they can view/print the report\n\n";

echo "If there are restrictions, they might be in:\n";
echo "- rocket_detail_view.php (button visibility)\n";
echo "- motor_charging_report_controller.php (access control)\n";
echo "- report_functions.php (business logic)\n\n";

echo "=== TESTING ADMIN ACCESS ===\n";

// Test admin access specifically
session_start();
$_SESSION['user_id'] = 3; // Admin user
$_SESSION['username'] = 'admin';

try {
    $can_generate = canGenerateMotorChargingReport($pdo, $rocket_id);
    echo "Admin can generate report: " . ($can_generate ? "✅ YES" : "❌ NO") . "\n";
    
    if ($can_generate) {
        echo "🎉 ADMIN CAN ALREADY ACCESS MOTOR CHARGING REPORT!\n";
        echo "The system should already work for admin users.\n";
    } else {
        echo "❌ Admin access is blocked. Need to check business logic.\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error testing admin access: " . $e->getMessage() . "\n";
}

echo "\n=== DEBUG COMPLETE ===\n";
?>
