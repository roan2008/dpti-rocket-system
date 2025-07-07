<?php
/**
 * Template Form View
 * Dynamic form for adding and editing step templates with field builder
 */

// Start session and check authentication
session_start();

// Include required files
require_once '../includes/user_functions.php';
require_once '../includes/db_connect.php';
require_once '../includes/template_functions.php';

// Check if user is logged in
if (!is_logged_in()) {
    header('Location: login_view.php');
    exit;
}

// Check if user has permission (admin or engineer only)
if (!has_role('admin') && !has_role('engineer')) {
    header('Location: ../dashboard.php?error=insufficient_permissions');
    exit;
}

// Determine if this is edit mode
$edit_mode = false;
$template = null;
$template_id = null;

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $template_id = (int) $_GET['id'];
    $template = getTemplateWithFields($pdo, $template_id);
    
    if ($template) {
        $edit_mode = true;
    } else {
        // Template not found, redirect to list
        header('Location: templates_list_view.php?error=template_not_found');
        exit;
    }
}

// Get form data from URL parameters (for error redirection)
$form_data = [
    'step_name' => $_GET['step_name'] ?? ($edit_mode ? $template['step_name'] : ''),
    'step_description' => $_GET['step_description'] ?? ($edit_mode ? $template['step_description'] : '')
];

include '../includes/header.php';
?>

<div class="dashboard-container">
    <div class="dashboard-header">
        <h1><?php echo $edit_mode ? 'Edit' : 'Add New'; ?> Step Template</h1>
        <div class="user-info">
            <p>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</p>
            <p>Role: <?php echo htmlspecialchars($_SESSION['role']); ?></p>
            <a href="../controllers/logout_controller.php" class="btn-logout">Logout</a>
        </div>
    </div>

    <!-- Breadcrumb Navigation -->
    <div class="page-header">
        <div class="header-left">
            <h2><?php echo $edit_mode ? 'Edit Template' : 'Create New Template'; ?></h2>
            <p class="breadcrumb">
                <a href="../dashboard.php">Dashboard</a> → 
                <a href="templates_list_view.php">Template Management</a> → 
                <span><?php echo $edit_mode ? 'Edit' : 'Add New'; ?></span>
            </p>
        </div>
        <div class="header-actions">
            <a href="templates_list_view.php" class="btn btn-secondary">← Back to Templates</a>
        </div>
    </div>

    <!-- Success/Error Messages -->
    <?php if (isset($_GET['success'])): ?>
        <div class="message success">
            <?php 
            switch ($_GET['success']) {
                case 'template_saved':
                    echo htmlspecialchars('Template saved successfully!');
                    break;
                default:
                    echo htmlspecialchars('Operation completed successfully!');
                    break;
            }
            ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="message error">
            <?php 
            switch ($_GET['error']) {
                case 'missing_fields':
                    echo htmlspecialchars('Please fill in all required fields.');
                    break;
                case 'template_exists':
                    echo htmlspecialchars('A template with this name already exists. Please choose a different name.');
                    break;
                case 'invalid_fields_data':
                    echo htmlspecialchars('Invalid field data provided. Please check your form fields.');
                    break;
                case 'save_failed':
                    echo htmlspecialchars('Failed to save template. Please try again.');
                    break;
                case 'invalid_method':
                    echo htmlspecialchars('Invalid request method.');
                    break;
                default:
                    echo htmlspecialchars('An error occurred. Please try again.');
                    break;
            }
            ?>
        </div>
    <?php endif; ?>

    <!-- Template Form -->
    <div class="section">
        <form id="template-form" method="POST" action="../controllers/template_controller.php">
            <input type="hidden" name="action" value="save">
            <?php if ($edit_mode): ?>
                <input type="hidden" name="template_id" value="<?php echo $template_id; ?>">
            <?php endif; ?>
            <input type="hidden" name="fields_data" id="fields-data-input">

            <!-- Section 1: Master Template Details -->
            <div class="section-header">
                <h2>Template Information</h2>
            </div>
            
            <div class="section-content">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="step_name" class="form-label required">Step Name</label>
                        <input 
                            type="text" 
                            id="step_name" 
                            name="step_name" 
                            class="form-control" 
                            value="<?php echo htmlspecialchars($form_data['step_name']); ?>" 
                            required
                            placeholder="e.g., Quality Control Inspection"
                        >
                        <small class="form-text">Enter a descriptive name for this production step template.</small>
                    </div>

                    <div class="form-group">
                        <label for="step_description" class="form-label">Step Description</label>
                        <textarea 
                            id="step_description" 
                            name="step_description" 
                            class="form-control" 
                            rows="4"
                            placeholder="Describe what this step involves and any important notes..."
                        ><?php echo htmlspecialchars($form_data['step_description']); ?></textarea>
                        <small class="form-text">Optional: Provide detailed instructions or notes for this step.</small>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Section 2: Dynamic Field Builder -->
    <div class="section">
        <div class="section-header">
            <h2>Form Fields</h2>
            <div class="header-actions">
                <button type="button" id="add-field-btn" class="btn btn-primary">
                    <span>➕</span> Add Field
                </button>
            </div>
        </div>
        
        <div class="section-content">
            <div id="fields-container" class="fields-container">
                <!-- Dynamic field rows will be inserted here -->
                <div class="empty-fields-message" id="empty-fields-message">
                    <p>No form fields added yet. Click "Add Field" to create your first form field.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Form Actions -->
    <div class="form-actions">
        <button type="submit" form="template-form" class="btn btn-primary btn-lg">
            <span><?php echo $edit_mode ? '💾 Update' : '✅ Create'; ?> Template</span>
        </button>
        <a href="templates_list_view.php" class="btn btn-secondary btn-lg">
            <span>❌ Cancel</span>
        </a>
    </div>
</div>

<script>
/**
 * Dynamic Template Form Builder
 * Handles adding/removing fields and form submission
 */

// Global variables
let fieldCounter = 0;
const fieldsContainer = document.getElementById('fields-container');
const addFieldBtn = document.getElementById('add-field-btn');
const templateForm = document.getElementById('template-form');
const emptyMessage = document.getElementById('empty-fields-message');

// Initialize the form
document.addEventListener('DOMContentLoaded', function() {
    console.log('Template Form initialized');
    
    // Add event listeners
    addFieldBtn.addEventListener('click', addField);
    templateForm.addEventListener('submit', handleFormSubmit);
    
    // Load existing fields if in edit mode
    <?php if ($edit_mode && !empty($template['fields'])): ?>
        loadExistingFields();
    <?php endif; ?>
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
    
    // Add 'has-fields' class to container
    fieldsContainer.classList.add('has-fields');
    
    // Create field row HTML
    const fieldRow = document.createElement('div');
    fieldRow.className = 'field-row';
    fieldRow.dataset.fieldId = fieldCounter;
    
    fieldRow.innerHTML = `
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
                <label>Field Label <span style="color: #dc3545;">*</span></label>
                <input 
                    type="text" 
                    name="field_label_${fieldCounter}" 
                    placeholder="e.g., Weight (grams)"
                    required
                >
            </div>
            
            <div class="field-input-group">
                <label>Field Name <span style="color: #dc3545;">*</span></label>
                <input 
                    type="text" 
                    name="field_name_${fieldCounter}" 
                    placeholder="e.g., weight_grams"
                    pattern="[a-z_][a-z0-9_]*"
                    title="Must be lowercase, start with letter/underscore, contain only letters, numbers, underscores"
                    required
                >
            </div>
            
            <div class="field-input-group">
                <label>Field Type <span style="color: #dc3545;">*</span></label>
                <select name="field_type_${fieldCounter}" onchange="toggleOptionsField(${fieldCounter})" required>
                    <option value="">Select type...</option>
                    <option value="text">Text Input</option>
                    <option value="number">Number Input</option>
                    <option value="textarea">Text Area</option>
                    <option value="select">Dropdown Select</option>
                    <option value="date">Date Input</option>
                </select>
            </div>
            
            <div class="field-input-group">
                <div class="checkbox-wrapper">
                    <input type="checkbox" name="is_required_${fieldCounter}" id="required_${fieldCounter}">
                    <label for="required_${fieldCounter}">Required field</label>
                </div>
            </div>
            
            <div class="field-input-group full-width">
                <div class="options-json-group hidden" id="options_group_${fieldCounter}">
                    <label>Dropdown Options <span style="color: #dc3545;">*</span></label>
                    <textarea 
                        name="options_json_${fieldCounter}" 
                        rows="3"
                        placeholder='["Option 1", "Option 2", "Option 3"]'
                    ></textarea>
                    <div class="help-text">
                        Enter options as a JSON array. Example: ["Pass", "Fail", "Needs Review"]
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Add the field row to container
    fieldsContainer.appendChild(fieldRow);
    
    // Add auto-sanitization to field name input
    const fieldNameInput = fieldRow.querySelector(`input[name="field_name_${fieldCounter}"]`);
    const fieldLabelInput = fieldRow.querySelector(`input[name="field_label_${fieldCounter}"]`);
    
    // Auto-generate field name from label
    fieldLabelInput.addEventListener('input', function() {
        if (!fieldNameInput.dataset.manuallyEdited) {
            fieldNameInput.value = sanitizeFieldName(this.value);
        }
    });
    
    // Mark as manually edited if user types directly in field name
    fieldNameInput.addEventListener('input', function() {
        this.dataset.manuallyEdited = 'true';
        this.value = sanitizeFieldName(this.value);
    });
    
    console.log('Added field row #' + fieldCounter);
}

/**
 * Remove a specific field row
 */
function removeField(fieldId) {
    const fieldRow = document.querySelector(`[data-field-id="${fieldId}"]`);
    if (fieldRow) {
        fieldRow.remove();
        console.log('Removed field row #' + fieldId);
        
        // Check if no fields remain
        const remainingFields = fieldsContainer.querySelectorAll('.field-row');
        if (remainingFields.length === 0) {
            // Show empty message
            if (emptyMessage) {
                emptyMessage.style.display = 'block';
            }
            fieldsContainer.classList.remove('has-fields');
        }
        
        // Renumber remaining fields
        renumberFields();
    }
}

/**
 * Renumber fields after deletion
 */
function renumberFields() {
    const fieldRows = fieldsContainer.querySelectorAll('.field-row');
    fieldRows.forEach((row, index) => {
        const number = index + 1;
        const numberElement = row.querySelector('.field-row-number');
        const titleElement = row.querySelector('.field-row-title');
        
        if (numberElement) numberElement.textContent = number;
        if (titleElement) titleElement.textContent = `Form Field #${number}`;
    });
}

/**
 * Toggle options field visibility based on field type
 */
function toggleOptionsField(fieldId) {
    const fieldTypeSelect = document.querySelector(`select[name="field_type_${fieldId}"]`);
    const optionsGroup = document.getElementById(`options_group_${fieldId}`);
    const optionsTextarea = document.querySelector(`textarea[name="options_json_${fieldId}"]`);
    
    if (fieldTypeSelect && optionsGroup) {
        if (fieldTypeSelect.value === 'select') {
            optionsGroup.classList.remove('hidden');
            optionsTextarea.required = true;
        } else {
            optionsGroup.classList.add('hidden');
            optionsTextarea.required = false;
            optionsTextarea.value = ''; // Clear value when hidden
        }
    }
}

/**
 * Sanitize field name to snake_case
 */
function sanitizeFieldName(input) {
    return input
        .toLowerCase()                          // Convert to lowercase
        .replace(/[^a-z0-9\s_-]/g, '')         // Remove special characters except spaces, underscores, hyphens
        .replace(/[\s-]+/g, '_')               // Replace spaces and hyphens with underscores
        .replace(/^[^a-z_]/, '_')              // Ensure starts with letter or underscore
        .replace(/_+/g, '_')                   // Replace multiple underscores with single
        .replace(/^_+|_+$/g, '');              // Remove leading/trailing underscores
}

/**
 * Handle form submission - gather all field data into JSON
 */
function handleFormSubmit(event) {
    event.preventDefault();
    
    console.log('Form submission intercepted');
    
    // Gather all field data
    const fieldsData = [];
    const fieldRows = fieldsContainer.querySelectorAll('.field-row');
    let hasErrors = false;
    
    fieldRows.forEach((row, index) => {
        const fieldId = row.dataset.fieldId;
        const displayOrder = index + 1;
        
        // Get field data
        const fieldLabel = row.querySelector(`input[name="field_label_${fieldId}"]`).value.trim();
        const fieldName = row.querySelector(`input[name="field_name_${fieldId}"]`).value.trim();
        const fieldType = row.querySelector(`select[name="field_type_${fieldId}"]`).value;
        const isRequired = row.querySelector(`input[name="is_required_${fieldId}"]`).checked;
        const optionsJson = row.querySelector(`textarea[name="options_json_${fieldId}"]`).value.trim();
        
        // Validate required fields
        if (!fieldLabel || !fieldName || !fieldType) {
            alert(`Field #${displayOrder}: Please fill in all required fields (Label, Name, Type).`);
            hasErrors = true;
            return;
        }
        
        // Validate options for select fields
        if (fieldType === 'select') {
            if (!optionsJson) {
                alert(`Field #${displayOrder}: Dropdown fields must have options defined.`);
                hasErrors = true;
                return;
            }
            
            // Validate JSON format
            try {
                const options = JSON.parse(optionsJson);
                if (!Array.isArray(options) || options.length === 0) {
                    throw new Error('Must be a non-empty array');
                }
            } catch (e) {
                alert(`Field #${displayOrder}: Invalid options format. Must be a valid JSON array like ["Option 1", "Option 2"].`);
                hasErrors = true;
                return;
            }
        }
        
        // Create field object
        const fieldData = {
            field_label: fieldLabel,
            field_name: fieldName,
            field_type: fieldType,
            is_required: isRequired,
            display_order: displayOrder,
            options_json: fieldType === 'select' ? optionsJson : null
        };
        
        fieldsData.push(fieldData);
    });
    
    // Stop if there are errors
    if (hasErrors) {
        return;
    }
    
    // Convert to JSON and place in hidden input
    const fieldsDataJson = JSON.stringify(fieldsData);
    document.getElementById('fields-data-input').value = fieldsDataJson;
    
    console.log('Fields data:', fieldsDataJson);
    console.log('Submitting form...');
    
    // Allow form to submit
    templateForm.submit();
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
        
        <?php foreach ($template['fields'] as $field): ?>
            fieldCounter++;
            const fieldRow = document.createElement('div');
            fieldRow.className = 'field-row';
            fieldRow.dataset.fieldId = fieldCounter;
            
            fieldRow.innerHTML = `
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
                        <label>Field Label <span style="color: #dc3545;">*</span></label>
                        <input 
                            type="text" 
                            name="field_label_${fieldCounter}" 
                            value="<?php echo htmlspecialchars($field['field_label']); ?>"
                            required
                        >
                    </div>
                    
                    <div class="field-input-group">
                        <label>Field Name <span style="color: #dc3545;">*</span></label>
                        <input 
                            type="text" 
                            name="field_name_${fieldCounter}" 
                            value="<?php echo htmlspecialchars($field['field_name']); ?>"
                            pattern="[a-z_][a-z0-9_]*"
                            data-manually-edited="true"
                            required
                        >
                    </div>
                    
                    <div class="field-input-group">
                        <label>Field Type <span style="color: #dc3545;">*</span></label>
                        <select name="field_type_${fieldCounter}" onchange="toggleOptionsField(${fieldCounter})" required>
                            <option value="">Select type...</option>
                            <option value="text" <?php echo $field['field_type'] === 'text' ? 'selected' : ''; ?>>Text Input</option>
                            <option value="number" <?php echo $field['field_type'] === 'number' ? 'selected' : ''; ?>>Number Input</option>
                            <option value="textarea" <?php echo $field['field_type'] === 'textarea' ? 'selected' : ''; ?>>Text Area</option>
                            <option value="select" <?php echo $field['field_type'] === 'select' ? 'selected' : ''; ?>>Dropdown Select</option>
                            <option value="date" <?php echo $field['field_type'] === 'date' ? 'selected' : ''; ?>>Date Input</option>
                        </select>
                    </div>
                    
                    <div class="field-input-group">
                        <div class="checkbox-wrapper">
                            <input type="checkbox" name="is_required_${fieldCounter}" id="required_${fieldCounter}" <?php echo $field['is_required'] ? 'checked' : ''; ?>>
                            <label for="required_${fieldCounter}">Required field</label>
                        </div>
                    </div>
                    
                    <div class="field-input-group full-width">
                        <div class="options-json-group <?php echo $field['field_type'] !== 'select' ? 'hidden' : ''; ?>" id="options_group_${fieldCounter}">
                            <label>Dropdown Options <span style="color: #dc3545;">*</span></label>
                            <textarea 
                                name="options_json_${fieldCounter}" 
                                rows="3"
                                <?php echo $field['field_type'] === 'select' ? 'required' : ''; ?>
                            ><?php echo $field['field_type'] === 'select' ? htmlspecialchars($field['options_json']) : ''; ?></textarea>
                            <div class="help-text">
                                Enter options as a JSON array. Example: ["Pass", "Fail", "Needs Review"]
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            fieldsContainer.appendChild(fieldRow);
        <?php endforeach; ?>
        
        console.log('Loaded <?php echo count($template['fields']); ?> existing fields');
    <?php endif; ?>
}
</script>

<?php include '../includes/footer.php'; ?>
