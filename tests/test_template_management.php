<?php
/**
 * DPTI Rocket System - Template Management Test Script
 * 
 * Command-line test script to verify template_functions.php functionality.
 * Tests the getAllActiveTemplates() and getTemplateWithFields() functions.
 * 
 * Usage: php tests/test_template_management.php
 * 
 * @version 1.0
 * @date July 1, 2025
 */

// Include required files
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/template_functions.php';

// Test configuration
$test_results = [];
$total_tests = 0;
$passed_tests = 0;

// ANSI color codes for terminal output
define('COLOR_GREEN', "\033[32m");
define('COLOR_RED', "\033[31m");
define('COLOR_YELLOW', "\033[33m");
define('COLOR_BLUE', "\033[34m");
define('COLOR_RESET', "\033[0m");

/**
 * Print colored output to terminal
 */
function printColored($text, $color = COLOR_RESET) {
    echo $color . $text . COLOR_RESET . "\n";
}

/**
 * Assert function for testing
 */
function assertTrue($condition, $message, $test_name) {
    global $total_tests, $passed_tests, $test_results;
    
    $total_tests++;
    
    if ($condition) {
        $passed_tests++;
        $test_results[] = ['name' => $test_name, 'status' => 'PASS', 'message' => $message];
        printColored("✓ PASS: $message", COLOR_GREEN);
    } else {
        $test_results[] = ['name' => $test_name, 'status' => 'FAIL', 'message' => $message];
        printColored("✗ FAIL: $message", COLOR_RED);
    }
}

/**
 * Assert false function for testing
 */
function assertFalse($condition, $message, $test_name) {
    assertTrue(!$condition, $message, $test_name);
}

/**
 * Assert equals function for testing
 */
function assertEquals($expected, $actual, $message, $test_name) {
    $condition = ($expected === $actual);
    if (!$condition) {
        $message .= " (Expected: " . var_export($expected, true) . ", Got: " . var_export($actual, true) . ")";
    }
    assertTrue($condition, $message, $test_name);
}

/**
 * Clean up all test data
 */
function cleanupTestData($pdo) {
    try {
        // Delete test templates (this will cascade delete template_fields)
        $cleanup_sql = "DELETE FROM step_templates WHERE step_name LIKE 'TEST_%'";
        $stmt = $pdo->prepare($cleanup_sql);
        $stmt->execute();
        
        printColored("🧹 Cleaned up test data", COLOR_YELLOW);
    } catch (PDOException $e) {
        printColored("⚠️  Warning: Could not clean up test data: " . $e->getMessage(), COLOR_YELLOW);
    }
}

/**
 * Test 1: getAllActiveTemplates() function
 */
function testGetAllActiveTemplates($pdo) {
    printColored("\n📋 Testing getAllActiveTemplates() function...", COLOR_BLUE);
    
    try {
        // Insert 3 test templates: 2 active, 1 inactive
        $test_templates = [
            ['name' => 'TEST_Quality_Check', 'description' => 'Test quality check template', 'active' => 1],
            ['name' => 'TEST_Assembly', 'description' => 'Test assembly template', 'active' => 1],
            ['name' => 'TEST_Inactive', 'description' => 'Test inactive template', 'active' => 0]
        ];
        
        $insert_sql = "INSERT INTO step_templates (step_name, step_description, is_active, created_by) VALUES (?, ?, ?, 3)";
        $stmt = $pdo->prepare($insert_sql);
        
        foreach ($test_templates as $template) {
            $stmt->execute([$template['name'], $template['description'], $template['active']]);
        }
        
        printColored("📝 Inserted 3 test templates (2 active, 1 inactive)", COLOR_YELLOW);
        
        // Test the function
        $active_templates = getAllActiveTemplates($pdo);
        
        // Count test templates in results
        $test_template_count = 0;
        $template_names = [];
        
        foreach ($active_templates as $template) {
            if (strpos($template['step_name'], 'TEST_') === 0) {
                $test_template_count++;
                $template_names[] = $template['step_name'];
            }
        }
        
        // Assertions
        assertEquals(2, $test_template_count, "Function returns exactly 2 active test templates", "getAllActiveTemplates_count");
        
        // Check if templates are ordered by name
        $expected_order = ['TEST_Assembly', 'TEST_Quality_Check'];
        sort($template_names);
        assertEquals($expected_order, $template_names, "Templates are ordered alphabetically by name", "getAllActiveTemplates_order");
        
        // Verify function returns array
        assertTrue(is_array($active_templates), "Function returns an array", "getAllActiveTemplates_type");
        
        // Verify each template has required fields
        if (!empty($active_templates)) {
            $first_template = $active_templates[0];
            assertTrue(isset($first_template['template_id']), "Template has template_id field", "getAllActiveTemplates_fields");
            assertTrue(isset($first_template['step_name']), "Template has step_name field", "getAllActiveTemplates_fields");
            assertTrue(isset($first_template['step_description']), "Template has step_description field", "getAllActiveTemplates_fields");
        }
        
    } catch (Exception $e) {
        assertTrue(false, "Exception occurred: " . $e->getMessage(), "getAllActiveTemplates_exception");
    }
}

/**
 * Test 2: getTemplateWithFields() function
 */
function testGetTemplateWithFields($pdo) {
    printColored("\n📝 Testing getTemplateWithFields() function...", COLOR_BLUE);
    
    try {
        // Insert master test template
        $insert_template_sql = "INSERT INTO step_templates (step_name, step_description, is_active, created_by) VALUES (?, ?, 1, 3)";
        $stmt = $pdo->prepare($insert_template_sql);
        $stmt->execute(['TEST_Master_Template', 'Master template for field testing']);
        
        $master_template_id = $pdo->lastInsertId();
        printColored("📝 Inserted master test template with ID: $master_template_id", COLOR_YELLOW);
        
        // Insert 3 test fields with non-sequential display_order (2, 1, 3)
        $test_fields = [
            ['label' => 'Field B', 'name' => 'field_b', 'type' => 'text', 'order' => 2, 'required' => 1],
            ['label' => 'Field A', 'name' => 'field_a', 'type' => 'number', 'order' => 1, 'required' => 0],
            ['label' => 'Field C', 'name' => 'field_c', 'type' => 'select', 'order' => 3, 'required' => 1]
        ];
        
        $insert_field_sql = "INSERT INTO template_fields (template_id, field_label, field_name, field_type, display_order, is_required, options_json) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $field_stmt = $pdo->prepare($insert_field_sql);
        
        foreach ($test_fields as $field) {
            $options_json = ($field['type'] === 'select') ? '["Option 1", "Option 2", "Option 3"]' : null;
            $field_stmt->execute([
                $master_template_id,
                $field['label'],
                $field['name'],
                $field['type'],
                $field['order'],
                $field['required'],
                $options_json
            ]);
        }
        
        printColored("📝 Inserted 3 test fields with display_order: 2, 1, 3", COLOR_YELLOW);
        
        // Test the function with valid template ID
        $template_with_fields = getTemplateWithFields($pdo, $master_template_id);
        
        // Assertions for valid template
        assertTrue($template_with_fields !== false, "Function returns data for valid template ID", "getTemplateWithFields_valid");
        assertTrue(is_array($template_with_fields), "Function returns an array for valid template", "getTemplateWithFields_array");
        assertTrue(isset($template_with_fields['fields']), "Returned array contains 'fields' key", "getTemplateWithFields_fields_key");
        assertTrue(is_array($template_with_fields['fields']), "Fields is an array", "getTemplateWithFields_fields_array");
        assertEquals(3, count($template_with_fields['fields']), "Fields array contains exactly 3 fields", "getTemplateWithFields_field_count");
        
        // Test field ordering by display_order
        if (isset($template_with_fields['fields']) && count($template_with_fields['fields']) === 3) {
            $fields = $template_with_fields['fields'];
            $expected_field_order = ['field_a', 'field_b', 'field_c']; // Based on display_order 1, 2, 3
            $actual_field_order = [];
            
            foreach ($fields as $field) {
                $actual_field_order[] = $field['field_name'];
            }
            
            assertEquals($expected_field_order, $actual_field_order, "Fields are correctly sorted by display_order", "getTemplateWithFields_sort_order");
            
            // Test field properties
            assertTrue(isset($fields[0]['field_label']), "Field has field_label property", "getTemplateWithFields_field_props");
            assertTrue(isset($fields[0]['field_type']), "Field has field_type property", "getTemplateWithFields_field_props");
            assertTrue(isset($fields[0]['is_required']), "Field has is_required property", "getTemplateWithFields_field_props");
            
            // Test boolean conversion
            assertTrue(is_bool($fields[0]['is_required']), "is_required is converted to boolean", "getTemplateWithFields_boolean");
            
            // Test select field options parsing
            $select_field = null;
            foreach ($fields as $field) {
                if ($field['field_type'] === 'select') {
                    $select_field = $field;
                    break;
                }
            }
            
            if ($select_field) {
                assertTrue(isset($select_field['options']), "Select field has options property", "getTemplateWithFields_select_options");
                assertTrue(is_array($select_field['options']), "Select field options is an array", "getTemplateWithFields_select_array");
                assertEquals(3, count($select_field['options']), "Select field has 3 options", "getTemplateWithFields_select_count");
            }
        }
        
        // Test with non-existent template ID
        $non_existent_result = getTemplateWithFields($pdo, 999999);
        assertEquals(false, $non_existent_result, "Function returns false for non-existent template ID", "getTemplateWithFields_nonexistent");
        
        // Test with inactive template
        // First, deactivate our test template
        $deactivate_sql = "UPDATE step_templates SET is_active = 0 WHERE template_id = ?";
        $deactivate_stmt = $pdo->prepare($deactivate_sql);
        $deactivate_stmt->execute([$master_template_id]);
        
        $inactive_result = getTemplateWithFields($pdo, $master_template_id);
        assertEquals(false, $inactive_result, "Function returns false for inactive template", "getTemplateWithFields_inactive");
        
    } catch (Exception $e) {
        assertTrue(false, "Exception occurred: " . $e->getMessage(), "getTemplateWithFields_exception");
    }
}

/**
 * Print test summary
 */
function printTestSummary() {
    global $total_tests, $passed_tests, $test_results;
    
    printColored("\n" . str_repeat("=", 60), COLOR_BLUE);
    printColored("📊 TEST SUMMARY", COLOR_BLUE);
    printColored(str_repeat("=", 60), COLOR_BLUE);
    
    $failed_tests = $total_tests - $passed_tests;
    $success_rate = ($total_tests > 0) ? round(($passed_tests / $total_tests) * 100, 1) : 0;
    
    printColored("Total Tests: $total_tests", COLOR_BLUE);
    printColored("Passed: $passed_tests", COLOR_GREEN);
    printColored("Failed: $failed_tests", ($failed_tests > 0) ? COLOR_RED : COLOR_GREEN);
    printColored("Success Rate: {$success_rate}%", ($success_rate >= 90) ? COLOR_GREEN : COLOR_RED);
    
    if ($failed_tests > 0) {
        printColored("\n❌ FAILED TESTS:", COLOR_RED);
        foreach ($test_results as $result) {
            if ($result['status'] === 'FAIL') {
                printColored("  • {$result['name']}: {$result['message']}", COLOR_RED);
            }
        }
    }
    
    if ($passed_tests === $total_tests) {
        printColored("\n🎉 ALL TESTS PASSED! Template functions are working correctly.", COLOR_GREEN);
    } else {
        printColored("\n⚠️  Some tests failed. Please review the template functions.", COLOR_YELLOW);
    }
    
    printColored(str_repeat("=", 60), COLOR_BLUE);
}

// Main execution
try {
    printColored("🚀 DPTI Rocket System - Template Management Tests", COLOR_BLUE);
    printColored("Starting tests for template_functions.php...\n", COLOR_BLUE);
    
    // Test database connection
    if (!isset($pdo)) {
        printColored("❌ Database connection not available. Please check db_connect.php", COLOR_RED);
        exit(1);
    }
    
    printColored("✓ Database connection established", COLOR_GREEN);
    
    // Clean up any existing test data before starting
    cleanupTestData($pdo);
    
    // Run tests
    testGetAllActiveTemplates($pdo);
    testGetTemplateWithFields($pdo);
    
    // Clean up test data after tests
    cleanupTestData($pdo);
    
    // Print summary
    printTestSummary();
    
} catch (Exception $e) {
    printColored("💥 Critical error during testing: " . $e->getMessage(), COLOR_RED);
    cleanupTestData($pdo);
    exit(1);
}

?>
