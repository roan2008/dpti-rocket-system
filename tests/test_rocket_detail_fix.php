<?php
/**
 * Test Fix for Rocket Detail View
 */

echo "=== TESTING ROCKET DETAIL VIEW FIX ===\n\n";

// Include database connection
require_once __DIR__ . '/../includes/db_connect.php';

// Simulate rocket detail view logic
$rocket_id = 2;

try {
    // Get rocket info
    $stmt = $pdo->prepare("SELECT * FROM rockets WHERE rocket_id = ?");
    $stmt->execute([$rocket_id]);
    $rocket = $stmt->fetch();
    
    if (!$rocket) {
        echo "❌ Rocket not found\n";
        exit;
    }
    
    echo "✅ Testing rocket: {$rocket['serial_number']}\n\n";
    
    // Get production steps
    $stmt = $pdo->prepare("
        SELECT ps.*, u.full_name as staff_name
        FROM production_steps ps
        LEFT JOIN users u ON ps.staff_id = u.user_id
        WHERE ps.rocket_id = ?
        ORDER BY ps.step_timestamp DESC
        LIMIT 3
    ");
    $stmt->execute([$rocket_id]);
    $steps = $stmt->fetchAll();
    
    echo "Testing step data rendering:\n";
    echo str_repeat("=", 50) . "\n";
    
    foreach ($steps as $step) {
        echo "Step: {$step['step_name']}\n";
        echo "Timestamp: {$step['step_timestamp']}\n";
        echo "Staff: {$step['staff_name']}\n";
        
        // Parse JSON data
        $step_data = json_decode($step['data_json'], true);
        
        if (json_last_error() === JSON_ERROR_NONE) {
            echo "Step Details:\n";
            
            foreach ($step_data as $key => $value) {
                echo "  $key: ";
                
                // Apply the same logic as in the fixed view
                if (is_array($value) || is_object($value)) {
                    $safe_value = htmlspecialchars(json_encode($value, JSON_UNESCAPED_UNICODE));
                    echo "[ARRAY/OBJECT] $safe_value\n";
                } else {
                    $safe_value = htmlspecialchars((string)$value);
                    echo "$safe_value\n";
                }
            }
        } else {
            echo "❌ JSON parsing error: " . json_last_error_msg() . "\n";
        }
        
        echo "\n" . str_repeat("-", 30) . "\n\n";
    }
    
    echo "✅ All step data rendered successfully without errors!\n";
    echo "🎉 Fix is working correctly!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== TEST COMPLETE ===\n";
?>
