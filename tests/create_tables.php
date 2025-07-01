<?php
// Create tables using PHP
try {
    $pdo = new PDO('mysql:host=localhost;dbname=dpti_rocket_prod', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Creating step_templates table...\n";
    
    // Create step_templates table
    $sql1 = "CREATE TABLE step_templates (
        template_id INT AUTO_INCREMENT PRIMARY KEY,
        step_name VARCHAR(100) NOT NULL UNIQUE,
        step_description TEXT NULL,
        is_active BOOLEAN NOT NULL DEFAULT 1,
        created_by INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        
        CONSTRAINT fk_step_templates_created_by 
            FOREIGN KEY (created_by) REFERENCES users(user_id)
            ON DELETE RESTRICT ON UPDATE CASCADE,
        
        INDEX idx_step_templates_active (is_active),
        INDEX idx_step_templates_name (step_name)
    )";
    
    $pdo->exec($sql1);
    echo "step_templates table created successfully!\n";
    
    echo "Creating template_fields table...\n";
    
    // Create template_fields table
    $sql2 = "CREATE TABLE template_fields (
        field_id INT AUTO_INCREMENT PRIMARY KEY,
        template_id INT NOT NULL,
        field_label VARCHAR(100) NOT NULL,
        field_name VARCHAR(50) NOT NULL,
        field_type ENUM('text', 'number', 'textarea', 'select', 'date') NOT NULL,
        options_json JSON NULL,
        is_required BOOLEAN NOT NULL DEFAULT 0,
        display_order INT NOT NULL DEFAULT 0,
        
        CONSTRAINT fk_template_fields_template_id 
            FOREIGN KEY (template_id) REFERENCES step_templates(template_id)
            ON DELETE CASCADE ON UPDATE CASCADE,
        
        CONSTRAINT uk_template_field_name 
            UNIQUE (template_id, field_name),
        
        INDEX idx_template_fields_template_id (template_id),
        INDEX idx_template_fields_display_order (template_id, display_order)
    )";
    
    $pdo->exec($sql2);
    echo "template_fields table created successfully!\n";
    
    echo "\nBoth tables created successfully!\n";
    
} catch (Exception $e) {
    echo "Error creating tables: " . $e->getMessage() . "\n";
}
?>
