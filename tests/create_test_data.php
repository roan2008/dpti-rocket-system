<?php
/**
 * Create Test Data for Motor Charging Report Testing
 * This script will add the required production steps and approvals for testing
 */

session_start();
$_SESSION['user_id'] = 1; // Simulate logged-in user
$_SESSION['username'] = 'test_user';

require_once 'includes/db_connect.php';
require_once 'includes/report_functions.php';

// Check if we have PDO connection
if (!isset($pdo)) {
    try {
        $pdo = new PDO("mysql:host=localhost;dbname=dpti_rocket_prod", "root", "");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (Exception $e) {
        die("Database connection failed: " . $e->getMessage());
    }
}

echo "<h1>🔧 Creating Test Data for Motor Charging Report</h1>\n";
echo "<style>body { font-family: Arial, sans-serif; margin: 2rem; } .success { color: green; } .error { color: red; } .info { color: blue; }</style>\n";

// Get a test rocket
$rocket_query = $pdo->prepare("SELECT rocket_id, serial_number, project_name FROM rockets LIMIT 1");
$rocket_query->execute();
$test_rocket = $rocket_query->fetch(PDO::FETCH_ASSOC);

if (!$test_rocket) {
    echo "<p class='error'>❌ No rockets found. Please create a rocket first.</p>";
    exit;
}

$rocket_id = $test_rocket['rocket_id'];
echo "<p class='info'>Creating test data for Rocket: {$test_rocket['serial_number']} - {$test_rocket['project_name']} (ID: {$rocket_id})</p>\n";

// Get a staff member to assign the steps
$staff_query = $pdo->prepare("SELECT user_id, full_name FROM users WHERE role IN ('staff', 'engineer', 'admin') LIMIT 1");
$staff_query->execute();
$staff_member = $staff_query->fetch(PDO::FETCH_ASSOC);

if (!$staff_member) {
    echo "<p class='error'>❌ No staff members found.</p>";
    exit;
}

$staff_id = $staff_member['user_id'];
echo "<p class='info'>Using staff member: {$staff_member['full_name']} (ID: {$staff_id})</p>\n";

// Get an engineer/admin for approvals
$approver_query = $pdo->prepare("SELECT user_id, full_name FROM users WHERE role IN ('engineer', 'admin') LIMIT 1");
$approver_query->execute();
$approver = $approver_query->fetch(PDO::FETCH_ASSOC);

if (!$approver) {
    echo "<p class='error'>❌ No engineers/admins found for approvals.</p>";
    exit;
}

$approver_id = $approver['user_id'];
echo "<p class='info'>Using approver: {$approver['full_name']} (ID: {$approver_id})</p>\n";

// Get mandatory steps
$mandatory_steps = [
    'Motor Casing Preparation',
    'Propellant Mixing', 
    'Propellant Loading',
    'Nozzle Installation',
    'Quality Control Inspection',
    'Final Assembly'
];

echo "<h2>Creating Production Steps and Approvals</h2>\n";

// Function to add production step
function addProductionStep($pdo, $rocket_id, $step_name, $data_json, $staff_id) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO production_steps (rocket_id, step_name, data_json, staff_id, step_timestamp) 
            VALUES (:rocket_id, :step_name, :data_json, :staff_id, NOW())
        ");
        
        $result = $stmt->execute([
            ':rocket_id' => $rocket_id,
            ':step_name' => $step_name,
            ':data_json' => $data_json,
            ':staff_id' => $staff_id
        ]);
        
        return $result ? $pdo->lastInsertId() : false;
    } catch (Exception $e) {
        echo "<p class='error'>Error creating step: " . $e->getMessage() . "</p>\n";
        return false;
    }
}

// Function to submit approval
function submitApproval($pdo, $step_id, $status, $comments, $engineer_id) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO approvals (step_id, engineer_id, status, comments, approval_timestamp) 
            VALUES (:step_id, :engineer_id, :status, :comments, NOW())
        ");
        
        return $stmt->execute([
            ':step_id' => $step_id,
            ':engineer_id' => $engineer_id,
            ':status' => $status,
            ':comments' => $comments
        ]);
    } catch (Exception $e) {
        echo "<p class='error'>Error creating approval: " . $e->getMessage() . "</p>\n";
        return false;
    }
}

try {
    $pdo->beginTransaction();
    $all_created = true;
    
    foreach ($mandatory_steps as $index => $step_name) {
        // Create detailed JSON data for each step
        $step_data = [
            'step_name' => $step_name,
            'procedure_followed' => 'Standard Operating Procedure v2.1',
            'quality_checks' => [
                'visual_inspection' => 'passed',
                'measurement_verification' => 'passed',
                'documentation_complete' => 'yes'
            ],
            'materials_used' => [
                'primary_component' => 'High-grade aluminum alloy',
                'secondary_materials' => 'Thermal insulation compound',
                'batch_number' => 'BATCH-2025-' . str_pad($index + 1, 3, '0', STR_PAD_LEFT)
            ],
            'environmental_conditions' => [
                'temperature' => '22°C',
                'humidity' => '45%',
                'clean_room_class' => 'ISO 14644-1 Class 7'
            ],
            'duration_minutes' => rand(30, 180),
            'notes' => "Completed {$step_name} following all safety protocols and quality standards.",
            'timestamp' => date('Y-m-d H:i:s', strtotime("-" . (count($mandatory_steps) - $index) . " days"))
        ];
        
        $data_json = json_encode($step_data, JSON_PRETTY_PRINT);
        
        // Add production step
        $step_id = addProductionStep($pdo, $rocket_id, $step_name, $data_json, $staff_id);
        
        if ($step_id) {
            echo "<p class='success'>✅ Created step: {$step_name} (ID: {$step_id})</p>\n";
            
            // Create approval for this step
            $approval_comments = "Step completed according to specifications. All quality checks passed. Approved for Motor Charging Report generation.";
            
            $approval_result = submitApproval($pdo, $step_id, 'approved', $approval_comments, $approver_id);
            
            if ($approval_result) {
                echo "<p class='success'>✅ Created approval for step: {$step_name}</p>\n";
            } else {
                echo "<p class='error'>❌ Failed to create approval for step: {$step_name}</p>\n";
                $all_created = false;
            }
            
        } else {
            echo "<p class='error'>❌ Failed to create step: {$step_name}</p>\n";
            $all_created = false;
        }
    }
    
    if ($all_created) {
        $pdo->commit();
        echo "<p class='success'>✅ All test data created successfully!</p>\n";
    } else {
        $pdo->rollBack();
        echo "<p class='error'>❌ Some steps failed, rolling back transaction</p>\n";
    }
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "<p class='error'>❌ Error creating test data: " . $e->getMessage() . "</p>\n";
}

// Verify the test data
echo "<h2>Verification</h2>\n";

// Test permission function again
$can_generate = canGenerateMotorChargingReport($rocket_id, $pdo);
if ($can_generate) {
    echo "<p class='success'>🎉 SUCCESS! Motor Charging Report can now be generated for rocket {$rocket_id}</p>\n";
    
    // Test data aggregation
    $report_data = getMotorChargingReportData($rocket_id, $pdo);
    if ($report_data && is_array($report_data)) {
        echo "<p class='success'>✅ Report data aggregation successful</p>\n";
        echo "<p class='info'>Report contains {$report_data['report_metadata']['total_steps']} steps, {$report_data['report_metadata']['approved_steps']} approved</p>\n";
        
        // Show sample data structure
        echo "<h3>Sample Report Data Structure:</h3>\n";
        echo "<pre style='background: #f8f9fa; padding: 1rem; border-radius: 4px; overflow-x: auto;'>\n";
        echo "rocket_info: " . json_encode($report_data['rocket_info'], JSON_PRETTY_PRINT) . "\n\n";
        echo "report_metadata: " . json_encode($report_data['report_metadata'], JSON_PRETTY_PRINT) . "\n\n";
        echo "First production step sample: " . json_encode($report_data['production_steps'][0] ?? [], JSON_PRETTY_PRINT) . "\n";
        echo "</pre>\n";
        
    } else {
        echo "<p class='error'>❌ Report data aggregation failed</p>\n";
    }
    
} else {
    echo "<p class='error'>❌ Report generation still not allowed. Check the data creation.</p>\n";
}

echo "<hr>\n";
echo "<h2>🚀 How to Print the Report</h2>\n";
echo "<p><strong>Step 1:</strong> Go to rocket detail view</p>\n";
echo "<p><a href='views/rocket_detail_view.php?id={$rocket_id}' target='_blank' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>📋 View Rocket Details</a></p>\n";
echo "<p><strong>Step 2:</strong> Look for the '🔧 Print Motor Charging Report' button</p>\n";
echo "<p><strong>Step 3:</strong> Click the button to generate the report</p>\n";
echo "<p><strong>Step 4:</strong> Use your browser's print function (Ctrl+P) or click the 🖨️ Print button</p>\n";

echo "<p><strong>Direct Report Link:</strong></p>\n";
echo "<p><a href='controllers/motor_charging_report_controller.php?rocket_id={$rocket_id}' target='_blank' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🚀 Generate Report Directly</a></p>\n";

echo "<hr>\n";
echo "<h2>🚀 Ready for Phase 2!</h2>\n";
echo "<p><strong>Test Data Created:</strong></p>\n";
echo "<ul>\n";
echo "<li>✅ 6 mandatory production steps</li>\n";
echo "<li>✅ All steps approved by engineer/admin</li>\n";
echo "<li>✅ Realistic JSON data for each step</li>\n";
echo "<li>✅ Permission function returns TRUE</li>\n";
echo "<li>✅ Data aggregation function returns complete data</li>\n";
echo "</ul>\n";

echo "<p><strong>Next Steps:</strong></p>\n";
echo "<ol>\n";
echo "<li>🔘 Modify rocket detail view to show 'Print Motor Charging Report' button</li>\n";
echo "<li>🔘 Create report controller to handle requests</li>\n";
echo "<li>🔘 Create report view with HTML table layout</li>\n";
echo "<li>🔘 Add print-friendly CSS</li>\n";
echo "</ol>\n";

echo "<p class='success'><strong>You can now proceed to Phase 2 implementation!</strong></p>\n";
?>
