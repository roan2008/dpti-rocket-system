<?php
// Add sample templates for testing
try {
    $pdo = new PDO('mysql:host=localhost;dbname=dpti_rocket_prod', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Adding sample templates for testing...\n";
    
    // Sample templates
    $sample_templates = [
        [
            'name' => 'Quality Control Inspection',
            'description' => 'Standard quality control inspection with measurements and pass/fail criteria',
            'created_by' => 3 // admin user
        ],
        [
            'name' => 'Component Assembly',
            'description' => 'Assembly of rocket components with torque specifications and verification',
            'created_by' => 4 // engineer user
        ],
        [
            'name' => 'Safety Check',
            'description' => 'Comprehensive safety inspection before testing or deployment',
            'created_by' => 3 // admin user
        ]
    ];
    
    $insert_sql = "INSERT INTO step_templates (step_name, step_description, is_active, created_by) VALUES (?, ?, 1, ?)";
    $stmt = $pdo->prepare($insert_sql);
    
    foreach ($sample_templates as $template) {
        // Check if template already exists
        $check_sql = "SELECT COUNT(*) FROM step_templates WHERE step_name = ?";
        $check_stmt = $pdo->prepare($check_sql);
        $check_stmt->execute([$template['name']]);
        
        if ($check_stmt->fetchColumn() == 0) {
            $stmt->execute([$template['name'], $template['description'], $template['created_by']]);
            $template_id = $pdo->lastInsertId();
            echo "Created template: {$template['name']} (ID: $template_id)\n";
            
            // Add some sample fields for the first template
            if ($template['name'] === 'Quality Control Inspection') {
                $field_sql = "INSERT INTO template_fields (template_id, field_label, field_name, field_type, is_required, display_order, options_json) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $field_stmt = $pdo->prepare($field_sql);
                
                $sample_fields = [
                    ['Length (mm)', 'length_mm', 'number', 1, 1, null],
                    ['Width (mm)', 'width_mm', 'number', 1, 2, null],
                    ['Weight (g)', 'weight_g', 'number', 1, 3, null],
                    ['Overall Status', 'overall_status', 'select', 1, 4, '["Pass", "Fail", "Needs Review"]'],
                    ['Inspector Notes', 'inspector_notes', 'textarea', 0, 5, null]
                ];
                
                foreach ($sample_fields as $field) {
                    $field_stmt->execute([
                        $template_id,
                        $field[0], // label
                        $field[1], // name
                        $field[2], // type
                        $field[3], // required
                        $field[4], // order
                        $field[5]  // options_json
                    ]);
                }
                echo "  - Added 5 sample fields\n";
            }
        } else {
            echo "Template '{$template['name']}' already exists, skipping...\n";
        }
    }
    
    echo "\nSample data setup complete!\n";
    
} catch (Exception $e) {
    echo "Error setting up sample data: " . $e->getMessage() . "\n";
}
?>
