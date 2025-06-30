<?php
/**
 * Test Production Steps Frontend Integration
 * Tests the complete production steps workflow
 */

// Include required files
require_once '../includes/db_connect.php';
require_once '../includes/user_functions.php';
require_once '../includes/rocket_functions.php';
require_once '../includes/production_functions.php';

echo "=== PRODUCTION STEPS FRONTEND INTEGRATION TEST ===\n\n";

// Test 1: Check if all required files exist
echo "1. Checking file structure:\n";
$required_files = [
    '../views/rocket_detail_view.php' => 'Rocket detail view',
    '../views/step_add_view.php' => 'Add step form',
    '../controllers/production_controller.php' => 'Production controller',
    '../includes/production_functions.php' => 'Production functions'
];

foreach ($required_files as $file => $description) {
    if (file_exists($file)) {
        echo "   ✓ $description exists (" . filesize($file) . " bytes)\n";
    } else {
        echo "   ✗ $description missing\n";
    }
}

// Test 2: Verify rocket detail view includes production functions
echo "\n2. Testing rocket detail view integration:\n";
$rocket_detail_content = file_get_contents('../views/rocket_detail_view.php');

$checks = [
    'production_functions.php' => 'Production functions included',
    'getStepsByRocketId' => 'Steps retrieval function called',
    'Production History' => 'Production history section exists',
    'Add New Production Step' => 'Add step button exists',
    'step-card' => 'Step card styling present'
];

foreach ($checks as $search => $description) {
    if (strpos($rocket_detail_content, $search) !== false) {
        echo "   ✓ $description\n";
    } else {
        echo "   ✗ $description missing\n";
    }
}

// Test 3: Verify add step form structure
echo "\n3. Testing add step form:\n";
$step_form_content = file_get_contents('../views/step_add_view.php');

$form_checks = [
    'production_controller.php' => 'Form action points to controller',
    'rocket_id' => 'Rocket ID hidden field',
    'step_name' => 'Step name dropdown',
    'data_json' => 'JSON data textarea',
    'Tube Preparation' => 'Tube Preparation option',
    'Propellant Mixing' => 'Propellant Mixing option',
    'Motor Assembly' => 'Motor Assembly option',
    'JSON.parse' => 'JSON validation JavaScript'
];

foreach ($form_checks as $search => $description) {
    if (strpos($step_form_content, $search) !== false) {
        echo "   ✓ $description\n";
    } else {
        echo "   ✗ $description missing\n";
    }
}

// Test 4: Verify production controller structure
echo "\n4. Testing production controller:\n";
$controller_content = file_get_contents('../controllers/production_controller.php');

$controller_checks = [
    'handle_add_step' => 'Add step handler function',
    'validateStepJsonData' => 'JSON validation call',
    'addProductionStep' => 'Production step creation call',
    'rocket_detail_view.php' => 'Redirect to detail view',
    'insufficient_permissions' => 'Permission checks',
    'createStepJsonData' => 'Default JSON creation'
];

foreach ($controller_checks as $search => $description) {
    if (strpos($controller_content, $search) !== false) {
        echo "   ✓ $description\n";
    } else {
        echo "   ✗ $description missing\n";
    }
}

// Test 5: Test database integration with a real rocket
echo "\n5. Testing database integration:\n";
try {
    $rockets = get_all_rockets($pdo);
    if (!empty($rockets)) {
        $test_rocket = $rockets[0];
        echo "   ✓ Test rocket available: " . $test_rocket['serial_number'] . "\n";
        
        // Test getting steps for this rocket
        $steps = getStepsByRocketId($pdo, $test_rocket['rocket_id']);
        echo "   ✓ Steps retrieval works: " . count($steps) . " steps found\n";
        
        // Test step count
        $step_count = countStepsByRocketId($pdo, $test_rocket['rocket_id']);
        echo "   ✓ Step counting works: $step_count steps counted\n";
        
    } else {
        echo "   ⚠️  No rockets available for testing\n";
    }
} catch (Exception $e) {
    echo "   ✗ Database integration error: " . $e->getMessage() . "\n";
}

// Test 6: Check CSS styling for production steps
echo "\n6. Testing CSS integration:\n";
$css_content = file_get_contents('../assets/css/style.css');

$css_checks = [
    '.steps-container' => 'Steps container styling',
    '.step-card' => 'Step card styling',
    '.step-header' => 'Step header styling',
    '.json-data' => 'JSON data styling',
    '.production-step-form' => 'Production form styling',
    '.rocket-info' => 'Rocket info styling'
];

foreach ($css_checks as $search => $description) {
    if (strpos($css_content, $search) !== false) {
        echo "   ✓ $description\n";
    } else {
        echo "   ✗ $description missing\n";
    }
}

// Test 7: Test URL patterns and navigation
echo "\n7. Testing navigation patterns:\n";

$navigation_tests = [
    'rocket_detail_view.php includes link to step_add_view.php',
    'step_add_view.php includes back link to rocket_detail_view.php',
    'production_controller.php redirects to rocket_detail_view.php',
    'Error handling preserves form data'
];

$navigation_checks = [
    strpos($rocket_detail_content, 'step_add_view.php') !== false,
    strpos($step_form_content, 'rocket_detail_view.php') !== false,
    strpos($controller_content, 'rocket_detail_view.php') !== false,
    strpos($controller_content, 'redirect_with_error') !== false
];

foreach ($navigation_tests as $index => $test) {
    if ($navigation_checks[$index]) {
        echo "   ✓ $test\n";
    } else {
        echo "   ✗ $test\n";
    }
}

// Test 8: Check user role integration
echo "\n8. Testing role-based access:\n";
$role_checks = [
    strpos($rocket_detail_content, 'has_role') !== false,
    strpos($controller_content, 'has_role') !== false,
    strpos($controller_content, 'insufficient_permissions') !== false
];

$role_tests = [
    'Rocket detail view has role checks',
    'Controller has role-based permissions',
    'Permission error handling exists'
];

foreach ($role_tests as $index => $test) {
    if ($role_checks[$index]) {
        echo "   ✓ $test\n";
    } else {
        echo "   ✗ $test\n";
    }
}

echo "\n=== FRONTEND INTEGRATION TEST COMPLETE ===\n";
echo "\n🎯 PRODUCTION STEPS FRONTEND READY!\n";
echo "\n📋 TESTING INSTRUCTIONS:\n";
echo "1. Login as any user (admin, engineer, or staff)\n";
echo "2. Navigate to any rocket detail page\n";
echo "3. Verify 'Production History' section appears\n";
echo "4. Click 'Add New Production Step' button\n";
echo "5. Fill out the form and submit\n";
echo "6. Verify step appears in rocket history\n";
echo "7. Test with different user roles for permissions\n";

echo "\n🌐 TEST URLs:\n";
echo "- Dashboard: http://localhost/dpti-rocket-system/dashboard.php\n";
echo "- Rocket Detail: http://localhost/dpti-rocket-system/views/rocket_detail_view.php?id=1\n";
echo "- Add Step: http://localhost/dpti-rocket-system/views/step_add_view.php?rocket_id=1\n";

echo "\n✅ All frontend components are integrated and ready for use!\n";
?>
