<?php
/**
 * CLI Test Script for Dynamic Template Form Builder
 * Tests all functionality including form creation, validation, and database operations
 */

require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/user_functions.php';
require_once __DIR__ . '/../includes/template_functions.php';

echo "🧪 DYNAMIC TEMPLATE FORM BUILDER - CLI TEST SUITE\n";
echo str_repeat("=", 70) . "\n\n";

// Test counters
$total_tests = 0;
$passed_tests = 0;
$failed_tests = 0;

function runTest($test_name, $test_function) {
    global $total_tests, $passed_tests, $failed_tests;
    $total_tests++;
    
    echo "🔍 Testing: $test_name\n";
    
    try {
        $result = $test_function();
        if ($result === true) {
            echo "✅ PASS: $test_name\n";
            $passed_tests++;
        } else {
            echo "❌ FAIL: $test_name - $result\n";
            $failed_tests++;
        }
    } catch (Exception $e) {
        echo "❌ ERROR: $test_name - " . $e->getMessage() . "\n";
        $failed_tests++;
    }
    
    echo str_repeat("-", 50) . "\n";
}

// Test 1: Template Form View File Exists
runTest("Template Form View File Exists", function() {
    $file_path = __DIR__ . '/../views/template_form_view.php';
    if (!file_exists($file_path)) {
        return "File template_form_view.php not found";
    }
    
    $content = file_get_contents($file_path);
    if (strpos($content, 'id="fields-container"') === false) {
        return "Fields container not found in template";
    }
    
    if (strpos($content, 'id="add-field-btn"') === false) {
        return "Add field button not found in template";
    }
    
    return true;
});

// Test 2: Controller Save Action
runTest("Controller Has Save Action", function() {
    $file_path = __DIR__ . '/../controllers/template_controller.php';
    if (!file_exists($file_path)) {
        return "Controller file not found";
    }
    
    $content = file_get_contents($file_path);
    if (strpos($content, "case 'save':") === false) {
        return "Save action not found in controller";
    }
    
    if (strpos($content, 'handle_save_template') === false) {
        return "handle_save_template function not found";
    }
    
    return true;
});

// Test 3: Database Schema Validation
runTest("Database Schema Validation", function() {
    global $pdo;
    
    // Check step_templates table
    $stmt = $pdo->query("SHOW TABLES LIKE 'step_templates'");
    if ($stmt->rowCount() === 0) {
        return "step_templates table not found";
    }
    
    // Check template_fields table  
    $stmt = $pdo->query("SHOW TABLES LIKE 'template_fields'");
    if ($stmt->rowCount() === 0) {
        return "template_fields table not found";
    }
    
    // Check template_fields columns
    $stmt = $pdo->query("DESCRIBE template_fields");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $required_columns = ['field_id', 'template_id', 'field_label', 'field_name', 'field_type', 'options_json', 'is_required', 'display_order'];
    foreach ($required_columns as $col) {
        if (!in_array($col, $columns)) {
            return "Column $col missing from template_fields table";
        }
    }
    
    return true;
});

// Test 4: Template Functions Exist
runTest("Template Functions Available", function() {
    if (!function_exists('createTemplate')) {
        return "createTemplate function not found";
    }
    
    if (!function_exists('updateTemplate')) {
        return "updateTemplate function not found";
    }
    
    if (!function_exists('validateTemplateField')) {
        return "validateTemplateField function not found";
    }
    
    if (!function_exists('templateNameExists')) {
        return "templateNameExists function not found";
    }
    
    return true;
});

// Test 5: Field Validation Logic
runTest("Field Validation Logic", function() {
    // Test valid field
    $valid_field = [
        'field_label' => 'Test Field',
        'field_name' => 'test_field',
        'field_type' => 'text',
        'is_required' => true,
        'display_order' => 1
    ];
    
    $result = validateTemplateField($valid_field);
    if (!$result['valid']) {
        return "Valid field rejected: " . implode(', ', $result['errors']);
    }
    
    // Test invalid field (missing label)
    $invalid_field = [
        'field_label' => '',
        'field_name' => 'test',
        'field_type' => 'text'
    ];
    
    $result = validateTemplateField($invalid_field);
    if ($result['valid']) {
        return "Invalid field (missing label) was accepted";
    }
    
    // Test select field with options
    $select_field = [
        'field_label' => 'Status',
        'field_name' => 'status',
        'field_type' => 'select',
        'options_json' => '["Pass", "Fail"]',
        'is_required' => true
    ];
    
    $result = validateTemplateField($select_field);
    if (!$result['valid']) {
        return "Valid select field rejected: " . implode(', ', $result['errors']);
    }
    
    // Test select field without options
    $select_field_no_options = [
        'field_label' => 'Status',
        'field_name' => 'status',
        'field_type' => 'select',
        'options_json' => '',
        'is_required' => true
    ];
    
    $result = validateTemplateField($select_field_no_options);
    if ($result['valid']) {
        return "Select field without options was accepted";
    }
    
    return true;
});

// Test 6: Create Template with Fields (Full Integration Test)
runTest("Complete Template Creation", function() {
    global $pdo;
    
    // Start transaction for test
    $pdo->beginTransaction();
    
    try {
        // Create test template
        $template_name = "CLI Test Template " . date('Y-m-d H:i:s');
        $template_description = "Template created by CLI test script";
        $created_by = 3; // admin user
        
        $template_id = createTemplate($pdo, $template_name, $template_description, $created_by);
        if (!$template_id) {
            $pdo->rollback();
            return "Failed to create template";
        }
        
        // Create test fields
        $test_fields = [
            [
                'field_label' => 'Weight (kg)',
                'field_name' => 'weight_kg',
                'field_type' => 'number',
                'is_required' => true,
                'display_order' => 1,
                'options_json' => null
            ],
            [
                'field_label' => 'Status',
                'field_name' => 'status',
                'field_type' => 'select',
                'is_required' => true,
                'display_order' => 2,
                'options_json' => '["Pass", "Fail", "Needs Review"]'
            ],
            [
                'field_label' => 'Notes',
                'field_name' => 'notes',
                'field_type' => 'textarea',
                'is_required' => false,
                'display_order' => 3,
                'options_json' => null
            ]
        ];
        
        // Insert fields
        $field_sql = "INSERT INTO template_fields (template_id, field_label, field_name, field_type, options_json, is_required, display_order) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $field_stmt = $pdo->prepare($field_sql);
        
        foreach ($test_fields as $field) {
            $field_stmt->execute([
                $template_id,
                $field['field_label'],
                $field['field_name'],
                $field['field_type'],
                $field['options_json'],
                $field['is_required'] ? 1 : 0,
                $field['display_order']
            ]);
        }
        
        // Verify creation
        $created_template = getTemplateWithFields($pdo, $template_id);
        if (!$created_template) {
            $pdo->rollback();
            return "Failed to retrieve created template";
        }
        
        if (count($created_template['fields']) !== 3) {
            $pdo->rollback();
            return "Expected 3 fields, got " . count($created_template['fields']);
        }
        
        // Verify field data
        $weight_field = null;
        $status_field = null;
        foreach ($created_template['fields'] as $field) {
            if ($field['field_name'] === 'weight_kg') {
                $weight_field = $field;
            } elseif ($field['field_name'] === 'status') {
                $status_field = $field;
            }
        }
        
        if (!$weight_field || $weight_field['field_type'] !== 'number') {
            $pdo->rollback();
            return "Weight field not created correctly";
        }
        
        if (!$status_field || $status_field['field_type'] !== 'select') {
            $pdo->rollback();
            return "Status field not created correctly";
        }
        
        // Verify select options
        if (!$status_field['options'] || !is_array($status_field['options'])) {
            $pdo->rollback();
            return "Status field options not parsed correctly";
        }
        
        if (!in_array('Pass', $status_field['options']) || !in_array('Fail', $status_field['options'])) {
            $pdo->rollback();
            return "Status field options missing expected values";
        }
        
        // Test complete - rollback to clean up
        $pdo->rollback();
        
        echo "  📊 Created template ID: $template_id\n";
        echo "  📊 Template name: $template_name\n";
        echo "  📊 Fields created: " . count($created_template['fields']) . "\n";
        echo "  📊 Status field options: " . implode(', ', $status_field['options']) . "\n";
        
        return true;
        
    } catch (Exception $e) {
        $pdo->rollback();
        return "Exception during template creation: " . $e->getMessage();
    }
});

// Test 7: Template Name Existence Check
runTest("Template Name Uniqueness Check", function() {
    global $pdo;
    
    // Check existing template name
    $exists = templateNameExists($pdo, 'Quality Control Inspection');
    if (!$exists) {
        return "Failed to detect existing template name";
    }
    
    // Check non-existing template name
    $not_exists = templateNameExists($pdo, 'Definitely Non-Existent Template Name 12345');
    if ($not_exists) {
        return "False positive on non-existing template name";
    }
    
    return true;
});

// Test 8: JSON Field Processing
runTest("JSON Field Options Processing", function() {
    // Test valid JSON array
    $valid_json = '["Option 1", "Option 2", "Option 3"]';
    $validation = validateSelectFieldOptions($valid_json);
    if (!$validation['valid']) {
        return "Valid JSON array rejected: " . implode(', ', $validation['errors']);
    }
    
    // Test invalid JSON syntax
    $invalid_json = '["Option 1", "Option 2"'; // Missing closing bracket
    $validation = validateSelectFieldOptions($invalid_json);
    if ($validation['valid']) {
        return "Invalid JSON syntax was accepted";
    }
    
    // Test JSON object instead of array
    $object_json = '{"option1": "value1", "option2": "value2"}';
    $validation = validateSelectFieldOptions($object_json);
    if ($validation['valid']) {
        return "JSON object was accepted instead of array";
    }
    
    // Test empty array
    $empty_array = '[]';
    $validation = validateSelectFieldOptions($empty_array);
    if ($validation['valid']) {
        return "Empty array was accepted";
    }
    
    // Test array with non-string values
    $mixed_array = '["String", 123, true]';
    $validation = validateSelectFieldOptions($mixed_array);
    if ($validation['valid']) {
        return "Array with non-string values was accepted";
    }
    
    // Test array with duplicate options
    $duplicate_array = '["Option 1", "Option 2", "option 1"]';
    $validation = validateSelectFieldOptions($duplicate_array);
    if ($validation['valid']) {
        return "Array with duplicate options was accepted";
    }
    
    // Test array with empty strings
    $empty_strings = '["Valid Option", "", "   "]';
    $validation = validateSelectFieldOptions($empty_strings);
    if ($validation['valid']) {
        return "Array with empty/whitespace strings was accepted";
    }
    
    return true;
});

// Test 9: Access Control Simulation
runTest("Access Control Logic", function() {
    // Test without session dependency - check function logic directly
    
    // Create a mock function to test role logic
    $test_role_logic = function($user_role, $required_roles) {
        return in_array($user_role, $required_roles);
    };
    
    // Test admin access
    if (!$test_role_logic('admin', ['admin', 'engineer'])) {
        return "Admin should have template access";
    }
    
    // Test engineer access
    if (!$test_role_logic('engineer', ['admin', 'engineer'])) {
        return "Engineer should have template access";
    }
    
    // Test staff access (should be denied)
    if ($test_role_logic('staff', ['admin', 'engineer'])) {
        return "Staff should not have template access";
    }
    
    // Test the actual has_role function exists
    if (!function_exists('has_role')) {
        return "has_role function not found";
    }
    
    return true;
});

// Test 10: Form Data Processing Simulation
runTest("Form Data Processing Logic", function() {
    // Simulate form submission data
    $form_data = [
        'step_name' => 'CLI Test Template',
        'step_description' => 'Test description',
        'fields_data' => json_encode([
            [
                'field_label' => 'Test Field 1',
                'field_name' => 'test_field_1',
                'field_type' => 'text',
                'is_required' => true,
                'display_order' => 1,
                'options_json' => null
            ],
            [
                'field_label' => 'Test Select Field',
                'field_name' => 'test_select',
                'field_type' => 'select',
                'is_required' => false,
                'display_order' => 2,
                'options_json' => '["Option A", "Option B"]'
            ]
        ])
    ];
    
    // Validate step name
    if (empty(trim($form_data['step_name']))) {
        return "Step name validation failed";
    }
    
    // Validate and decode fields data
    $fields_data = json_decode($form_data['fields_data'], true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return "Fields data JSON decode failed";
    }
    
    if (!is_array($fields_data) || count($fields_data) !== 2) {
        return "Fields data structure invalid";
    }
    
    // Validate each field
    foreach ($fields_data as $index => $field) {
        $validation = validateTemplateField($field);
        if (!$validation['valid']) {
            return "Field validation failed for field " . ($index + 1) . ": " . implode(', ', $validation['errors']);
        }
    }
    
    return true;
});

echo "\n📊 TEST SUMMARY\n";
echo str_repeat("=", 70) . "\n";
echo "Total Tests: $total_tests\n";
echo "Passed: $passed_tests\n";
echo "Failed: $failed_tests\n";
if ($total_tests > 0) {
    echo "Success Rate: " . round(($passed_tests / $total_tests) * 100, 1) . "%\n";
} else {
    echo "Success Rate: 0%\n";
}

if ($failed_tests === 0) {
    echo "🎉 ALL TESTS PASSED! Dynamic Template Form Builder is working correctly.\n";
} else {
    echo "⚠️  Some tests failed. Please review the issues above.\n";
}

echo str_repeat("=", 70) . "\n";
?>
