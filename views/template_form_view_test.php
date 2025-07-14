<?php
/**
 * Template Form View - TEST VERSION (No Authentication)
 * Dynamic form for adding and editing step templates with field builder
 */

// Include required files
require_once '../includes/db_connect.php';
require_once '../includes/template_functions.php';

// Get template ID from URL for edit mode
$template_id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$edit_mode = !empty($template_id);

// If edit mode, get existing template data
$template = null;
if ($edit_mode) {
    $template = getTemplateWithFields($pdo, $template_id);
    if (!$template) {
        echo "Template not found!";
        exit;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // For testing purposes, just show a message
    $success_message = "Form submission test - data received successfully!";
    // In real implementation, we would process the form data here
}

include '../includes/header.php';
?>

<main class="main-content">
    <div class="container">
        <div class="page-header">
            <h1><?php echo $edit_mode ? 'Edit Template' : 'Add New Template'; ?></h1>
            <!-- Debug info for autotest -->
            <div style="display: none;" id="debug-edit-mode"><?php echo $edit_mode ? 'true' : 'false'; ?></div>
            <div style="display: none;" id="debug-template-id"><?php echo $template_id ?? 'null'; ?></div>
            <div class="page-actions">
                <a href="templates_list_view.php" class="btn btn-secondary">
                    ← Back to Templates
                </a>
            </div>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($success_message)): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h3>Template Information</h3>
            </div>
            <div class="card-body">
                <form id="template-form" method="POST">
                    <div class="form-group">
                        <label for="step_name" class="form-label">Step Name</label>
                        <input 
                            type="text" 
                            id="step_name" 
                            name="step_name" 
                            class="form-control" 
                            value="<?php echo $edit_mode ? htmlspecialchars($template['step_name']) : ''; ?>"
                            required
                        >
                        <small class="form-text">Enter a descriptive name for this production step</small>
                    </div>

                    <div class="form-group">
                        <label for="description" class="form-label">Description</label>
                        <textarea 
                            id="description" 
                            name="description" 
                            class="form-control" 
                            rows="3"
                        ><?php echo $edit_mode ? htmlspecialchars($template['description']) : ''; ?></textarea>
                        <small class="form-text">Optional description of what this step involves</small>
                    </div>

                    <div class="form-group">
                        <div class="form-section-header">
                            <h4>Form Fields</h4>
                            <button type="button" id="add-field-btn" class="btn btn-primary btn-sm">
                                + Add Field
                            </button>
                        </div>
                        
                        <div id="fields-container" class="fields-container">
                            <div id="empty-fields-message" class="empty-message">
                                No fields added yet. Click "Add Field" to create form fields for this template.
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-success">
                            <?php echo $edit_mode ? 'Update Template' : 'Create Template'; ?>
                        </button>
                        <a href="templates_list_view.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<style>
.fields-container {
    border: 2px dashed #ddd;
    border-radius: 8px;
    padding: 20px;
    min-height: 100px;
    margin-top: 10px;
    background-color: #fafafa;
}

.fields-container.has-fields {
    background-color: white;
    border: 1px solid #ddd;
    border-style: solid;
}

.field-row {
    background-color: white;
    border: 1px solid #e0e0e0;
    border-radius: 6px;
    margin-bottom: 15px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.field-row-header {
    background-color: #f8f9fa;
    padding: 12px 16px;
    border-bottom: 1px solid #e0e0e0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.field-row-number {
    background-color: #007bff;
    color: white;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 14px;
}

.field-row-title {
    font-weight: 600;
    color: #333;
}

.remove-field-btn {
    background-color: #dc3545;
    color: white;
    border: none;
    padding: 6px 12px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 12px;
}

.remove-field-btn:hover {
    background-color: #c82333;
}

.field-inputs {
    padding: 16px;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

.field-input-group {
    display: flex;
    flex-direction: column;
}

.field-input-group label {
    font-weight: 500;
    margin-bottom: 4px;
    color: #555;
    font-size: 13px;
}

.field-input-group input,
.field-input-group select,
.field-input-group textarea {
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 8px;
    font-size: 14px;
}

.field-input-group input:focus,
.field-input-group select:focus,
.field-input-group textarea:focus {
    outline: none;
    border-color: #007bff;
    box-shadow: 0 0 0 2px rgba(0,123,255,0.25);
}

.options-field {
    grid-column: 1 / -1;
}

.options-field textarea {
    height: 80px;
    resize: vertical;
}

.empty-message {
    text-align: center;
    color: #666;
    font-style: italic;
    padding: 40px 20px;
}

.form-section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.form-section-header h4 {
    margin: 0;
    color: #333;
}
</style>

<script>
// Global variables
let fieldCounter = 0;
const fieldsContainer = document.getElementById('fields-container');
const addFieldBtn = document.getElementById('add-field-btn');
const templateForm = document.getElementById('template-form');
const emptyMessage = document.getElementById('empty-fields-message');

// Initialize the form
document.addEventListener('DOMContentLoaded', function() {
    console.log('=== TEMPLATE FORM DEBUG START ===');
    console.log('Template Form initialized');
    console.log('Debug: Edit mode check');
    console.log('Edit mode:', <?php echo $edit_mode ? 'true' : 'false'; ?>);
    console.log('Template fields count:', <?php echo !empty($template['fields']) ? count($template['fields']) : 0; ?>);
    console.log('Current URL:', window.location.href);
    console.log('Template ID from PHP:', <?php echo $template_id ?? 'null'; ?>);
    
    // Make functions globally accessible for testing
    window.addField = addField;
    window.loadExistingFields = loadExistingFields;
    window.removeField = removeField;
    window.handleFormSubmit = handleFormSubmit;
    
    console.log('Functions made global:', {
        addField: typeof window.addField,
        loadExistingFields: typeof window.loadExistingFields,
        removeField: typeof window.removeField,
        handleFormSubmit: typeof window.handleFormSubmit
    });
    
    // Add event listeners
    addFieldBtn.addEventListener('click', addField);
    templateForm.addEventListener('submit', handleFormSubmit);
    
    // Event delegation for field type changes (works for dynamic fields)
    fieldsContainer.addEventListener('change', function(event) {
        if (event.target.classList.contains('field-type-select')) {
            const fieldId = event.target.dataset.fieldId;
            toggleOptionsField(fieldId);
        }
    });
    
    <?php if ($edit_mode && !empty($template['fields'])): ?>
        console.log('Debug: About to call loadExistingFields()');
        console.log('Template data available:', <?php echo json_encode($template); ?>);
        // Add small delay to ensure DOM is ready
        setTimeout(function() {
            console.log('Debug: DOM ready, calling loadExistingFields()');
            loadExistingFields();
            console.log('Debug: loadExistingFields() completed');
        }, 100);
    <?php else: ?>
        console.log('Debug: Not loading existing fields');
        console.log('- edit_mode:', <?php echo $edit_mode ? 'true' : 'false'; ?>);
        console.log('- fields_empty:', <?php echo empty($template['fields']) ? 'true' : 'false'; ?>);
        console.log('- template:', <?php echo json_encode($template); ?>);
    <?php endif; ?>
    
    console.log('=== TEMPLATE FORM DEBUG END ===');
});

/**
 * Add a new field row to the form
 */
function addField() {
    fieldCounter++;
    
    // Hide empty message
    if (emptyMessage) {
        emptyMessage.style.display = 'none';
    }
    fieldsContainer.classList.add('has-fields');
    
    const newFieldRow = document.createElement('div');
    newFieldRow.className = 'field-row';
    newFieldRow.dataset.fieldId = fieldCounter;
    
    newFieldRow.innerHTML = `
        <div class="field-row-header">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div class="field-row-number">${fieldCounter}</div>
                <div class="field-row-title">Form Field #${fieldCounter}</div>
            </div>
            <button type="button" class="remove-field-btn" onclick="removeField(${fieldCounter})">
                🗑️ Remove Field
            </button>
        </div>
        
        <div class="field-inputs">
            <div class="field-input-group">
                <label>Field Name</label>
                <input 
                    type="text" 
                    name="fields[${fieldCounter}][name]" 
                    placeholder="e.g., length_mm, inspector_name"
                    required
                >
            </div>
            
            <div class="field-input-group">
                <label>Field Type</label>
                <select 
                    name="fields[${fieldCounter}][type]" 
                    class="field-type-select"
                    data-field-id="${fieldCounter}"
                    required
                >
                    <option value="">Select type...</option>
                    <option value="text">Text Input</option>
                    <option value="number">Number Input</option>
                    <option value="email">Email Input</option>
                    <option value="tel">Phone Number</option>
                    <option value="date">Date Picker</option>
                    <option value="time">Time Picker</option>
                    <option value="datetime-local">Date & Time</option>
                    <option value="textarea">Text Area</option>
                    <option value="select">Dropdown (Select)</option>
                    <option value="radio">Radio Buttons</option>
                    <option value="checkbox">Checkboxes</option>
                </select>
            </div>
            
            <div class="field-input-group">
                <label>Label</label>
                <input 
                    type="text" 
                    name="fields[${fieldCounter}][label]" 
                    placeholder="e.g., Length (mm), Inspector Name"
                    required
                >
            </div>
            
            <div class="field-input-group">
                <label>Required?</label>
                <select name="fields[${fieldCounter}][required]">
                    <option value="0">Optional</option>
                    <option value="1">Required</option>
                </select>
            </div>
            
            <div class="field-input-group options-field" id="options-field-${fieldCounter}" style="display: none;">
                <label>Options</label>
                <textarea 
                    name="fields[${fieldCounter}][options]" 
                    placeholder="For select/radio/checkbox fields, enter each option on a new line:&#10;Option 1&#10;Option 2&#10;Option 3"
                ></textarea>
            </div>
        </div>
    `;
    
    fieldsContainer.appendChild(newFieldRow);
    console.log(`Added field #${fieldCounter}`);
}

/**
 * Remove a field row
 */
function removeField(fieldId) {
    const fieldRowToRemove = document.querySelector(`[data-field-id="${fieldId}"]`);
    if (fieldRowToRemove) {
        fieldRowToRemove.remove();
        console.log(`Removed field #${fieldId}`);
        
        // Show empty message if no fields left
        const remainingFields = fieldsContainer.querySelectorAll('.field-row');
        if (remainingFields.length === 0) {
            if (emptyMessage) {
                emptyMessage.style.display = 'block';
            }
            fieldsContainer.classList.remove('has-fields');
        }
    }
}

/**
 * Toggle options field visibility based on field type
 */
function toggleOptionsField(fieldId) {
    const fieldTypeSelect = document.querySelector(`[data-field-id="${fieldId}"]`);
    const optionsField = document.getElementById(`options-field-${fieldId}`);
    const selectedType = fieldTypeSelect.value;
    
    if (optionsField) {
        if (['select', 'radio', 'checkbox'].includes(selectedType)) {
            optionsField.style.display = 'block';
            optionsField.querySelector('textarea').required = true;
        } else {
            optionsField.style.display = 'none';
            optionsField.querySelector('textarea').required = false;
        }
    }
}

/**
 * Load existing fields for edit mode
 */
function loadExistingFields() {
    <?php if ($edit_mode && !empty($template['fields'])): ?>
        console.log('Loading existing fields for edit mode');
        
        // Hide empty message
        if (emptyMessage) {
            emptyMessage.style.display = 'none';
        }
        fieldsContainer.classList.add('has-fields');
        
        // Get template fields data from PHP
        const templateFields = <?php echo json_encode($template['fields']); ?>;
        
        // Loop through fields using JavaScript
        templateFields.forEach(function(field, index) {
            fieldCounter++;
            const existingFieldRow = document.createElement('div');
            existingFieldRow.className = 'field-row';
            existingFieldRow.dataset.fieldId = fieldCounter;
            
            existingFieldRow.innerHTML = `
                <div class="field-row-header">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div class="field-row-number">${fieldCounter}</div>
                        <div class="field-row-title">Form Field #${fieldCounter}</div>
                    </div>
                    <button type="button" class="remove-field-btn" onclick="removeField(${fieldCounter})">
                        🗑️ Remove Field
                    </button>
                </div>
                
                <div class="field-inputs">
                    <div class="field-input-group">
                        <label>Field Name</label>
                        <input 
                            type="text" 
                            name="fields[${fieldCounter}][name]" 
                            value="${field.field_name || ''}"
                            required
                        >
                    </div>
                    
                    <div class="field-input-group">
                        <label>Field Type</label>
                        <select 
                            name="fields[${fieldCounter}][type]" 
                            class="field-type-select"
                            data-field-id="${fieldCounter}"
                            required
                        >
                            <option value="">Select type...</option>
                            <option value="text" ${field.field_type === 'text' ? 'selected' : ''}>Text Input</option>
                            <option value="number" ${field.field_type === 'number' ? 'selected' : ''}>Number Input</option>
                            <option value="email" ${field.field_type === 'email' ? 'selected' : ''}>Email Input</option>
                            <option value="tel" ${field.field_type === 'tel' ? 'selected' : ''}>Phone Number</option>
                            <option value="date" ${field.field_type === 'date' ? 'selected' : ''}>Date Picker</option>
                            <option value="time" ${field.field_type === 'time' ? 'selected' : ''}>Time Picker</option>
                            <option value="datetime-local" ${field.field_type === 'datetime-local' ? 'selected' : ''}>Date & Time</option>
                            <option value="textarea" ${field.field_type === 'textarea' ? 'selected' : ''}>Text Area</option>
                            <option value="select" ${field.field_type === 'select' ? 'selected' : ''}>Dropdown (Select)</option>
                            <option value="radio" ${field.field_type === 'radio' ? 'selected' : ''}>Radio Buttons</option>
                            <option value="checkbox" ${field.field_type === 'checkbox' ? 'selected' : ''}>Checkboxes</option>
                        </select>
                    </div>
                    
                    <div class="field-input-group">
                        <label>Label</label>
                        <input 
                            type="text" 
                            name="fields[${fieldCounter}][label]" 
                            value="${field.field_label || ''}"
                            required
                        >
                    </div>
                    
                    <div class="field-input-group">
                        <label>Required?</label>
                        <select name="fields[${fieldCounter}][required]">
                            <option value="0" ${!field.required ? 'selected' : ''}>Optional</option>
                            <option value="1" ${field.required ? 'selected' : ''}>Required</option>
                        </select>
                    </div>
                    
                    <div class="field-input-group options-field" id="options-field-${fieldCounter}" style="display: ${['select', 'radio', 'checkbox'].includes(field.field_type) ? 'block' : 'none'};">
                        <label>Options</label>
                        <textarea 
                            name="fields[${fieldCounter}][options]" 
                            placeholder="For select/radio/checkbox fields, enter each option on a new line"
                        >${field.field_options || ''}</textarea>
                    </div>
                </div>
            `;
            
            fieldsContainer.appendChild(existingFieldRow);
            console.log(`Loaded existing field #${fieldCounter}: ${field.field_name}`);
        });
        
        console.log(`Loaded ${templateFields.length} existing fields`);
    <?php endif; ?>
}

/**
 * Handle form submission
 */
function handleFormSubmit(event) {
    const fieldRows = fieldsContainer.querySelectorAll('.field-row');
    
    if (fieldRows.length === 0) {
        const confirmSubmit = confirm('This template has no fields. Are you sure you want to continue?');
        if (!confirmSubmit) {
            event.preventDefault();
            return false;
        }
    }
    
    console.log(`Submitting template with ${fieldRows.length} fields`);
    return true;
}
</script>

<?php include '../includes/footer.php'; ?>
