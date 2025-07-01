<?php
/**
 * DPTI Rocket System - Template Functions
 * 
 * This file contains functions for managing dynamic production step templates.
 * All database operations use PDO prepared statements for security.
 * 
 * Functions:
 * - getAllActiveTemplates($pdo): Get all active step templates
 * - getTemplateWithFields($pdo, $template_id): Get template with all its fields
 * 
 * @version 1.0
 * @date July 1, 2025
 */

/**
 * Get all active step templates
 * 
 * Retrieves all step templates where is_active = 1, ordered by step_name.
 * This function is used to populate dropdowns for step type selection.
 * 
 * @param PDO $pdo Database connection object
 * @return array Array of template objects with template_id, step_name, and step_description
 * @throws PDOException If database query fails
 */
function getAllActiveTemplates($pdo) {
    try {
        // Prepare SQL query to get active templates ordered by name
        $sql = "SELECT 
                    template_id, 
                    step_name, 
                    step_description, 
                    created_by, 
                    created_at 
                FROM step_templates 
                WHERE is_active = 1 
                ORDER BY step_name ASC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        
        // Fetch all results as associative array
        $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $templates;
        
    } catch (PDOException $e) {
        // Log error for debugging (in production, use proper logging)
        error_log("Error in getAllActiveTemplates(): " . $e->getMessage());
        
        // Return empty array on error to prevent application break
        return [];
    }
}

/**
 * Get a specific template with all its fields
 * 
 * Retrieves template details and all associated fields for a given template_id.
 * Fields are ordered by display_order to maintain proper form field sequence.
 * 
 * @param PDO $pdo Database connection object
 * @param int $template_id The template ID to retrieve
 * @return array|false Returns associative array with template data and fields, or false if not found
 * @throws PDOException If database query fails
 */
function getTemplateWithFields($pdo, $template_id) {
    try {
        // First, get the template details
        $template_sql = "SELECT 
                            template_id, 
                            step_name, 
                            step_description, 
                            is_active, 
                            created_by, 
                            created_at 
                        FROM step_templates 
                        WHERE template_id = ? AND is_active = 1";
        
        $template_stmt = $pdo->prepare($template_sql);
        $template_stmt->execute([$template_id]);
        
        $template = $template_stmt->fetch(PDO::FETCH_ASSOC);
        
        // If template not found or inactive, return false
        if (!$template) {
            return false;
        }
        
        // Now get all fields for this template, ordered by display_order
        $fields_sql = "SELECT 
                        field_id, 
                        template_id, 
                        field_label, 
                        field_name, 
                        field_type, 
                        options_json, 
                        is_required, 
                        display_order 
                    FROM template_fields 
                    WHERE template_id = ? 
                    ORDER BY display_order ASC, field_id ASC";
        
        $fields_stmt = $pdo->prepare($fields_sql);
        $fields_stmt->execute([$template_id]);
        
        $fields = $fields_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Parse JSON options for select fields
        foreach ($fields as &$field) {
            if ($field['field_type'] === 'select' && !empty($field['options_json'])) {
                $field['options'] = json_decode($field['options_json'], true);
                
                // If JSON decode fails, set empty array
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $field['options'] = [];
                }
            } else {
                $field['options'] = null;
            }
            
            // Convert boolean fields to proper boolean type
            $field['is_required'] = (bool) $field['is_required'];
        }
        
        // Add fields array to template data
        $template['fields'] = $fields;
        
        return $template;
        
    } catch (PDOException $e) {
        // Log error for debugging (in production, use proper logging)
        error_log("Error in getTemplateWithFields(): " . $e->getMessage());
        
        // Return false on error
        return false;
    }
}

/**
 * Helper function to validate template field data
 * 
 * Validates field data structure for template creation/update operations.
 * This function can be used before inserting or updating template fields.
 * 
 * @param array $field_data Associative array containing field data
 * @return array Array with 'valid' boolean and 'errors' array
 */
function validateTemplateField($field_data) {
    $errors = [];
    $valid_types = ['text', 'number', 'textarea', 'select', 'date'];
    
    // Check required fields
    if (empty($field_data['field_label'])) {
        $errors[] = "Field label is required";
    }
    
    if (empty($field_data['field_name'])) {
        $errors[] = "Field name is required";
    } elseif (!preg_match('/^[a-z_][a-z0-9_]*$/', $field_data['field_name'])) {
        $errors[] = "Field name must be lowercase, start with letter or underscore, and contain only letters, numbers, and underscores";
    }
    
    if (empty($field_data['field_type']) || !in_array($field_data['field_type'], $valid_types)) {
        $errors[] = "Invalid field type. Must be one of: " . implode(', ', $valid_types);
    }
    
    // For select fields, options_json should contain valid options
    if ($field_data['field_type'] === 'select') {
        if (empty($field_data['options_json'])) {
            $errors[] = "Select fields must have options defined";
        } else {
            $options = json_decode($field_data['options_json'], true);
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($options) || empty($options)) {
                $errors[] = "Select field options must be a valid non-empty JSON array";
            }
        }
    }
    
    // Validate display_order is numeric
    if (isset($field_data['display_order']) && !is_numeric($field_data['display_order'])) {
        $errors[] = "Display order must be a number";
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * Get template field count for a specific template
 * 
 * Returns the number of fields associated with a template.
 * Useful for validation and UI display purposes.
 * 
 * @param PDO $pdo Database connection object
 * @param int $template_id The template ID
 * @return int Number of fields, or 0 on error
 */
function getTemplateFieldCount($pdo, $template_id) {
    try {
        $sql = "SELECT COUNT(*) as field_count FROM template_fields WHERE template_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$template_id]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) $result['field_count'];
        
    } catch (PDOException $e) {
        error_log("Error in getTemplateFieldCount(): " . $e->getMessage());
        return 0;
    }
}

/**
 * Check if template name already exists
 * 
 * Validates that a step name is unique before creating a new template.
 * 
 * @param PDO $pdo Database connection object
 * @param string $step_name The step name to check
 * @param int|null $exclude_template_id Optional template ID to exclude from check (for updates)
 * @return bool True if name exists, false if available
 */
function templateNameExists($pdo, $step_name, $exclude_template_id = null) {
    try {
        $sql = "SELECT COUNT(*) as name_count FROM step_templates WHERE step_name = ?";
        $params = [$step_name];
        
        // If excluding a specific template (for updates), add WHERE clause
        if ($exclude_template_id !== null) {
            $sql .= " AND template_id != ?";
            $params[] = $exclude_template_id;
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return ((int) $result['name_count']) > 0;
        
    } catch (PDOException $e) {
        error_log("Error in templateNameExists(): " . $e->getMessage());
        return true; // Return true on error to be safe (assume name exists)
    }
}

?>
