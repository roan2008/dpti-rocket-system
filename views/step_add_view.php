<?php
/**
 * Add Production Step View
 * Form for adding new production steps to a rocket
 */

// Start session and check authentication
session_start();

// Include required files
require_once '../includes/db_connect.php';
require_once '../includes/user_functions.php';
require_once '../includes/rocket_functions.php';
require_once '../includes/production_functions.php';
require_once '../includes/template_functions.php';

// Check if user is logged in
if (!is_logged_in()) {
    header('Location: login_view.php');
    exit;
}

// Get rocket ID from URL
$rocket_id = (int) ($_GET['rocket_id'] ?? 0);
if ($rocket_id <= 0) {
    header('Location: ../dashboard.php?error=invalid_rocket_id');
    exit;
}

// Get rocket details
$rocket = get_rocket_by_id($pdo, $rocket_id);
if (!$rocket) {
    header('Location: ../dashboard.php?error=rocket_not_found');
    exit;
}

// Get all active templates for dropdown population
$active_templates = getAllActiveTemplates($pdo);

include '../includes/header.php';
?>

<div class="form-container">
    <div class="form-header">
        <h2>Add Production Step</h2>
        <div class="rocket-info">
            <span class="rocket-serial"><?php echo htmlspecialchars($rocket['serial_number']); ?></span>
            <span class="rocket-project"><?php echo htmlspecialchars($rocket['project_name']); ?></span>
        </div>
        <a href="rocket_detail_view.php?id=<?php echo $rocket_id; ?>" class="btn-secondary">← Back to Rocket Details</a>
    </div>
    
    <!-- Error Messages -->
    <?php if (isset($_GET['error'])): ?>
        <div class="error-message">
            <?php
            switch ($_GET['error']) {
                case 'missing_fields':
                    echo 'Please fill in all required fields.';
                    break;
                case 'invalid_json':
                    echo 'Invalid JSON data format. Please check your data and try again.';
                    break;
                case 'step_creation_failed':
                    echo 'Failed to create production step. Please try again.';
                    break;
                case 'invalid_rocket':
                    echo 'Invalid rocket specified.';
                    break;
                case 'permission_denied':
                    echo 'You do not have permission to add production steps.';
                    break;
                case 'invalid_template':
                    echo 'Invalid template selected. Please choose a valid production step.';
                    break;
                default:
                    echo 'An error occurred. Please try again.';
                    break;
            }
            ?>
        </div>
    <?php endif; ?>

    <!-- Success Messages -->
    <?php if (isset($_GET['success'])): ?>
        <div class="success-message">
            Production step added successfully! 
            <a href="rocket_detail_view.php?id=<?php echo $rocket_id; ?>">View rocket details</a>
        </div>
    <?php endif; ?>

    <form method="POST" action="../controllers/production_controller.php" class="production-step-form" onsubmit="return handleFormSubmission(event)">
        <input type="hidden" name="action" value="add_step">
        <input type="hidden" name="rocket_id" value="<?php echo $rocket_id; ?>">
        
        <div class="form-group">
            <label for="step_name">Production Step <span class="required">*</span></label>
            <select id="step_name" name="step_name" required>
                <option value="">Select a production step...</option>
                <?php if (empty($active_templates)): ?>
                    <option value="" disabled>No active templates available</option>
                <?php else: ?>
                    <?php foreach ($active_templates as $template): ?>
                        <option value="<?php echo htmlspecialchars($template['template_id']); ?>" 
                                <?php echo (($_GET['template_id'] ?? '') == $template['template_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($template['step_name']); ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
            <small class="field-help">Select the production step being recorded</small>
        </div>
        
        <!-- Dynamic Form Fields Container -->
        <div id="dynamic-form-fields" class="dynamic-fields-container">
            <div class="form-help">
                <p>👆 Select a production step above to see relevant fields</p>
            </div>
        </div>
        
        <!-- Hidden field for JSON data -->
        <input type="hidden" id="data_json_hidden" name="data_json" value="">
        
        <div class="form-actions">
            <button type="submit" class="btn-primary">Record Production Step</button>
            <a href="rocket_detail_view.php?id=<?php echo $rocket_id; ?>" class="btn-secondary">Cancel</a>
        </div>
    </form>
    
    <div class="form-info">
        <h4>📋 Guidelines:</h4>
        <ul>
            <li><strong>Select Production Step:</strong> Choose the step that best describes the work being recorded</li>
            <li><strong>Dynamic Fields:</strong> Relevant input fields will appear based on your step selection</li>
            <li><strong>Required Fields:</strong> Fields marked with * must be filled for successful submission</li>
            <li><strong>Auto-Tracking:</strong> All steps are timestamped and linked to your user account</li>
            <li><strong>Status Updates:</strong> Rocket status will be automatically updated based on the recorded step</li>
        </ul>
    </div>
</div>

<script>
// Dynamic Form Generation - Template-driven system
// Forms are now loaded dynamically from database via AJAX

// AJAX function to load template data and render form
async function loadAndRenderForm(templateId) {
    const container = document.getElementById('dynamic-form-fields');
    
    if (!templateId) {
        container.innerHTML = '<div class="form-help"><p>👆 Select a production step above to see relevant fields</p></div>';
        return;
    }
    
    // Show loading state
    container.innerHTML = '<div class="form-loading"><p>🔄 Loading form fields...</p></div>';
    
    try {
        const response = await fetch(`../controllers/template_ajax.php?template_id=${templateId}`);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.error) {
            container.innerHTML = `<div class="form-error"><p>❌ Error: ${data.error}</p></div>`;
            return;
        }
        
        if (data.success) {
            // Generate form using the template data
            generateFormFields(data);
        } else {
            container.innerHTML = '<div class="form-error"><p>❌ Invalid response from server</p></div>';
        }
        
    } catch (error) {
        console.error('Error loading template:', error);
        container.innerHTML = `<div class="form-error"><p>❌ Failed to load form: ${error.message}</p></div>`;
    }
}

// Generate dynamic form fields based on template data from database
function generateFormFields(templateData) {
    const container = document.getElementById('dynamic-form-fields');
    container.innerHTML = ''; // Clear existing fields
    
    if (!templateData || !templateData.fields || templateData.fields.length === 0) {
        container.innerHTML = '<div class="form-help"><p>⚠️ No fields defined for this template</p></div>';
        return;
    }
    
    // Add section header with template information
    const header = document.createElement('div');
    header.className = 'dynamic-form-header';
    header.innerHTML = `
        <h4>📝 ${templateData.step_name} Details</h4>
        <p>${templateData.step_description || 'Fill in the relevant information for this production step:'}</p>
    `;
    container.appendChild(header);
    
    // Generate fields from database template
    templateData.fields.forEach(field => {
        const fieldDiv = document.createElement('div');
        fieldDiv.className = 'form-group';
        
        const label = document.createElement('label');
        label.textContent = field.field_label + (field.is_required ? ' *' : '');
        label.htmlFor = 'dynamic_' + field.field_name;
        
        let input;
        
        switch(field.field_type) {
            case 'text':
            case 'number':
            case 'date':
            case 'email':
                input = document.createElement('input');
                input.type = field.field_type;
                input.id = 'dynamic_' + field.field_name;
                input.name = 'dynamic_' + field.field_name;
                input.required = field.is_required;
                if (field.placeholder) input.placeholder = field.placeholder;
                break;
                
            case 'select':
                input = document.createElement('select');
                input.id = 'dynamic_' + field.field_name;
                input.name = 'dynamic_' + field.field_name;
                input.required = field.is_required;
                
                // Add empty option for required fields
                if (field.is_required) {
                    const emptyOption = document.createElement('option');
                    emptyOption.value = '';
                    emptyOption.textContent = 'Select...';
                    input.appendChild(emptyOption);
                }
                
                // Parse options from field.options (already parsed) or field.options_json
                let options = null;
                
                // First check if options are already parsed (field.options)
                if (field.options && Array.isArray(field.options)) {
                    options = field.options;
                } else if (field.options_json) {
                    // Fallback to parsing options_json if needed
                    try {
                        options = JSON.parse(field.options_json);
                    } catch (e) {
                        console.error('Error parsing field.options_json:', e, field.options_json);
                    }
                }
                
                // Add options to select element
                if (options && Array.isArray(options)) {
                    options.forEach(option => {
                        const optionEl = document.createElement('option');
                        optionEl.value = option;
                        optionEl.textContent = option;
                        input.appendChild(optionEl);
                    });
                } else {
                    console.warn('No options found for select field:', field.field_name, field);
                }
                break;
                
            case 'textarea':
                input = document.createElement('textarea');
                input.id = 'dynamic_' + field.field_name;
                input.name = 'dynamic_' + field.field_name;
                input.required = field.is_required;
                input.rows = 3;
                if (field.placeholder) input.placeholder = field.placeholder;
                break;
                
            default:
                // Fallback to text input for unknown types
                input = document.createElement('input');
                input.type = 'text';
                input.id = 'dynamic_' + field.field_name;
                input.name = 'dynamic_' + field.field_name;
                input.required = field.is_required;
                break;
        }
        
        input.className = 'form-control';
        
        fieldDiv.appendChild(label);
        fieldDiv.appendChild(input);
        container.appendChild(fieldDiv);
    });
    
    // Add info section
    const infoDiv = document.createElement('div');
    infoDiv.className = 'form-info';
    infoDiv.innerHTML = '<small>💡 All fields marked with * are required. Additional details help track production progress.</small>';
    container.appendChild(infoDiv);
}
// Handle form submission - collect dynamic fields and create JSON
function handleFormSubmission(event) {
    const form = event.target;
    const dynamicFields = document.getElementById('dynamic-form-fields');
    const hiddenInput = document.getElementById('data_json_hidden');
    const stepSelect = document.getElementById('step_name');
    
    // Check if step is selected
    if (!stepSelect.value) {
        alert('Please select a production step.');
        return false;
    }
    
    // Collect dynamic form data
    const formData = {};
    const inputs = dynamicFields.querySelectorAll('input, select, textarea');
    
    inputs.forEach(input => {
        if (input.value.trim() !== '') {
            // Remove 'dynamic_' prefix from field names
            const fieldName = input.name.replace('dynamic_', '');
            formData[fieldName] = input.value.trim();
        }
    });
    
    // Add template_id and timestamp (template_id is now the selected value)
    formData.template_id = stepSelect.value;
    formData.step_name = stepSelect.options[stepSelect.selectedIndex].text; // Get the display name
    formData.recorded_at = new Date().toISOString();
    
    // Convert to JSON and set hidden field
    hiddenInput.value = JSON.stringify(formData);
    
    console.log('Form data collected:', formData); // Debug
    
    return true; // Allow form submission
}

// Event listener for step selection change - now uses AJAX to load template data
document.addEventListener('DOMContentLoaded', function() {
    const stepSelect = document.getElementById('step_name');
    
    stepSelect.addEventListener('change', function() {
        const selectedTemplateId = this.value;
        loadAndRenderForm(selectedTemplateId);
    });
    
    // If there's a pre-selected step, load its template data
    if (stepSelect.value) {
        loadAndRenderForm(stepSelect.value);
    }
});
</script>

<?php include '../includes/footer.php'; ?>
