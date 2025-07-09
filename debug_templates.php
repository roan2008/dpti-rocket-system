<?php
require_once 'includes/db_connect.php';

echo "=== ALL TEMPLATES ===\n";
$stmt = $pdo->query('SELECT template_id, step_name, is_active FROM step_templates ORDER BY template_id');
while($row = $stmt->fetch()) {
    echo "ID: {$row['template_id']}, Name: {$row['step_name']}, Active: {$row['is_active']}\n";
}

echo "\n=== TEMPLATE FIELDS ===\n";
$stmt = $pdo->query('SELECT template_id, field_label, field_type, options_json FROM template_fields ORDER BY template_id, display_order');
while($row = $stmt->fetch()) {
    echo "Template: {$row['template_id']}, Field: {$row['field_label']}, Type: {$row['field_type']}, Options: {$row['options_json']}\n";
}
?>
