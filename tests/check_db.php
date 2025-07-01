<?php
// Simple database check script
try {
    $pdo = new PDO('mysql:host=localhost;dbname=dpti_rocket_prod', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if step_templates table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'step_templates'");
    $step_templates_exists = $stmt->rowCount() > 0;
    
    // Check if template_fields table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'template_fields'");
    $template_fields_exists = $stmt->rowCount() > 0;
    
    echo "Database connection: SUCCESS\n";
    echo "step_templates table exists: " . ($step_templates_exists ? 'YES' : 'NO') . "\n";
    echo "template_fields table exists: " . ($template_fields_exists ? 'YES' : 'NO') . "\n";
    
    if (!$step_templates_exists || !$template_fields_exists) {
        echo "\nNeed to create tables first. Run the SQL schema file.\n";
    }
    
} catch (Exception $e) {
    echo "Database connection failed: " . $e->getMessage() . "\n";
}
?>
