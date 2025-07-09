<?php
/**
 * Debug Script for Rocket Detail View - htmlspecialchars Array Error
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== ROCKET DETAIL VIEW DEBUG SCRIPT ===\n\n";

// Include database connection
require_once __DIR__ . '/../includes/db_connect.php';

try {
    // Check data structure in production_steps
    echo "TEST 1: Data Structure Analysis\n";
    echo "-------------------------------\n";
    
    $stmt = $pdo->prepare("SELECT step_id, step_name, data_json FROM production_steps WHERE rocket_id = 2 LIMIT 3");
    $stmt->execute();
    $steps = $stmt->fetchAll();
    
    foreach ($steps as $step) {
        echo "Step ID: {$step['step_id']}\n";
        echo "Step Name: {$step['step_name']}\n";
        echo "Raw JSON Length: " . strlen($step['data_json']) . " characters\n";
        
        // Parse JSON
        $step_data = json_decode($step['data_json'], true);
        
        if (json_last_error() === JSON_ERROR_NONE) {
            echo "✅ JSON parsed successfully\n";
            echo "Data structure:\n";
            
            foreach ($step_data as $key => $value) {
                $type = gettype($value);
                echo "  - '$key' => ($type) ";
                
                if (is_array($value)) {
                    echo "[ARRAY with " . count($value) . " elements]\n";
                    echo "    Array contents: " . json_encode($value, JSON_UNESCAPED_UNICODE) . "\n";
                } elseif (is_string($value)) {
                    echo "'" . substr($value, 0, 50) . (strlen($value) > 50 ? '...' : '') . "'\n";
                } else {
                    echo "$value\n";
                }
            }
        } else {
            echo "❌ JSON parsing failed: " . json_last_error_msg() . "\n";
        }
        
        echo "\n" . str_repeat("-", 50) . "\n\n";
    }
    
    // Test the problematic code scenario
    echo "TEST 2: Simulate View Rendering Problem\n";
    echo "---------------------------------------\n";
    
    $test_step = $steps[0] ?? null;
    if ($test_step) {
        $step_data = json_decode($test_step['data_json'], true);
        
        echo "Simulating rocket_detail_view.php rendering:\n";
        
        foreach ($step_data as $key => $value) {
            echo "Key: '$key' => ";
            
            try {
                if (is_array($value)) {
                    echo "❌ WOULD CAUSE ERROR - Trying htmlspecialchars() on array\n";
                    echo "   Array content: " . json_encode($value, JSON_UNESCAPED_UNICODE) . "\n";
                } else {
                    $escaped = htmlspecialchars($value);
                    echo "✅ OK - String value escaped successfully\n";
                }
            } catch (Exception $e) {
                echo "❌ ERROR: " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "\nTEST 3: Proposed Fix Solution\n";
    echo "-----------------------------\n";
    
    if ($test_step) {
        $step_data = json_decode($test_step['data_json'], true);
        
        echo "Fixed rendering approach:\n";
        
        foreach ($step_data as $key => $value) {
            echo "Key: '$key' => ";
            
            if (is_array($value)) {
                $safe_value = json_encode($value, JSON_UNESCAPED_UNICODE);
                echo "✅ FIXED - Array converted to JSON string: " . htmlspecialchars($safe_value) . "\n";
            } elseif (is_object($value)) {
                $safe_value = json_encode($value, JSON_UNESCAPED_UNICODE);
                echo "✅ FIXED - Object converted to JSON string: " . htmlspecialchars($safe_value) . "\n";
            } else {
                echo "✅ OK - String value: " . htmlspecialchars((string)$value) . "\n";
            }
        }
    }
    
    echo "\nTEST 4: Generate Fix Code\n";
    echo "-------------------------\n";
    
    echo "Replace this line in rocket_detail_view.php (line 325):\n";
    echo "OLD: <td class=\"data-value\"><?php echo htmlspecialchars(\$value); ?></td>\n\n";
    
    echo "NEW: <td class=\"data-value\"><?php \n";
    echo "    if (is_array(\$value) || is_object(\$value)) {\n";
    echo "        echo htmlspecialchars(json_encode(\$value, JSON_UNESCAPED_UNICODE));\n";
    echo "    } else {\n";
    echo "        echo htmlspecialchars((string)\$value);\n";
    echo "    }\n";
    echo "?></td>\n\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "=== DEBUG COMPLETE ===\n";
?>
