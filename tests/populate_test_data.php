<?php
/**
 * Test Data Population Script
 * Creates test rockets, templates, and production steps for approval workflow testing
 */

// Include required files
require_once '../includes/db_connect.php';
require_once '../includes/user_functions.php';
require_once '../includes/rocket_functions.php';
require_once '../includes/template_functions.php';
require_once '../includes/production_functions.php';

echo "<h2>🔧 DPTI Rocket System - Test Data Population</h2>\n";
echo "<pre>";

try {
    // Start transaction for data consistency
    $pdo->beginTransaction();
    
    echo "Starting test data population...\n";
    echo "==========================================\n\n";
    
    // 1. Check/Create Test Users
    echo "1. Setting up test users...\n";
    
    // Check if test users exist, create if needed
    $test_users = [
        ['username' => 'test_staff1', 'email' => 'staff1@dpti.test', 'role' => 'staff', 'name' => 'Alice Johnson'],
        ['username' => 'test_staff2', 'email' => 'staff2@dpti.test', 'role' => 'staff', 'name' => 'Bob Wilson'],
        ['username' => 'test_engineer1', 'email' => 'engineer1@dpti.test', 'role' => 'engineer', 'name' => 'Dr. Sarah Chen'],
        ['username' => 'test_admin1', 'email' => 'admin1@dpti.test', 'role' => 'admin', 'name' => 'Mike Rodriguez']
    ];
    
    $user_ids = [];
    foreach ($test_users as $user_data) {
        // Check if user exists
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ?");
        $stmt->execute([$user_data['username']]);
        $existing_user = $stmt->fetch();
        
        if ($existing_user) {
            $user_ids[$user_data['username']] = $existing_user['user_id'];
            echo "   ✓ User '{$user_data['username']}' already exists (ID: {$existing_user['user_id']})\n";
        } else {
            // Create new user
            $hashed_password = password_hash('testpass123', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("
                INSERT INTO users (username, password_hash, full_name, role) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([
                $user_data['username'], 
                $hashed_password, 
                $user_data['name'], 
                $user_data['role']
            ]);
            $user_ids[$user_data['username']] = $pdo->lastInsertId();
            echo "   ✓ Created user '{$user_data['username']}' (ID: {$user_ids[$user_data['username']]})\n";
        }
    }
    
    echo "\n2. Skipping templates (templates system not yet implemented)...\n";
    echo "   ⚠️  Template management system will be implemented in Phase 2\n";
    
    echo "\n3. Creating test rockets...\n";
    
    // Create test rockets
    $test_rockets = [
        [
            'serial_number' => 'RKT-2025-001',
            'project_name' => 'Mission Alpha',
            'status' => 'In Production'
        ],
        [
            'serial_number' => 'RKT-2025-002',
            'project_name' => 'Mission Beta',
            'status' => 'In Production'
        ],
        [
            'serial_number' => 'HLV-2025-001',
            'project_name' => 'Satellite Deployment 5',
            'status' => 'In Production'
        ],
        [
            'serial_number' => 'RKT-2025-003',
            'project_name' => 'Mission Gamma',
            'status' => 'In Production'
        ]
    ];
    
    $rocket_ids = [];
    foreach ($test_rockets as $rocket_data) {
        // Check if rocket exists
        $stmt = $pdo->prepare("SELECT rocket_id FROM rockets WHERE serial_number = ?");
        $stmt->execute([$rocket_data['serial_number']]);
        $existing_rocket = $stmt->fetch();
        
        if ($existing_rocket) {
            $rocket_ids[] = $existing_rocket['rocket_id'];
            echo "   ✓ Rocket '{$rocket_data['serial_number']}' already exists (ID: {$existing_rocket['rocket_id']})\n";
        } else {
            // Create new rocket
            $stmt = $pdo->prepare("
                INSERT INTO rockets (serial_number, project_name, current_status) 
                VALUES (?, ?, ?)
            ");
            $stmt->execute([
                $rocket_data['serial_number'],
                $rocket_data['project_name'],
                $rocket_data['status']
            ]);
            $rocket_id = $pdo->lastInsertId();
            $rocket_ids[] = $rocket_id;
            echo "   ✓ Created rocket '{$rocket_data['serial_number']}' (ID: $rocket_id)\n";
        }
    }
    
    echo "\n4. Creating production steps awaiting approval...\n";
    
    // Create production steps that need approval
    $test_production_steps = [
        // Rocket 1 - Multiple steps completed
        [
            'rocket_id' => $rocket_ids[0],
            'step_name' => 'Frame Assembly',
            'staff_id' => $user_ids['test_staff1'],
            'data_json' => json_encode([
                'operation' => 'Frame Assembly',
                'status' => 'completed',
                'quality_check' => 'passed',
                'notes' => 'Frame assembly completed successfully. All welds inspected and verified.'
            ]),
            'days_ago' => 2
        ],
        [
            'rocket_id' => $rocket_ids[0],
            'step_name' => 'Engine Mount Installation',
            'staff_id' => $user_ids['test_staff2'],
            'data_json' => json_encode([
                'operation' => 'Engine Mount Installation',
                'status' => 'completed',
                'torque_spec' => '50 Nm',
                'notes' => 'Engine mount installed and torqued to specification. Ready for next phase.'
            ]),
            'days_ago' => 1
        ],
        
        // Rocket 2 - Just started
        [
            'rocket_id' => $rocket_ids[1],
            'step_name' => 'Frame Assembly',
            'staff_id' => $user_ids['test_staff1'],
            'data_json' => json_encode([
                'operation' => 'Frame Assembly',
                'status' => 'completed',
                'adjustment_notes' => 'Minor adjustment made to alignment',
                'notes' => 'Initial frame assembly complete. Minor adjustment made to alignment.'
            ]),
            'days_ago' => 3
        ],
        
        // Rocket 3 - Heavy lift rocket
        [
            'rocket_id' => $rocket_ids[2],
            'step_name' => 'Core Structure Assembly',
            'staff_id' => $user_ids['test_staff2'],
            'data_json' => json_encode([
                'operation' => 'Core Structure Assembly',
                'status' => 'completed',
                'integrity_check' => 'passed all tests',
                'notes' => 'Core structure assembled and tested. Passed all structural integrity checks.'
            ]),
            'days_ago' => 1
        ],
        [
            'rocket_id' => $rocket_ids[2],
            'step_name' => 'Booster Installation',
            'staff_id' => $user_ids['test_staff1'],
            'data_json' => json_encode([
                'operation' => 'Booster Installation',
                'status' => 'completed',
                'booster_count' => 4,
                'systems_check' => 'successful',
                'notes' => 'All four boosters installed and connected. Systems check successful.'
            ]),
            'days_ago' => 0
        ],
        
        // Rocket 4 - Recent completion
        [
            'rocket_id' => $rocket_ids[3],
            'step_name' => 'Frame Assembly',
            'staff_id' => $user_ids['test_staff2'],
            'data_json' => json_encode([
                'operation' => 'Frame Assembly',
                'status' => 'completed',
                'schedule' => 'ahead of schedule',
                'quality_metrics' => 'exceeded standards',
                'notes' => 'Frame assembly completed ahead of schedule. Quality metrics exceeded.'
            ]),
            'days_ago' => 0
        ]
    ];
    
    $created_steps = 0;
    foreach ($test_production_steps as $step_data) {
        // Calculate timestamp
        $timestamp = date('Y-m-d H:i:s', strtotime("-{$step_data['days_ago']} days"));
        
        // Check if this step already exists for this rocket
        $stmt = $pdo->prepare("
            SELECT step_id FROM production_steps 
            WHERE rocket_id = ? AND step_name = ?
        ");
        $stmt->execute([$step_data['rocket_id'], $step_data['step_name']]);
        $existing_step = $stmt->fetch();
        
        if (!$existing_step) {
            // Create production step
            $stmt = $pdo->prepare("
                INSERT INTO production_steps 
                (rocket_id, step_name, data_json, staff_id, step_timestamp) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $step_data['rocket_id'],
                $step_data['step_name'],
                $step_data['data_json'],
                $step_data['staff_id'],
                $timestamp
            ]);
            $created_steps++;
            
            // Get rocket info for display
            $stmt = $pdo->prepare("SELECT serial_number FROM rockets WHERE rocket_id = ?");
            $stmt->execute([$step_data['rocket_id']]);
            $rocket = $stmt->fetch();
            
            echo "   ✓ Created step '{$step_data['step_name']}' for rocket '{$rocket['serial_number']}'\n";
        }
    }
    
    echo "\n5. Summary of test data:\n";
    echo "==========================================\n";
    
    // Count production steps (all production steps, not just pending approvals)
    $stmt = $pdo->query("SELECT COUNT(*) as step_count FROM production_steps");
    $step_count = $stmt->fetch()['step_count'];
    
    // Count approvals if table exists
    $approval_count = 0;
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as approval_count FROM approvals");
        $approval_count = $stmt->fetch()['approval_count'];
    } catch (Exception $e) {
        // Approvals table might not exist yet
    }
    
    // Count rockets
    $rocket_count = count($rocket_ids);
    
    echo "   • Users: " . count($user_ids) . " test users available\n";
    echo "   • Rockets: $rocket_count test rockets in production\n";
    echo "   • Production Steps: $step_count production steps created\n";
    echo "   • Approvals: $approval_count approvals recorded\n";
    
    echo "\n6. Test user credentials:\n";
    echo "==========================================\n";
    echo "   Staff Users:\n";
    echo "     • Username: test_staff1, Password: testpass123 (Alice Johnson)\n";
    echo "     • Username: test_staff2, Password: testpass123 (Bob Wilson)\n";
    echo "   Engineer Users:\n";
    echo "     • Username: test_engineer1, Password: testpass123 (Dr. Sarah Chen)\n";
    echo "   Admin Users:\n";
    echo "     • Username: test_admin1, Password: testpass123 (Mike Rodriguez)\n";
    
    echo "\n7. How to test the approval workflow:\n";
    echo "==========================================\n";
    echo "   1. Login as test_engineer1 or test_admin1\n";
    echo "   2. Navigate to: /controllers/approval_controller.php?action=list_pending\n";
    echo "   3. You should see $step_count production steps for approval testing\n";
    echo "   4. Click 'Review & Approve' to test the approval modal\n";
    echo "   5. Submit approvals and verify the workflow\n";
    
    echo "\n8. Direct URLs for testing:\n";
    echo "==========================================\n";
    echo "   • Pending Approvals: /controllers/approval_controller.php?action=list_pending\n";
    echo "   • Production Steps: /views/production_steps_view.php\n";
    echo "   • Rocket Details: /views/rocket_detail_view.php?id=1\n";
    
    // Commit the transaction
    $pdo->commit();
    
    echo "\n✅ Test data population completed successfully!\n";
    echo "\nYou can now test the approval workflow with realistic data.\n";
    
} catch (Exception $e) {
    // Rollback on error
    if ($pdo->inTransaction()) {
        $pdo->rollback();
    }
    
    echo "\n❌ Error during test data population:\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " (Line: " . $e->getLine() . ")\n";
    
    // Log the error
    error_log("Test data population failed: " . $e->getMessage());
}

echo "</pre>";

// Add some CSS for better formatting
echo "<style>
body { font-family: 'Consolas', 'Monaco', monospace; margin: 20px; }
h2 { color: #2c3e50; }
pre { background: #f8f9fa; padding: 20px; border-radius: 5px; border-left: 4px solid #3498db; }
</style>";
?>
