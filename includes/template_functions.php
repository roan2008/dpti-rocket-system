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
 * - createTemplate($pdo, $step_name, $step_description, $created_by): Create a new template
 * - updateTemplate($pdo, $template_id, $step_name, $step_description): Update an existing template
 * - deleteTemplate($pdo, $template_id): Delete a template
 * - updateTemplateStatus($pdo, $template_id, $is_active): Update template status (active/inactive)
 * - getAllTemplates($pdo): Get all templates (including inactive ones)
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
            $validation_result = validateSelectFieldOptions($field_data['options_json']);
            if (!$validation_result['valid']) {
                $errors = array_merge($errors, $validation_result['errors']);
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
 * Validate select field options JSON structure
 * 
 * Validates that the options_json for select fields contains a proper array of strings.
 * Performs comprehensive validation including JSON syntax, structure, and content.
 * 
 * @param string $options_json The JSON string containing select options
 * @return array Array with 'valid' boolean and 'errors' array
 */
function validateSelectFieldOptions($options_json) {
    $errors = [];
    
    // Check if options_json is provided
    if (empty($options_json)) {
        $errors[] = "Select field options cannot be empty";
        return ['valid' => false, 'errors' => $errors];
    }
    
    // Validate JSON syntax
    $options = json_decode($options_json, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $errors[] = "Invalid JSON syntax in options: " . json_last_error_msg();
        return ['valid' => false, 'errors' => $errors];
    }
    
    // Check if it's an array
    if (!is_array($options)) {
        $errors[] = "Select field options must be a JSON array, not " . gettype($options);
        return ['valid' => false, 'errors' => $errors];
    }
    
    // Check if array is not empty
    if (empty($options)) {
        $errors[] = "Select field options array cannot be empty";
        return ['valid' => false, 'errors' => $errors];
    }
    
    // Check if it's an indexed array (not associative array from JSON object)
    if (array_keys($options) !== range(0, count($options) - 1)) {
        $errors[] = "Select field options must be a JSON array, not a JSON object";
        return ['valid' => false, 'errors' => $errors];
    }
    
    // Check if array has too many options (reasonable limit)
    if (count($options) > 50) {
        $errors[] = "Select field cannot have more than 50 options (found " . count($options) . ")";
        return ['valid' => false, 'errors' => $errors];
    }
    
    // Validate each option
    $seen_options = [];
    foreach ($options as $index => $option) {
        // Check if option is a string
        if (!is_string($option)) {
            $errors[] = "Option at index $index must be a string, not " . gettype($option);
            continue;
        }
        
        // Check if option is not empty
        if (trim($option) === '') {
            $errors[] = "Option at index $index cannot be empty or contain only whitespace";
            continue;
        }
        
        // Check option length (reasonable limit)
        if (strlen($option) > 100) {
            $errors[] = "Option at index $index is too long (max 100 characters)";
            continue;
        }
        
        // Check for duplicates (case-insensitive)
        $option_lower = strtolower(trim($option));
        if (in_array($option_lower, $seen_options)) {
            $errors[] = "Duplicate option found: '$option' (options must be unique)";
            continue;
        }
        $seen_options[] = $option_lower;
    }
    
    // Additional validation for common patterns
    $has_useful_options = false;
    foreach ($options as $option) {
        if (strlen(trim($option)) >= 2) {
            $has_useful_options = true;
            break;
        }
    }
    
    if (!$has_useful_options) {
        $errors[] = "Select field must have at least one meaningful option (2+ characters)";
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * Enhanced JSON validation with structural checks
 * 
 * Provides comprehensive JSON validation with detailed error reporting
 * and structural validation for different data types.
 * 
 * @param string $json_string The JSON string to validate
 * @param string $expected_type Expected type: 'array', 'object', 'string_array', etc.
 * @return array Array with validation results and detailed errors
 */
function validateJsonStructure($json_string, $expected_type = 'array') {
    $errors = [];
    
    // Basic JSON syntax validation
    if (empty($json_string)) {
        $errors[] = "JSON string cannot be empty";
        return ['valid' => false, 'errors' => $errors, 'data' => null];
    }
    
    $decoded = json_decode($json_string, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $errors[] = "Invalid JSON syntax: " . json_last_error_msg();
        return ['valid' => false, 'errors' => $errors, 'data' => null];
    }
    
    // Type-specific validation
    switch ($expected_type) {
        case 'array':
            if (!is_array($decoded)) {
                $errors[] = "Expected JSON array, got " . gettype($decoded);
            } elseif (empty($decoded)) {
                $errors[] = "JSON array cannot be empty";
            }
            break;
            
        case 'string_array':
            if (!is_array($decoded)) {
                $errors[] = "Expected JSON array, got " . gettype($decoded);
            } elseif (empty($decoded)) {
                $errors[] = "JSON array cannot be empty";
            } else {
                foreach ($decoded as $index => $item) {
                    if (!is_string($item)) {
                        $errors[] = "Array item at index $index must be a string, got " . gettype($item);
                    }
                }
            }
            break;
            
        case 'object':
            if (!is_array($decoded) || array_keys($decoded) === range(0, count($decoded) - 1)) {
                $errors[] = "Expected JSON object, got " . (is_array($decoded) ? 'indexed array' : gettype($decoded));
            }
            break;
            
        case 'non_empty_array':
            if (!is_array($decoded)) {
                $errors[] = "Expected JSON array, got " . gettype($decoded);
            } elseif (empty($decoded)) {
                $errors[] = "JSON array cannot be empty";
            }
            break;
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors,
        'data' => $decoded
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

/**
 * Create a new template
 * 
 * Creates a new step template in the database.
 * 
 * @param PDO $pdo Database connection object
 * @param string $step_name The name of the step
 * @param string $step_description The description of the step
 * @param int $created_by User ID of the creator
 * @return int|false Template ID if successful, false on failure
 */
function createTemplate($pdo, $step_name, $step_description, $created_by) {
    try {
        $sql = "INSERT INTO step_templates (step_name, step_description, is_active, created_by) VALUES (?, ?, 1, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$step_name, $step_description, $created_by]);
        
        return $pdo->lastInsertId();
        
    } catch (PDOException $e) {
        error_log("Error in createTemplate(): " . $e->getMessage());
        return false;
    }
}

/**
 * Update an existing template
 * 
 * Updates the name and description of an existing template.
 * 
 * @param PDO $pdo Database connection object
 * @param int $template_id The template ID to update
 * @param string $step_name The new step name
 * @param string $step_description The new step description
 * @return bool True if successful, false on failure
 */
function updateTemplate($pdo, $template_id, $step_name, $step_description) {
    try {
        $sql = "UPDATE step_templates SET step_name = ?, step_description = ? WHERE template_id = ?";
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([$step_name, $step_description, $template_id]);
        
        // Return true if the query executed successfully, regardless of whether rows were affected
        // (No changes needed if data is the same)
        return $result;
        
    } catch (PDOException $e) {
        error_log("Error in updateTemplate(): " . $e->getMessage());
        return false;
    }
}

/**
 * Delete a template
 * 
 * Deletes a template and all its associated fields (CASCADE DELETE).
 * 
 * @param PDO $pdo Database connection object
 * @param int $template_id The template ID to delete
 * @return bool True if successful, false on failure
 */
function deleteTemplate($pdo, $template_id) {
    try {
        $sql = "DELETE FROM step_templates WHERE template_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$template_id]);
        
        return $stmt->rowCount() > 0;
        
    } catch (PDOException $e) {
        error_log("Error in deleteTemplate(): " . $e->getMessage());
        return false;
    }
}

/**
 * Update template status (active/inactive)
 * 
 * Updates the is_active status of a template.
 * 
 * @param PDO $pdo Database connection object
 * @param int $template_id The template ID to update
 * @param bool $is_active New active status
 * @return bool True if successful, false on failure
 */
function updateTemplateStatus($pdo, $template_id, $is_active) {
    try {
        $sql = "UPDATE step_templates SET is_active = ? WHERE template_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([(int) $is_active, $template_id]);
        
        return $stmt->rowCount() > 0;
        
    } catch (PDOException $e) {
        error_log("Error in updateTemplateStatus(): " . $e->getMessage());
        return false;
    }
}

/**
 * Get all templates (including inactive ones)
 * 
 * Retrieves all templates regardless of status, useful for admin views.
 * 
 * @param PDO $pdo Database connection object
 * @return array Array of all template objects
 */
function getAllTemplates($pdo) {
    try {
        $sql = "SELECT 
                    template_id, 
                    step_name, 
                    step_description, 
                    is_active, 
                    created_by, 
                    created_at 
                FROM step_templates 
                ORDER BY step_name ASC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        
        $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Convert is_active to boolean
        foreach ($templates as &$template) {
            $template['is_active'] = (bool) $template['is_active'];
        }
        
        return $templates;
        
    } catch (PDOException $e) {
        error_log("Error in getAllTemplates(): " . $e->getMessage());
        return [];
    }
}

/**
 * Search and filter templates
 * 
 * @param PDO $pdo Database connection object
 * @param string $search_term Search term for step name or description
 * @param string $status_filter Filter by active status (active, inactive, all)
 * @param string $creator_filter Filter by creator user ID
 * @param string $date_from Filter by date from (YYYY-MM-DD)
 * @param string $date_to Filter by date to (YYYY-MM-DD)
 * @param string $sort_by Sort field (step_name, created_at, created_by)
 * @param string $sort_order Sort order (ASC or DESC)
 * @return array Array of filtered templates
 */
function search_templates($pdo, $search_term = '', $status_filter = 'all', $creator_filter = '', $date_from = '', $date_to = '', $sort_by = 'step_name', $sort_order = 'ASC') {
    try {
        // Build the WHERE clause
        $where_conditions = [];
        $params = [];
        
        // Search term for step name or description
        if (!empty($search_term)) {
            $where_conditions[] = "(step_name LIKE ? OR step_description LIKE ?)";
            $search_param = '%' . $search_term . '%';
            $params[] = $search_param;
            $params[] = $search_param;
        }
        
        // Status filter
        if ($status_filter === 'active') {
            $where_conditions[] = "is_active = 1";
        } elseif ($status_filter === 'inactive') {
            $where_conditions[] = "is_active = 0";
        }
        // 'all' means no status filter
        
        // Creator filter
        if (!empty($creator_filter)) {
            $where_conditions[] = "created_by = ?";
            $params[] = $creator_filter;
        }
        
        // Date range filter
        if (!empty($date_from)) {
            $where_conditions[] = "DATE(created_at) >= ?";
            $params[] = $date_from;
        }
        
        if (!empty($date_to)) {
            $where_conditions[] = "DATE(created_at) <= ?";
            $params[] = $date_to;
        }
        
        // Build the complete query
        $sql = "SELECT st.template_id, st.step_name, st.step_description, st.is_active, st.created_by, st.created_at, u.full_name as creator_name
                FROM step_templates st
                LEFT JOIN users u ON st.created_by = u.user_id";
        
        if (!empty($where_conditions)) {
            $sql .= " WHERE " . implode(' AND ', $where_conditions);
        }
        
        // Add sorting
        $allowed_sort_fields = ['step_name', 'created_at', 'created_by', 'is_active'];
        $sort_by = in_array($sort_by, $allowed_sort_fields) ? $sort_by : 'step_name';
        $sort_order = strtoupper($sort_order) === 'DESC' ? 'DESC' : 'ASC';
        
        $sql .= " ORDER BY st." . $sort_by . " " . $sort_order;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Convert is_active to boolean
        foreach ($templates as &$template) {
            $template['is_active'] = (bool) $template['is_active'];
        }
        
        return $templates;
        
    } catch (PDOException $e) {
        error_log("Search templates error: " . $e->getMessage());
        return array();
    }
}

/**
 * Get unique creators from templates table
 * 
 * @param PDO $pdo Database connection object
 * @return array Array of users who have created templates
 */
function get_template_creators($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT DISTINCT u.user_id, u.full_name, u.username 
            FROM step_templates st 
            INNER JOIN users u ON st.created_by = u.user_id 
            ORDER BY u.full_name
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Get template creators error: " . $e->getMessage());
        return array();
    }
}

/**
 * Count templates matching search criteria
 * 
 * @param PDO $pdo Database connection object
 * @param string $search_term Search term for step name or description
 * @param string $status_filter Filter by active status (active, inactive, all)
 * @param string $creator_filter Filter by creator user ID
 * @param string $date_from Filter by date from (YYYY-MM-DD)
 * @param string $date_to Filter by date to (YYYY-MM-DD)
 * @return int Number of templates matching criteria
 */
function count_filtered_templates($pdo, $search_term = '', $status_filter = 'all', $creator_filter = '', $date_from = '', $date_to = '') {
    try {
        // Build the WHERE clause
        $where_conditions = [];
        $params = [];
        
        // Search term for step name or description
        if (!empty($search_term)) {
            $where_conditions[] = "(step_name LIKE ? OR step_description LIKE ?)";
            $search_param = '%' . $search_term . '%';
            $params[] = $search_param;
            $params[] = $search_param;
        }
        
        // Status filter
        if ($status_filter === 'active') {
            $where_conditions[] = "is_active = 1";
        } elseif ($status_filter === 'inactive') {
            $where_conditions[] = "is_active = 0";
        }
        
        // Creator filter
        if (!empty($creator_filter)) {
            $where_conditions[] = "created_by = ?";
            $params[] = $creator_filter;
        }
        
        // Date range filter
        if (!empty($date_from)) {
            $where_conditions[] = "DATE(created_at) >= ?";
            $params[] = $date_from;
        }
        
        if (!empty($date_to)) {
            $where_conditions[] = "DATE(created_at) <= ?";
            $params[] = $date_to;
        }
        
        // Build the complete query
        $sql = "SELECT COUNT(*) FROM step_templates";
        
        if (!empty($where_conditions)) {
            $sql .= " WHERE " . implode(' AND ', $where_conditions);
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
        
    } catch (PDOException $e) {
        error_log("Count filtered templates error: " . $e->getMessage());
        return 0;
    }
}

?>
