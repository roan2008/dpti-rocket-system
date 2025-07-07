<?php
/**
 * Non-Interactive CLI Test Scripts for Dynamic Template Form Builder
 * Quick tests without user input
 */

require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/user_functions.php';
require_once __DIR__ . '/../includes/template_functions.php';

if ($argc < 2) {
    echo "Usage: php quick_tests.php [test_name]\n\n";
    echo "Available tests:\n";
    echo "  stats       - Show template statistics\n";
    echo "  list        - List all templates\n";
    echo "  validate    - Test field validation\n";
    echo "  create      - Create a test template\n";
    echo "  cleanup     - Delete test templates\n";
    echo "  json        - Test JSON processing\n";
    echo "  functions   - Test function availability\n";
    echo "  database    - Test database schema\n";
    exit(1);
}

$test_name = $argv[1];

switch ($test_name) {
    case 'stats':
        showTemplateStatistics();
        break;
    case 'list':
        listAllTemplates();
        break;
    case 'validate':
        testFieldValidation();
        break;
    case 'create':
        createTestTemplate();
        break;
    case 'cleanup':
        cleanupTestTemplates();
        break;
    case 'json':
        testJsonProcessing();
        break;
    case 'functions':
        testFunctions();
        break;
    case 'database':
        testDatabaseSchema();
        break;
    default:
        echo "❌ Unknown test: $test_name\n";
        exit(1);
}

function showTemplateStatistics() {
    global $pdo;
    
    echo "📊 TEMPLATE STATISTICS\n";
    echo str_repeat("=", 50) . "\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM step_templates");
    $total_templates = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) as active FROM step_templates WHERE is_active = 1");
    $active_templates = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) as total_fields FROM template_fields");
    $total_fields = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT field_type, COUNT(*) as count FROM template_fields GROUP BY field_type");
    $field_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Templates:\n";
    echo "  📋 Total: $total_templates\n";
    echo "  ✅ Active: $active_templates\n";
    echo "  ❌ Inactive: " . ($total_templates - $active_templates) . "\n\n";
    
    echo "Fields:\n";
    echo "  🔧 Total Fields: $total_fields\n";
    echo "  📊 Average per Template: " . ($total_templates > 0 ? round($total_fields / $total_templates, 1) : 0) . "\n\n";
    
    echo "Field Types:\n";
    foreach ($field_types as $type) {
        echo "  🔸 {$type['field_type']}: {$type['count']}\n";
    }
    
    $stmt = $pdo->query("SELECT step_name, created_at FROM step_templates ORDER BY created_at DESC LIMIT 5");
    $recent = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nRecent Templates:\n";
    foreach ($recent as $template) {
        echo "  🕒 {$template['step_name']} (created: {$template['created_at']})\n";
    }
    
    echo "\n✅ Statistics displayed successfully!\n";
}

function listAllTemplates() {
    global $pdo;
    
    echo "📋 ALL TEMPLATES\n";
    echo str_repeat("=", 50) . "\n";
    
    $templates = getAllTemplates($pdo);
    
    if (empty($templates)) {
        echo "No templates found.\n";
        return;
    }
    
    foreach ($templates as $template) {
        $field_count = getTemplateFieldCount($pdo, $template['template_id']);
        $status = $template['is_active'] ? '✅ Active' : '❌ Inactive';
        
        echo "ID: {$template['template_id']} | $status\n";
        echo "Name: {$template['step_name']}\n";
        echo "Description: " . (empty($template['step_description']) ? 'No description' : substr($template['step_description'], 0, 60)) . "\n";
        echo "Fields: $field_count | Created: {$template['created_at']}\n";
        echo str_repeat("-", 40) . "\n";
    }
    
    echo "✅ Listed " . count($templates) . " templates!\n";
}

function testFieldValidation() {
    echo "🔍 FIELD VALIDATION TESTING\n";
    echo str_repeat("=", 50) . "\n";
    
    $test_fields = [
        [
            'name' => 'Valid Text Field',
            'data' => [
                'field_label' => 'Product Name',
                'field_name' => 'product_name',
                'field_type' => 'text',
                'is_required' => true
            ],
            'expected' => true
        ],
        [
            'name' => 'Valid Select Field',
            'data' => [
                'field_label' => 'Status',
                'field_name' => 'status',
                'field_type' => 'select',
                'options_json' => '["Pass", "Fail"]',
                'is_required' => true
            ],
            'expected' => true
        ],
        [
            'name' => 'Invalid - Missing Label',
            'data' => [
                'field_label' => '',
                'field_name' => 'test',
                'field_type' => 'text'
            ],
            'expected' => false
        ],
        [
            'name' => 'Invalid - Bad Field Name',
            'data' => [
                'field_label' => 'Test',
                'field_name' => '123invalid',
                'field_type' => 'text'
            ],
            'expected' => false
        ],
        [
            'name' => 'Invalid - Select Without Options',
            'data' => [
                'field_label' => 'Status',
                'field_name' => 'status',
                'field_type' => 'select',
                'options_json' => '',
                'is_required' => true
            ],
            'expected' => false
        ]
    ];
    
    $passed = 0;
    $total = count($test_fields);
    
    foreach ($test_fields as $test) {
        echo "Testing: {$test['name']}\n";
        
        $result = validateTemplateField($test['data']);
        
        if ($result['valid'] === $test['expected']) {
            echo "  ✅ PASS\n";
            $passed++;
        } else {
            echo "  ❌ FAIL - Expected " . ($test['expected'] ? 'valid' : 'invalid') . ", got " . ($result['valid'] ? 'valid' : 'invalid') . "\n";
            if (!empty($result['errors'])) {
                echo "  Errors: " . implode(', ', $result['errors']) . "\n";
            }
        }
        echo "\n";
    }
    
    echo "Results: $passed/$total tests passed\n";
    echo ($passed === $total ? "🎉 All validation tests passed!" : "⚠️  Some validation tests failed") . "\n";
}

function createTestTemplate() {
    global $pdo;
    
    echo "📝 CREATING TEST TEMPLATE\n";
    echo str_repeat("=", 50) . "\n";
    
    $template_name = "CLI Quick Test " . date('Y-m-d H:i:s');
    $template_description = "Automatically created test template";
    
    try {
        $pdo->beginTransaction();
        
        $template_id = createTemplate($pdo, $template_name, $template_description, 3);
        if (!$template_id) {
            throw new Exception("Failed to create template");
        }
        
        echo "✅ Template created with ID: $template_id\n";
        echo "Name: $template_name\n";
        
        // Add test fields
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
                'field_label' => 'Test Result',
                'field_name' => 'test_result',
                'field_type' => 'select',
                'is_required' => true,
                'display_order' => 2,
                'options_json' => '["Pass", "Fail", "Pending"]'
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
        
        $pdo->commit();
        
        echo "✅ Added " . count($test_fields) . " fields\n";
        echo "🎉 Test template created successfully!\n";
        
        // Verify creation
        $created_template = getTemplateWithFields($pdo, $template_id);
        if ($created_template && count($created_template['fields']) === 3) {
            echo "✅ Verification: Template retrieved with all fields\n";
        } else {
            echo "⚠️  Verification: Issue retrieving template or fields\n";
        }
        
    } catch (Exception $e) {
        $pdo->rollback();
        echo "❌ Error: " . $e->getMessage() . "\n";
    }
}

function cleanupTestTemplates() {
    global $pdo;
    
    echo "🗑️  CLEANING UP TEST TEMPLATES\n";
    echo str_repeat("=", 50) . "\n";
    
    $stmt = $pdo->prepare("SELECT template_id, step_name FROM step_templates WHERE step_name LIKE '%Test%' OR step_name LIKE '%CLI%'");
    $stmt->execute();
    $test_templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($test_templates)) {
        echo "No test templates found to delete.\n";
        return;
    }
    
    echo "Found " . count($test_templates) . " test templates:\n";
    
    $deleted_count = 0;
    foreach ($test_templates as $template) {
        echo "Deleting: {$template['step_name']} (ID: {$template['template_id']})\n";
        
        if (deleteTemplate($pdo, $template['template_id'])) {
            echo "  ✅ Deleted successfully\n";
            $deleted_count++;
        } else {
            echo "  ❌ Failed to delete\n";
        }
    }
    
    echo "\n🎉 Cleanup completed: $deleted_count templates deleted\n";
}

function testJsonProcessing() {
    echo "🧬 JSON PROCESSING TESTING\n";
    echo str_repeat("=", 50) . "\n";
    
    $test_cases = [
        [
            'name' => 'Valid String Array',
            'json' => '["Option 1", "Option 2", "Option 3"]',
            'expected' => true
        ],
        [
            'name' => 'Valid Complex Options',
            'json' => '["Pass", "Fail", "Needs Review", "Not Applicable"]',
            'expected' => true
        ],
        [
            'name' => 'Invalid - Missing Bracket',
            'json' => '["Option 1", "Option 2"',
            'expected' => false
        ],
        [
            'name' => 'Invalid - JSON Object',
            'json' => '{"option1": "value1", "option2": "value2"}',
            'expected' => false
        ],
        [
            'name' => 'Invalid - Empty Array',
            'json' => '[]',
            'expected' => false
        ],
        [
            'name' => 'Invalid - Mixed Types',
            'json' => '["String", 123, true, null]',
            'expected' => false
        ],
        [
            'name' => 'Invalid - Duplicate Options',
            'json' => '["Option 1", "Option 2", "option 1"]',
            'expected' => false
        ],
        [
            'name' => 'Invalid - Empty Strings',
            'json' => '["Valid", "", "   "]',
            'expected' => false
        ]
    ];
    
    $passed = 0;
    $total = count($test_cases);
    
    foreach ($test_cases as $test) {
        echo "Testing: {$test['name']}\n";
        echo "JSON: {$test['json']}\n";
        
        $validation = validateSelectFieldOptions($test['json']);
        $is_valid = $validation['valid'];
        
        if ($is_valid === $test['expected']) {
            echo "  ✅ PASS\n";
            $passed++;
            if ($is_valid) {
                $data = json_decode($test['json'], true);
                echo "  Validated: " . count($data) . " valid options\n";
            }
        } else {
            echo "  ❌ FAIL - Expected " . ($test['expected'] ? 'valid' : 'invalid') . ", got " . ($is_valid ? 'valid' : 'invalid') . "\n";
            if (!$is_valid && !empty($validation['errors'])) {
                echo "  Errors: " . implode('; ', $validation['errors']) . "\n";
            }
        }
        echo "\n";
    }
    
    echo "Results: $passed/$total tests passed\n";
    echo ($passed === $total ? "🎉 All JSON tests passed!" : "⚠️  Some JSON tests failed") . "\n";
}

function testFunctions() {
    echo "🔧 FUNCTION AVAILABILITY TESTING\n";
    echo str_repeat("=", 50) . "\n";
    
    $required_functions = [
        'getAllActiveTemplates',
        'getTemplateWithFields',
        'createTemplate',
        'updateTemplate',
        'deleteTemplate',
        'validateTemplateField',
        'templateNameExists',
        'getTemplateFieldCount',
        'getAllTemplates',
        'updateTemplateStatus'
    ];
    
    $available = 0;
    $total = count($required_functions);
    
    foreach ($required_functions as $func) {
        if (function_exists($func)) {
            echo "✅ $func - Available\n";
            $available++;
        } else {
            echo "❌ $func - Missing\n";
        }
    }
    
    echo "\nResults: $available/$total functions available\n";
    echo ($available === $total ? "🎉 All required functions available!" : "⚠️  Some functions missing") . "\n";
}

function testDatabaseSchema() {
    global $pdo;
    
    echo "🗄️  DATABASE SCHEMA TESTING\n";
    echo str_repeat("=", 50) . "\n";
    
    // Test tables exist
    $required_tables = ['step_templates', 'template_fields'];
    $tables_ok = true;
    
    foreach ($required_tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✅ Table '$table' exists\n";
        } else {
            echo "❌ Table '$table' missing\n";
            $tables_ok = false;
        }
    }
    
    if ($tables_ok) {
        // Test step_templates columns
        echo "\nChecking step_templates columns:\n";
        $stmt = $pdo->query("DESCRIBE step_templates");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $required_cols = ['template_id', 'step_name', 'step_description', 'is_active', 'created_by', 'created_at'];
        foreach ($required_cols as $col) {
            $found = false;
            foreach ($columns as $column) {
                if ($column['Field'] === $col) {
                    echo "  ✅ $col\n";
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                echo "  ❌ $col missing\n";
            }
        }
        
        // Test template_fields columns
        echo "\nChecking template_fields columns:\n";
        $stmt = $pdo->query("DESCRIBE template_fields");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $required_cols = ['field_id', 'template_id', 'field_label', 'field_name', 'field_type', 'options_json', 'is_required', 'display_order'];
        foreach ($required_cols as $col) {
            $found = false;
            foreach ($columns as $column) {
                if ($column['Field'] === $col) {
                    echo "  ✅ $col\n";
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                echo "  ❌ $col missing\n";
            }
        }
        
        echo "\n🎉 Database schema check completed!\n";
    } else {
        echo "\n❌ Database schema check failed!\n";
    }
}
?>
