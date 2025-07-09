<?php
/**
 * Full Test Suite for Motor Charging Report Feature
 * Tests all aspects: permissions, data validation, report generation
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== MOTOR CHARGING REPORT FULL TEST SUITE ===\n\n";

// Include required files
require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/report_functions.php';

// Start session for testing
session_start();
$_SESSION['user_id'] = 3;
$_SESSION['username'] = 'admin';

echo "✅ Test Environment Setup Complete\n\n";

// TEST 1: Database Connection
echo "TEST 1: Database Connection\n";
echo "----------------------------\n";
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM rockets");
    $rocket_count = $stmt->fetchColumn();
    echo "✅ Database connected successfully\n";
    echo "📊 Found $rocket_count rockets in database\n\n";
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n\n";
    exit(1);
}

// TEST 2: Check Available Rockets
echo "TEST 2: Available Rockets\n";
echo "-------------------------\n";
try {
    $stmt = $pdo->query("SELECT rocket_id, serial_number, project_name FROM rockets LIMIT 5");
    $rockets = $stmt->fetchAll();
    
    if (empty($rockets)) {
        echo "⚠️ No rockets found in database\n\n";
    } else {
        echo "Available rockets for testing:\n";
        foreach ($rockets as $rocket) {
            echo "  - ID: {$rocket['rocket_id']}, Serial: {$rocket['serial_number']}, Project: {$rocket['project_name']}\n";
        }
        echo "\n";
    }
} catch (Exception $e) {
    echo "❌ Failed to fetch rockets: " . $e->getMessage() . "\n\n";
}

// TEST 3: Production Steps Analysis
echo "TEST 3: Production Steps Analysis\n";
echo "---------------------------------\n";
$test_rocket_id = 2; // Use rocket ID 2 for testing

try {
    $stmt = $pdo->prepare("
        SELECT 
            ps.step_name,
            ps.step_timestamp,
            ps.staff_id,
            ps.data_json,
            CASE WHEN a.approval_id IS NOT NULL THEN 'Approved' ELSE 'Not Approved' END as approval_status
        FROM production_steps ps
        LEFT JOIN approvals a ON ps.step_id = a.step_id
        WHERE ps.rocket_id = ?
        ORDER BY ps.step_timestamp
    ");
    $stmt->execute([$test_rocket_id]);
    $steps = $stmt->fetchAll();
    
    echo "Production steps for rocket ID $test_rocket_id:\n";
    $total_steps = count($steps);
    $approved_steps = 0;
    
    foreach ($steps as $step) {
        $status_icon = '✅'; // All steps exist means they are recorded
        $approval_icon = $step['approval_status'] === 'Approved' ? '✅' : '❌';
        
        echo "  $status_icon {$step['step_name']} | Timestamp: {$step['step_timestamp']} | Approval: $approval_icon {$step['approval_status']}\n";
        
        if ($step['approval_status'] === 'Approved') {
            $approved_steps++;
        }
    }
    
    echo "\n📊 Summary: $approved_steps/$total_steps steps approved\n\n";
    
} catch (Exception $e) {
    echo "❌ Failed to analyze production steps: " . $e->getMessage() . "\n\n";
}

// TEST 4: Permission Check Function
echo "TEST 4: Permission Check Function\n";
echo "---------------------------------\n";
try {
    $can_generate = canGenerateMotorChargingReport($pdo, $test_rocket_id);
    
    if ($can_generate) {
        echo "✅ Permission check PASSED - Report can be generated\n";
    } else {
        echo "❌ Permission check FAILED - Report cannot be generated\n";
        echo "   Reason: Not all required production steps are approved\n";
    }
    echo "\n";
    
} catch (Exception $e) {
    echo "❌ Permission check function error: " . $e->getMessage() . "\n\n";
}

// TEST 5: Required Steps Validation
echo "TEST 5: Required Steps Validation\n";
echo "---------------------------------\n";
$required_steps = [
    'Motor Casing Preparation',
    'Propellant Mixing', 
    'Propellant Loading',
    'Nozzle Installation',
    'Quality Control Inspection',
    'Final Assembly'
];

try {
    $stmt = $pdo->prepare("
        SELECT ps.step_name, a.approval_id as approval_id
        FROM production_steps ps
        LEFT JOIN approvals a ON ps.step_id = a.step_id
        WHERE ps.rocket_id = ? AND ps.step_name IN ('" . implode("','", $required_steps) . "')
    ");
    $stmt->execute([$test_rocket_id]);
    $found_steps = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    echo "Required steps validation:\n";
    $all_approved = true;
    
    foreach ($required_steps as $required_step) {
        if (isset($found_steps[$required_step])) {
            if ($found_steps[$required_step]) {
                echo "  ✅ $required_step - APPROVED\n";
            } else {
                echo "  ❌ $required_step - NOT APPROVED\n";
                $all_approved = false;
            }
        } else {
            echo "  ⚠️ $required_step - NOT FOUND\n";
            $all_approved = false;
        }
    }
    
    echo "\n" . ($all_approved ? "✅ All required steps approved" : "❌ Some required steps missing approval") . "\n\n";
    
} catch (Exception $e) {
    echo "❌ Required steps validation error: " . $e->getMessage() . "\n\n";
}

// TEST 6: Data Aggregation Function
echo "TEST 6: Data Aggregation Function\n";
echo "---------------------------------\n";
try {
    $report_data = getMotorChargingReportData($pdo, $test_rocket_id);
    
    if ($report_data === false) {
        echo "❌ Data aggregation FAILED\n\n";
    } else {
        echo "✅ Data aggregation SUCCESSFUL\n";
        echo "Report data structure:\n";
        
        // Validate data structure
        $required_keys = ['rocket_info', 'production_summary', 'production_steps', 'technical_data', 'signatures'];
        foreach ($required_keys as $key) {
            if (isset($report_data[$key])) {
                echo "  ✅ $key - Present\n";
            } else {
                echo "  ❌ $key - Missing\n";
            }
        }
        
        if (isset($report_data['rocket_info'])) {
            echo "\nRocket Info:\n";
            echo "  - Serial: " . ($report_data['rocket_info']['serial_number'] ?? 'N/A') . "\n";
            echo "  - Type: " . ($report_data['rocket_info']['rocket_type'] ?? 'N/A') . "\n";
        }
        
        if (isset($report_data['production_summary'])) {
            echo "\nProduction Summary:\n";
            echo "  - Total Steps: " . ($report_data['production_summary']['total_steps'] ?? 'N/A') . "\n";
            echo "  - Approved Steps: " . ($report_data['production_summary']['approved_steps'] ?? 'N/A') . "\n";
            echo "  - Completion Rate: " . ($report_data['production_summary']['completion_percentage'] ?? 'N/A') . "%\n";
        }
        echo "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Data aggregation function error: " . $e->getMessage() . "\n\n";
}

// TEST 7: Report Generation Simulation
echo "TEST 7: Report Generation Simulation\n";
echo "------------------------------------\n";
try {
    // Simulate URL parameters
    $_GET['rocket_id'] = $test_rocket_id;
    
    // Capture output
    ob_start();
    
    // Test controller logic (without includes)
    $rocket_id = (int) ($_GET['rocket_id'] ?? 0);
    if ($rocket_id <= 0) {
        echo "❌ Invalid rocket ID\n";
    } else {
        if (!canGenerateMotorChargingReport($pdo, $rocket_id)) {
            echo "❌ Report generation denied - insufficient permissions\n";
        } else {
            $report_data = getMotorChargingReportData($pdo, $rocket_id);
            if (!$report_data) {
                echo "❌ Report data aggregation failed\n";
            } else {
                echo "✅ Report generation simulation SUCCESSFUL\n";
                echo "   - Rocket ID: $rocket_id\n";
                echo "   - Data retrieved: " . count($report_data) . " sections\n";
                echo "   - Ready for view rendering\n";
            }
        }
    }
    
    $output = ob_get_clean();
    echo $output;
    echo "\n";
    
} catch (Exception $e) {
    ob_clean();
    echo "❌ Report generation simulation error: " . $e->getMessage() . "\n\n";
}

// TEST 8: HTML Output Test
echo "TEST 8: HTML Output Test\n";
echo "------------------------\n";
try {
    if (isset($report_data) && $report_data) {
        // Test view rendering
        ob_start();
        include __DIR__ . '/views/motor_charging_report_view.php';
        $html_output = ob_get_clean();
        
        if (strlen($html_output) > 1000) {
            echo "✅ HTML output generated successfully\n";
            echo "   - Output length: " . strlen($html_output) . " characters\n";
            echo "   - Contains HTML structure: " . (strpos($html_output, '<html>') !== false ? 'YES' : 'NO') . "\n";
            echo "   - Contains report data: " . (strpos($html_output, 'Motor Charging Report') !== false ? 'YES' : 'NO') . "\n";
        } else {
            echo "❌ HTML output too short or missing\n";
        }
    } else {
        echo "⚠️ Skipping HTML test - no report data available\n";
    }
    echo "\n";
    
} catch (Exception $e) {
    ob_clean();
    echo "❌ HTML output test error: " . $e->getMessage() . "\n\n";
}

// FINAL SUMMARY
echo "=== TEST SUMMARY ===\n";
echo "Motor Charging Report Feature Status:\n";

if (isset($can_generate) && $can_generate && isset($report_data) && $report_data) {
    echo "🎉 FEATURE FULLY FUNCTIONAL\n";
    echo "   ✅ Database connectivity\n";
    echo "   ✅ Permission validation\n";
    echo "   ✅ Data aggregation\n";
    echo "   ✅ Report generation\n";
    echo "   ✅ HTML output\n";
} else {
    echo "⚠️ FEATURE PARTIALLY FUNCTIONAL\n";
    echo "   - Check approval requirements\n";
    echo "   - Verify test data setup\n";
}

echo "\nTest completed at " . date('Y-m-d H:i:s') . "\n";
?>
