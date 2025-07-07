<?php
echo "=== TEMPLATE FIELDS DIAGNOSTIC ===\n\n";

try {
    require_once '../includes/db_connect.php';
    
    echo "1. Checking step_templates table:\n";
    $stmt = $pdo->query("SELECT template_id, step_name, is_active FROM step_templates ORDER BY template_id");
    $templates = $stmt->fetchAll();
    
    foreach ($templates as $template) {
        $status = $template['is_active'] ? 'Active' : 'Inactive';
        echo "   Template {$template['template_id']}: {$template['step_name']} ({$status})\n";
    }
    
    echo "\n2. Checking template_fields table:\n";
    $stmt = $pdo->query("SELECT template_id, COUNT(*) as field_count FROM template_fields GROUP BY template_id ORDER BY template_id");
    $field_counts = $stmt->fetchAll();
    
    if (empty($field_counts)) {
        echo "   ⚠️  No fields found in template_fields table!\n";
        echo "   This means all templates have 0 fields defined.\n\n";
        
        echo "3. Sample template_fields table structure:\n";
        $stmt = $pdo->query("DESCRIBE template_fields");
        $columns = $stmt->fetchAll();
        foreach ($columns as $col) {
            echo "   - {$col['Field']}: {$col['Type']}\n";
        }
        
    } else {
        foreach ($field_counts as $count) {
            echo "   Template {$count['template_id']}: {$count['field_count']} fields\n";
        }
        
        echo "\n3. Sample fields from template_fields:\n";
        $stmt = $pdo->query("SELECT * FROM template_fields LIMIT 5");
        $sample_fields = $stmt->fetchAll();
        
        foreach ($sample_fields as $field) {
            echo "   Template {$field['template_id']}: {$field['field_label']} ({$field['field_type']})\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== DIAGNOSTIC COMPLETE ===\n";
?>
