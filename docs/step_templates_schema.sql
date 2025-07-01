-- DPTI Rocket System - Step Templates Database Schema
-- Phase 1: Dynamic Production Step System
-- Created: July 1, 2025

-- Table 1: step_templates
-- Master table for step types that can be created and managed by Admins and Engineers
CREATE TABLE step_templates (
    template_id INT AUTO_INCREMENT PRIMARY KEY,
    step_name VARCHAR(100) NOT NULL UNIQUE,
    step_description TEXT NULL,
    is_active BOOLEAN NOT NULL DEFAULT 1,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign Key Constraint
    CONSTRAINT fk_step_templates_created_by 
        FOREIGN KEY (created_by) REFERENCES users(user_id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    
    -- Index for performance
    INDEX idx_step_templates_active (is_active),
    INDEX idx_step_templates_name (step_name)
);

-- Table 2: template_fields
-- Stores individual form fields for each step template
CREATE TABLE template_fields (
    field_id INT AUTO_INCREMENT PRIMARY KEY,
    template_id INT NOT NULL,
    field_label VARCHAR(100) NOT NULL,
    field_name VARCHAR(50) NOT NULL,
    field_type ENUM('text', 'number', 'textarea', 'select', 'date') NOT NULL,
    options_json JSON NULL,
    is_required BOOLEAN NOT NULL DEFAULT 0,
    display_order INT NOT NULL DEFAULT 0,
    
    -- Foreign Key Constraint with CASCADE DELETE
    CONSTRAINT fk_template_fields_template_id 
        FOREIGN KEY (template_id) REFERENCES step_templates(template_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    
    -- Unique Constraint: template_id + field_name must be unique
    CONSTRAINT uk_template_field_name 
        UNIQUE (template_id, field_name),
    
    -- Indexes for performance
    INDEX idx_template_fields_template_id (template_id),
    INDEX idx_template_fields_display_order (template_id, display_order)
);

-- Insert some sample data for testing (optional)
-- You can uncomment these lines to add initial templates

/*
-- Sample template 1: Quality Control Check
INSERT INTO step_templates (step_name, step_description, created_by) 
VALUES ('Quality Control Check', 'Standard quality control inspection with measurements and pass/fail criteria', 1);

SET @qc_template_id = LAST_INSERT_ID();

INSERT INTO template_fields (template_id, field_label, field_name, field_type, is_required, display_order) VALUES
(@qc_template_id, 'Length (mm)', 'length_mm', 'number', 1, 1),
(@qc_template_id, 'Width (mm)', 'width_mm', 'number', 1, 2),
(@qc_template_id, 'Height (mm)', 'height_mm', 'number', 1, 3),
(@qc_template_id, 'Weight (g)', 'weight_g', 'number', 1, 4),
(@qc_template_id, 'Overall Status', 'overall_status', 'select', 1, 5),
(@qc_template_id, 'Inspector Notes', 'inspector_notes', 'textarea', 0, 6);

-- Add options for the select field
UPDATE template_fields 
SET options_json = JSON_ARRAY('Pass', 'Fail', 'Needs Review') 
WHERE template_id = @qc_template_id AND field_name = 'overall_status';

-- Sample template 2: Component Assembly
INSERT INTO step_templates (step_name, step_description, created_by) 
VALUES ('Component Assembly', 'Assembly of rocket components with torque specifications and verification', 1);

SET @assembly_template_id = LAST_INSERT_ID();

INSERT INTO template_fields (template_id, field_label, field_name, field_type, is_required, display_order) VALUES
(@assembly_template_id, 'Component Type', 'component_type', 'select', 1, 1),
(@assembly_template_id, 'Torque Specification (Nm)', 'torque_spec', 'number', 1, 2),
(@assembly_template_id, 'Actual Torque (Nm)', 'actual_torque', 'number', 1, 3),
(@assembly_template_id, 'Assembly Date', 'assembly_date', 'date', 1, 4),
(@assembly_template_id, 'Verification Status', 'verification_status', 'select', 1, 5),
(@assembly_template_id, 'Assembly Notes', 'assembly_notes', 'textarea', 0, 6);

-- Add options for select fields
UPDATE template_fields 
SET options_json = JSON_ARRAY('Engine Mount', 'Nose Cone', 'Body Tube', 'Recovery System', 'Fins') 
WHERE template_id = @assembly_template_id AND field_name = 'component_type';

UPDATE template_fields 
SET options_json = JSON_ARRAY('Verified', 'Failed Verification', 'Pending Review') 
WHERE template_id = @assembly_template_id AND field_name = 'verification_status';
*/
