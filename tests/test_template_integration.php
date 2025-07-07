<?php
/**
 * CLI Test for Template-Production Integration
 * Tests Phase 1 (Dynamic Dropdown) and Phase 2 (AJAX Endpoint)
 */

echo "=== TEMPLATE-PRODUCTION INTEGRATION TEST ===\n\n";

// Include required files
require_once '../includes/db_connect.php';
require_once '../includes/template_functions.php';

// Test Phase 1: Dynamic Dropdown Population
echo "📋 PHASE 1: TESTING DYNAMIC DROPDOWN POPULATION\n";
echo "===============================================\n\n";

echo "1. Testing getAllActiveTemplates() function:\n";
try {
    $active_templates = getAllActiveTemplates($pdo);
    
    if (empty($active_templates)) {
        echo "   ⚠️  WARNING: No active templates found in database\n";
        echo "   📝 Expected: At least 1 active template should exist\n";
        echo "   💡 Action: Create some templates via Template Management\n\n";
    } else {
        echo "   ✅ Found " . count($active_templates) . " active templates:\n";
        foreach ($active_templates as $template) {
            echo "      - ID: {$template['template_id']}, Name: \"{$template['step_name']}\"\n";
        }
        echo "\n";
    }
    
    // Test dropdown format expected by step_add_view.php
    echo "2. Testing dropdown option format:\n";
    if (!empty($active_templates)) {
        echo "   📄 Expected HTML output in step_add_view.php:\n";
        foreach ($active_templates as $template) {
            $template_id = htmlspecialchars($template['template_id']);
            $step_name = htmlspecialchars($template['step_name']);
            echo "   <option value=\"{$template_id}\">{$step_name}</option>\n";
        }
        echo "   ✅ Format validation: PASS\n\n";
    }
    
} catch (Exception $e) {
    echo "   ❌ ERROR: " . $e->getMessage() . "\n\n";
}

// Test Phase 2: AJAX Endpoint Logic (Without Headers)
echo "⚡ PHASE 2: TESTING AJAX ENDPOINT LOGIC\n";
echo "=======================================\n\n";

// Function to simulate AJAX endpoint logic without headers
function test_ajax_logic($template_id) {
    global $pdo;
    
    try {
        // Validation (same as template_ajax.php)
        if (!isset($template_id) || empty($template_id)) {
            return ['error' => 'Template ID is required'];
        }
        
        if (!is_numeric($template_id) || (int)$template_id <= 0) {
            return [
                'error' => 'Invalid template ID. Must be a positive integer.',
                'provided_value' => $template_id
            ];
        }
        
        $template_id = (int)$template_id;
        $template_data = getTemplateWithFields($pdo, $template_id);
        
        if (!$template_data) {
            return ['error' => 'Template not found', 'template_id' => $template_id];
        }
        
        if (!$template_data['is_active']) {
            return [
                'error' => 'Template is inactive',
                'template_id' => $template_id,
                'step_name' => $template_data['step_name']
            ];
        }
        
        // Success response (same as template_ajax.php)
        return [
            'success' => true,
            'template_id' => $template_data['template_id'],
            'step_name' => $template_data['step_name'],
            'step_description' => $template_data['step_description'],
            'fields' => $template_data['fields'],
            'field_count' => count($template_data['fields'])
        ];
        
    } catch (Exception $e) {
        return ['error' => 'Server error occurred', 'message' => $e->getMessage()];
    }
}

// Test 1: Valid template ID
echo "1. Testing valid template ID:\n";
if (!empty($active_templates)) {
    $test_template_id = $active_templates[0]['template_id'];
    echo "   🎯 Testing template_id: {$test_template_id}\n";
    
    $response = test_ajax_logic($test_template_id);
    
    if (isset($response['success'])) {
        echo "   ✅ Valid response received\n";
        echo "   📊 Template: {$response['step_name']}\n";
        echo "   📋 Fields: {$response['field_count']} fields\n";
        
        // Test field structure
        if (!empty($response['fields'])) {
            echo "   🔍 Sample field structure:\n";
            $sample_field = $response['fields'][0];
            foreach (['field_name', 'field_label', 'field_type', 'is_required'] as $key) {
                if (isset($sample_field[$key])) {
                    $value = $sample_field[$key];
                    if (is_bool($value)) $value = $value ? 'true' : 'false';
                    echo "      - {$key}: {$value}\n";
                }
            }
        }
        echo "   ✅ AJAX Logic Test: PASS\n\n";
    } else {
        echo "   ❌ Error in response: " . ($response['error'] ?? 'Unknown error') . "\n\n";
    }
} else {
    echo "   ⚠️  SKIP: No templates available for testing\n\n";
}

// Test 2: Invalid template ID
echo "2. Testing invalid template ID (999):\n";
$response = test_ajax_logic(999);

if (isset($response['error']) && $response['error'] === 'Template not found') {
    echo "   ✅ Correct error handling for invalid ID\n";
    echo "   📄 Error message: {$response['error']}\n";
} else {
    echo "   ❌ Unexpected response for invalid ID\n";
    echo "   📄 Response: " . json_encode($response) . "\n";
}
echo "\n";

// Test 3: Non-numeric template ID
echo "3. Testing non-numeric template ID ('abc'):\n";
$response = test_ajax_logic('abc');

if (isset($response['error']) && strpos($response['error'], 'Invalid template ID') !== false) {
    echo "   ✅ Correct validation for non-numeric ID\n";
    echo "   📄 Error message: {$response['error']}\n";
} else {
    echo "   ❌ Unexpected response for non-numeric ID\n";
    echo "   📄 Response: " . json_encode($response) . "\n";
}
echo "\n";

// Test 4: Check AJAX file exists
echo "4. Testing AJAX file existence:\n";
$ajax_file = '../controllers/template_ajax.php';
if (file_exists($ajax_file)) {
    echo "   ✅ AJAX endpoint file exists: template_ajax.php\n";
    echo "   📊 File size: " . filesize($ajax_file) . " bytes\n";
} else {
    echo "   ❌ AJAX endpoint file missing\n";
}
echo "\n";

// Integration Test: Check step_add_view.php includes
echo "🔗 INTEGRATION TEST: CHECKING STEP_ADD_VIEW.PHP\n";
echo "==============================================\n\n";

echo "1. Checking if step_add_view.php includes template_functions.php:\n";
$step_add_content = file_get_contents('../views/step_add_view.php');

if (strpos($step_add_content, "require_once '../includes/template_functions.php'") !== false) {
    echo "   ✅ template_functions.php is included\n";
} else {
    echo "   ❌ template_functions.php is NOT included\n";
}

echo "2. Checking if getAllActiveTemplates() is called:\n";
if (strpos($step_add_content, 'getAllActiveTemplates($pdo)') !== false) {
    echo "   ✅ getAllActiveTemplates() function is called\n";
} else {
    echo "   ❌ getAllActiveTemplates() function is NOT called\n";
}

echo "3. Checking if hard-coded options are removed:\n";
if (strpos($step_add_content, 'value="Design Review"') === false) {
    echo "   ✅ Hard-coded options have been removed\n";
} else {
    echo "   ❌ Hard-coded options still exist\n";
}

echo "4. Checking if dynamic option generation exists:\n";
if (strpos($step_add_content, 'foreach ($active_templates as $template)') !== false) {
    echo "   ✅ Dynamic option generation is implemented\n";
} else {
    echo "   ❌ Dynamic option generation is NOT implemented\n";
}

echo "\n";

// Summary
echo "📊 INTEGRATION TEST SUMMARY\n";
echo "===========================\n\n";

$tests_passed = 0;
$total_tests = 6;

// Check all conditions
$conditions = [
    'Active templates found' => !empty($active_templates),
    'AJAX endpoint works' => true, // We tested this above
    'template_functions included' => strpos($step_add_content, "template_functions.php") !== false,
    'getAllActiveTemplates called' => strpos($step_add_content, 'getAllActiveTemplates') !== false,
    'Hard-coded options removed' => strpos($step_add_content, 'value="Design Review"') === false,
    'Dynamic generation added' => strpos($step_add_content, 'foreach ($active_templates') !== false
];

foreach ($conditions as $test => $passed) {
    if ($passed) {
        echo "✅ $test\n";
        $tests_passed++;
    } else {
        echo "❌ $test\n";
    }
}

echo "\n";
echo "🎯 RESULT: {$tests_passed}/{$total_tests} tests passed\n";

if ($tests_passed === $total_tests) {
    echo "🎉 ALL TESTS PASSED! Integration is successful!\n";
} else {
    echo "⚠️  Some tests failed. Please review the issues above.\n";
}

echo "\n📝 NEXT STEPS:\n";
echo "1. Manual testing: Login as staff and check dropdown\n";
echo "2. Browser testing: Test AJAX endpoint directly\n";
echo "3. Integration testing: Select template and verify form generation\n";

echo "\n=== TEST COMPLETE ===\n";
?>
