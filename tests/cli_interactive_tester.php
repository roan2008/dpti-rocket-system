<?php
/**
 * Interactive CLI Test Suite for Dynamic Template Form Builder
 * Allows user to run specific tests or scenarios
 */

require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/user_functions.php';
require_once __DIR__ . '/../includes/template_functions.php';

function showMenu() {
    echo "\n🧪 DYNAMIC TEMPLATE FORM BUILDER - INTERACTIVE CLI TESTER\n";
    echo str_repeat("=", 70) . "\n";
    echo "Choose a test option:\n\n";
    echo "1. 🔧 Run Full Test Suite\n";
    echo "2. 📝 Create Test Template with Fields\n";
    echo "3. 🗑️  Delete Test Templates\n";
    echo "4. 📊 Show Template Statistics\n";
    echo "5. 🔍 Validate Field Data\n";
    echo "6. 🧬 Test JSON Processing\n";
    echo "7. 📋 List All Templates\n";
    echo "8. 🎯 Test Specific Template ID\n";
    echo "9. 🔄 Simulate Form Submission\n";
    echo "0. ❌ Exit\n";
    echo str_repeat("-", 70) . "\n";
    echo "Enter your choice (0-9): ";
}

function runFullTestSuite() {
    echo "\n🚀 Running Full Test Suite...\n";
    echo str_repeat("=", 50) . "\n";
    
    // Include and run the main test script
    include __DIR__ . '/cli_test_dynamic_form.php';
}

function createTestTemplate() {
    global $pdo;
    
    echo "\n📝 CREATE TEST TEMPLATE\n";
    echo str_repeat("=", 50) . "\n";
    
    echo "Enter template name: ";
    $template_name = trim(fgets(STDIN));
    
    echo "Enter template description: ";
    $template_description = trim(fgets(STDIN));
    
    if (empty($template_name)) {
        echo "❌ Template name cannot be empty!\n";
        return;
    }
    
    // Check if name already exists
    if (templateNameExists($pdo, $template_name)) {
        echo "❌ Template name already exists!\n";
        return;
    }
    
    try {
        $pdo->beginTransaction();
        
        // Create template
        $template_id = createTemplate($pdo, $template_name, $template_description, 3); // admin user
        if (!$template_id) {
            throw new Exception("Failed to create template");
        }
        
        echo "✅ Template created with ID: $template_id\n";
        
        // Ask for fields
        echo "\nAdd fields to this template? (y/n): ";
        $add_fields = trim(fgets(STDIN));
        
        if (strtolower($add_fields) === 'y') {
            $field_count = 0;
            
            while (true) {
                $field_count++;
                echo "\n--- Field #$field_count ---\n";
                
                echo "Field label: ";
                $field_label = trim(fgets(STDIN));
                
                if (empty($field_label)) {
                    break;
                }
                
                echo "Field name (auto-generated from label): ";
                $auto_name = strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', $field_label));
                $auto_name = preg_replace('/_+/', '_', $auto_name);
                $auto_name = trim($auto_name, '_');
                echo $auto_name . "\n";
                
                echo "Field type (text/number/textarea/select/date): ";
                $field_type = trim(fgets(STDIN));
                
                if (!in_array($field_type, ['text', 'number', 'textarea', 'select', 'date'])) {
                    echo "❌ Invalid field type, skipping...\n";
                    continue;
                }
                
                echo "Required field? (y/n): ";
                $is_required = trim(fgets(STDIN)) === 'y';
                
                $options_json = null;
                if ($field_type === 'select') {
                    echo "Enter options (comma-separated): ";
                    $options_input = trim(fgets(STDIN));
                    $options_array = array_map('trim', explode(',', $options_input));
                    $options_json = json_encode($options_array);
                }
                
                // Insert field
                $field_sql = "INSERT INTO template_fields (template_id, field_label, field_name, field_type, options_json, is_required, display_order) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $field_stmt = $pdo->prepare($field_sql);
                $field_stmt->execute([
                    $template_id,
                    $field_label,
                    $auto_name,
                    $field_type,
                    $options_json,
                    $is_required ? 1 : 0,
                    $field_count
                ]);
                
                echo "✅ Field added!\n";
                
                echo "Add another field? (y/n): ";
                if (trim(fgets(STDIN)) !== 'y') {
                    break;
                }
            }
        }
        
        $pdo->commit();
        echo "\n🎉 Template creation completed successfully!\n";
        
        // Show created template
        $created_template = getTemplateWithFields($pdo, $template_id);
        echo "\n📊 Template Summary:\n";
        echo "Name: {$created_template['step_name']}\n";
        echo "Description: {$created_template['step_description']}\n";
        echo "Fields: " . count($created_template['fields']) . "\n";
        
        foreach ($created_template['fields'] as $field) {
            echo "  - {$field['field_label']} ({$field['field_type']}" . ($field['is_required'] ? ', required' : '') . ")\n";
        }
        
    } catch (Exception $e) {
        $pdo->rollback();
        echo "❌ Error: " . $e->getMessage() . "\n";
    }
}

function deleteTestTemplates() {
    global $pdo;
    
    echo "\n🗑️  DELETE TEST TEMPLATES\n";
    echo str_repeat("=", 50) . "\n";
    
    // Find templates with "CLI Test" or "Test" in name
    $stmt = $pdo->prepare("SELECT template_id, step_name, created_at FROM step_templates WHERE step_name LIKE '%Test%' OR step_name LIKE '%CLI%'");
    $stmt->execute();
    $test_templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($test_templates)) {
        echo "No test templates found.\n";
        return;
    }
    
    echo "Found test templates:\n";
    foreach ($test_templates as $template) {
        echo "  {$template['template_id']}: {$template['step_name']} (created: {$template['created_at']})\n";
    }
    
    echo "\nDelete all test templates? (y/n): ";
    $confirm = trim(fgets(STDIN));
    
    if (strtolower($confirm) === 'y') {
        $deleted_count = 0;
        foreach ($test_templates as $template) {
            if (deleteTemplate($pdo, $template['template_id'])) {
                echo "✅ Deleted: {$template['step_name']}\n";
                $deleted_count++;
            } else {
                echo "❌ Failed to delete: {$template['step_name']}\n";
            }
        }
        echo "\n🎉 Deleted $deleted_count test templates.\n";
    } else {
        echo "Operation cancelled.\n";
    }
}

function showTemplateStatistics() {
    global $pdo;
    
    echo "\n📊 TEMPLATE STATISTICS\n";
    echo str_repeat("=", 50) . "\n";
    
    // Count templates
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM step_templates");
    $total_templates = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) as active FROM step_templates WHERE is_active = 1");
    $active_templates = $stmt->fetchColumn();
    
    // Count fields
    $stmt = $pdo->query("SELECT COUNT(*) as total_fields FROM template_fields");
    $total_fields = $stmt->fetchColumn();
    
    // Count by field type
    $stmt = $pdo->query("SELECT field_type, COUNT(*) as count FROM template_fields GROUP BY field_type");
    $field_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "📋 Templates:\n";
    echo "  Total: $total_templates\n";
    echo "  Active: $active_templates\n";
    echo "  Inactive: " . ($total_templates - $active_templates) . "\n\n";
    
    echo "🔧 Fields:\n";
    echo "  Total Fields: $total_fields\n";
    echo "  Average per Template: " . ($total_templates > 0 ? round($total_fields / $total_templates, 1) : 0) . "\n\n";
    
    echo "📊 Field Types:\n";
    foreach ($field_types as $type) {
        echo "  {$type['field_type']}: {$type['count']}\n";
    }
    
    // Recent templates
    $stmt = $pdo->query("SELECT step_name, created_at FROM step_templates ORDER BY created_at DESC LIMIT 5");
    $recent = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\n🕒 Recent Templates:\n";
    foreach ($recent as $template) {
        echo "  {$template['step_name']} (created: {$template['created_at']})\n";
    }
}

function validateFieldData() {
    echo "\n🔍 FIELD DATA VALIDATION TESTER\n";
    echo str_repeat("=", 50) . "\n";
    
    echo "Enter field label: ";
    $field_label = trim(fgets(STDIN));
    
    echo "Enter field name: ";
    $field_name = trim(fgets(STDIN));
    
    echo "Enter field type (text/number/textarea/select/date): ";
    $field_type = trim(fgets(STDIN));
    
    echo "Required field? (y/n): ";
    $is_required = trim(fgets(STDIN)) === 'y';
    
    $options_json = null;
    if ($field_type === 'select') {
        echo "Enter options JSON (e.g., [\"Option 1\", \"Option 2\"]): ";
        $options_json = trim(fgets(STDIN));
    }
    
    $field_data = [
        'field_label' => $field_label,
        'field_name' => $field_name,
        'field_type' => $field_type,
        'is_required' => $is_required,
        'options_json' => $options_json
    ];
    
    echo "\n🧪 Validating field data...\n";
    $validation = validateTemplateField($field_data);
    
    if ($validation['valid']) {
        echo "✅ Field data is VALID!\n";
        echo "Field summary:\n";
        echo "  Label: $field_label\n";
        echo "  Name: $field_name\n";
        echo "  Type: $field_type\n";
        echo "  Required: " . ($is_required ? 'Yes' : 'No') . "\n";
        if ($options_json) {
            echo "  Options: $options_json\n";
        }
    } else {
        echo "❌ Field data is INVALID!\n";
        echo "Errors:\n";
        foreach ($validation['errors'] as $error) {
            echo "  - $error\n";
        }
    }
}

function testJsonProcessing() {
    echo "\n🧬 JSON PROCESSING TESTER\n";
    echo str_repeat("=", 50) . "\n";
    
    echo "Enter JSON string to test: ";
    $json_input = trim(fgets(STDIN));
    
    echo "\n🧪 Testing JSON...\n";
    
    $decoded = json_decode($json_input, true);
    $json_error = json_last_error();
    
    if ($json_error === JSON_ERROR_NONE) {
        echo "✅ JSON is VALID!\n";
        echo "Decoded data:\n";
        print_r($decoded);
        
        if (is_array($decoded)) {
            echo "Array with " . count($decoded) . " elements\n";
        }
    } else {
        echo "❌ JSON is INVALID!\n";
        echo "Error: " . json_last_error_msg() . "\n";
    }
}

function listAllTemplates() {
    global $pdo;
    
    echo "\n📋 ALL TEMPLATES\n";
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
        echo "Description: " . (empty($template['step_description']) ? 'No description' : $template['step_description']) . "\n";
        echo "Fields: $field_count\n";
        echo "Created: {$template['created_at']}\n";
        echo str_repeat("-", 30) . "\n";
    }
}

function testSpecificTemplate() {
    global $pdo;
    
    echo "\n🎯 TEST SPECIFIC TEMPLATE\n";
    echo str_repeat("=", 50) . "\n";
    
    echo "Enter template ID: ";
    $template_id = (int) trim(fgets(STDIN));
    
    if ($template_id <= 0) {
        echo "❌ Invalid template ID!\n";
        return;
    }
    
    $template = getTemplateWithFields($pdo, $template_id);
    
    if (!$template) {
        echo "❌ Template not found!\n";
        return;
    }
    
    echo "✅ Template found!\n\n";
    echo "📊 Template Details:\n";
    echo "ID: {$template['template_id']}\n";
    echo "Name: {$template['step_name']}\n";
    echo "Description: " . ($template['step_description'] ?: 'No description') . "\n";
    echo "Active: " . ($template['is_active'] ? 'Yes' : 'No') . "\n";
    echo "Created: {$template['created_at']}\n";
    echo "Fields: " . count($template['fields']) . "\n\n";
    
    if (!empty($template['fields'])) {
        echo "🔧 Fields:\n";
        foreach ($template['fields'] as $field) {
            echo "  #{$field['display_order']}: {$field['field_label']}\n";
            echo "    Name: {$field['field_name']}\n";
            echo "    Type: {$field['field_type']}\n";
            echo "    Required: " . ($field['is_required'] ? 'Yes' : 'No') . "\n";
            if ($field['field_type'] === 'select' && $field['options']) {
                echo "    Options: " . implode(', ', $field['options']) . "\n";
            }
            echo "\n";
        }
    }
}

function simulateFormSubmission() {
    echo "\n🔄 SIMULATE FORM SUBMISSION\n";
    echo str_repeat("=", 50) . "\n";
    
    echo "This simulates the data that would be sent from the form.\n\n";
    
    // Generate sample form data
    $sample_data = [
        'step_name' => 'Simulated Template ' . date('Y-m-d H:i:s'),
        'step_description' => 'This is a simulated template submission',
        'fields_data' => json_encode([
            [
                'field_label' => 'Product Weight',
                'field_name' => 'product_weight',
                'field_type' => 'number',
                'is_required' => true,
                'display_order' => 1,
                'options_json' => null
            ],
            [
                'field_label' => 'Quality Status',
                'field_name' => 'quality_status',
                'field_type' => 'select',
                'is_required' => true,
                'display_order' => 2,
                'options_json' => '["Pass", "Fail", "Needs Review"]'
            ]
        ])
    ];
    
    echo "📝 Simulated Form Data:\n";
    echo "Step Name: {$sample_data['step_name']}\n";
    echo "Step Description: {$sample_data['step_description']}\n";
    echo "Fields Data: {$sample_data['fields_data']}\n\n";
    
    echo "🧪 Processing data...\n";
    
    // Validate data (same as controller would do)
    if (empty(trim($sample_data['step_name']))) {
        echo "❌ Validation failed: Step name required\n";
        return;
    }
    
    $fields_data = json_decode($sample_data['fields_data'], true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "❌ Validation failed: Invalid JSON in fields data\n";
        return;
    }
    
    foreach ($fields_data as $index => $field) {
        $validation = validateTemplateField($field);
        if (!$validation['valid']) {
            echo "❌ Validation failed for field " . ($index + 1) . ": " . implode(', ', $validation['errors']) . "\n";
            return;
        }
    }
    
    echo "✅ All validations passed!\n";
    echo "🎉 Form submission would be successful.\n";
    
    echo "\nActually create this template? (y/n): ";
    $create = trim(fgets(STDIN));
    
    if (strtolower($create) === 'y') {
        // Actually create the template
        global $pdo;
        
        try {
            $pdo->beginTransaction();
            
            $template_id = createTemplate($pdo, $sample_data['step_name'], $sample_data['step_description'], 3);
            
            $field_sql = "INSERT INTO template_fields (template_id, field_label, field_name, field_type, options_json, is_required, display_order) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $field_stmt = $pdo->prepare($field_sql);
            
            foreach ($fields_data as $field) {
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
            echo "✅ Template created with ID: $template_id\n";
            
        } catch (Exception $e) {
            $pdo->rollback();
            echo "❌ Error creating template: " . $e->getMessage() . "\n";
        }
    }
}

// Main loop
while (true) {
    showMenu();
    $choice = trim(fgets(STDIN));
    
    switch ($choice) {
        case '1':
            runFullTestSuite();
            break;
        case '2':
            createTestTemplate();
            break;
        case '3':
            deleteTestTemplates();
            break;
        case '4':
            showTemplateStatistics();
            break;
        case '5':
            validateFieldData();
            break;
        case '6':
            testJsonProcessing();
            break;
        case '7':
            listAllTemplates();
            break;
        case '8':
            testSpecificTemplate();
            break;
        case '9':
            simulateFormSubmission();
            break;
        case '0':
            echo "\n👋 Goodbye!\n";
            exit(0);
        default:
            echo "\n❌ Invalid choice. Please select 0-9.\n";
    }
    
    echo "\nPress Enter to continue...";
    fgets(STDIN);
}
?>
