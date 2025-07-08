<?php
/**
 * Edit Production Step View
 * Form for editing existing production steps
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

// Check permissions (admin or engineer can edit steps)
if (!has_role('admin') && !has_role('engineer')) {
    header('Location: ../dashboard.php?error=insufficient_permissions');
    exit;
}

// Get step ID from URL
$step_id = (int) ($_GET['id'] ?? 0);
if ($step_id <= 0) {
    header('Location: ../dashboard.php?error=invalid_step_id');
    exit;
}

// Get step data
$step_data = getProductionStepById($pdo, $step_id);
if (!$step_data) {
    header('Location: ../dashboard.php?error=step_not_found');
    exit;
}

// Get rocket data
$rocket = get_rocket_by_id($pdo, $step_data['rocket_id']);
if (!$rocket) {
    header('Location: ../dashboard.php?error=rocket_not_found');
    exit;
}

// Parse existing JSON data
$existing_data = json_decode($step_data['data_json'], true) ?: [];

// Get template data if template_id exists in the data
$template_data = null;
if (isset($existing_data['template_id'])) {
    $template_data = getTemplateWithFields($pdo, $existing_data['template_id']);
}

// Get all active templates for the dropdown
$active_templates = getAllActiveTemplates($pdo);

include '../includes/header.php';
?>

<div class="form-container">
    <div class="form-header">
        <h2>Edit Production Step</h2>
        <div class="step-info">
            <span class="info-label">Step ID:</span>
            <span class="info-value">#<?php echo $step_id; ?></span>
            <span class="info-separator">•</span>
            <span class="info-label">Rocket:</span>
            <span class="info-value"><?php echo htmlspecialchars($rocket['serial_number']); ?></span>
        </div>
    </div>

    <!-- Display any messages -->
    <?php if (isset($_GET['error'])): ?>
        <div class="error-message">
            <?php
            switch ($_GET['error']) {
                case 'missing_fields':
                    echo 'Please fill in all required fields.';
                    break;
                case 'invalid_json':
                    echo 'Invalid JSON data format. Please check your input.';
                    break;
                case 'update_failed':
                    echo 'Failed to update production step. Please try again.';
                    break;
                default:
                    echo 'An error occurred. Please try again.';
                    break;
            }
            ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['success'])): ?>
        <div class="success-message">
            Production step updated successfully! 
            <a href="rocket_detail_view.php?id=<?php echo $step_data['rocket_id']; ?>">View rocket details</a>
        </div>
    <?php endif; ?>

    <form method="POST" action="../controllers/production_controller.php" class="production-step-form" onsubmit="return handleFormSubmission(event)">
        <input type="hidden" name="action" value="edit_step">
        <input type="hidden" name="step_id" value="<?php echo $step_id; ?>">
        
        <div class="form-group">
            <label for="step_name">Production Step <span class="required">*</span></label>
            <select id="step_name" name="step_name" required onchange="loadTemplateFields()">
                <option value="">Select a production step...</option>
                <?php if (empty($active_templates)): ?>
                    <option value="" disabled>No active templates available</option>
                <?php else: ?>
                    <?php foreach ($active_templates as $template): ?>
                        <option value="<?php echo htmlspecialchars($template['template_id']); ?>" 
                                <?php echo (($existing_data['template_id'] ?? '') == $template['template_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($template['step_name']); ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
            <small class="field-help">Select the production step type</small>
        </div>
        
        <!-- Dynamic Form Fields Container -->
        <div id="dynamic-form-fields" class="dynamic-fields-container">
            <?php if ($template_data): ?>
                <!-- Pre-populate with existing data -->
                <div class="template-info">
                    <h4>📝 <?php echo htmlspecialchars($template_data['step_name']); ?> Details</h4>
                    <p><?php echo htmlspecialchars($template_data['step_description'] ?: 'Fill in the relevant information for this production step:'); ?></p>
                </div>
                
                <?php foreach ($template_data['fields'] as $field): ?>
                    <div class="form-group">
                        <label for="dynamic_<?php echo $field['field_name']; ?>">
                            <?php echo htmlspecialchars($field['field_label']); ?>
                            <?php if ($field['is_required']): ?><span class="required">*</span><?php endif; ?>
                        </label>
                        
                        <?php 
                        $field_value = $existing_data[$field['field_name']] ?? '';
                        
                        switch($field['field_type']): 
                            case 'text':
                            case 'number':
                            case 'date':
                            case 'email': 
                        ?>
                            <input type="<?php echo $field['field_type']; ?>" 
                                   id="dynamic_<?php echo $field['field_name']; ?>" 
                                   name="dynamic_<?php echo $field['field_name']; ?>"
                                   value="<?php echo htmlspecialchars($field_value); ?>"
                                   <?php echo $field['is_required'] ? 'required' : ''; ?>>
                        <?php 
                            break;
                            case 'select': 
                                $options = json_decode($field['options_json'], true) ?: [];
                        ?>
                            <select id="dynamic_<?php echo $field['field_name']; ?>" 
                                    name="dynamic_<?php echo $field['field_name']; ?>"
                                    <?php echo $field['is_required'] ? 'required' : ''; ?>>
                                <option value="">-- Select --</option>
                                <?php foreach ($options as $option): ?>
                                    <option value="<?php echo htmlspecialchars($option); ?>"
                                            <?php echo ($field_value === $option) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($option); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php 
                            break;
                            case 'textarea': 
                        ?>
                            <textarea id="dynamic_<?php echo $field['field_name']; ?>" 
                                      name="dynamic_<?php echo $field['field_name']; ?>"
                                      rows="4"
                                      <?php echo $field['is_required'] ? 'required' : ''; ?>><?php echo htmlspecialchars($field_value); ?></textarea>
                        <?php break; ?>
                        <?php endswitch; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="form-help">
                    <p>👆 Select a production step above to see relevant fields</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Hidden field for JSON data -->
        <input type="hidden" id="data_json_hidden" name="data_json" value="">
        
        <div class="form-actions">
            <button type="submit" class="btn-primary">Update Production Step</button>
            <a href="rocket_detail_view.php?id=<?php echo $step_data['rocket_id']; ?>" class="btn-secondary">Cancel</a>
        </div>
    </form>
    
    <div class="form-info">
        <h4>📋 Guidelines:</h4>
        <ul>
            <li><strong>Updating Step Type:</strong> You can change the step type, but all data will need to be re-entered</li>
            <li><strong>Required Fields:</strong> Fields marked with * must be filled for successful submission</li>
            <li><strong>Data Preservation:</strong> Your existing data is pre-filled in the form fields</li>
            <li><strong>Auto-Tracking:</strong> Changes are timestamped and linked to your user account</li>
        </ul>
    </div>
</div>

<script>
// Store existing data for reference
const existingData = <?php echo json_encode($existing_data); ?>;
const activeTemplates = <?php echo json_encode($active_templates); ?>;

// Load template fields when step type changes
function loadTemplateFields() {
    const stepSelect = document.getElementById('step_name');
    const dynamicFields = document.getElementById('dynamic-form-fields');
    
    const selectedTemplateId = stepSelect.value;
    
    if (!selectedTemplateId) {
        dynamicFields.innerHTML = '<div class="form-help"><p>👆 Select a production step above to see relevant fields</p></div>';
        return;
    }
    
    // Find the selected template
    const selectedTemplate = activeTemplates.find(t => t.template_id == selectedTemplateId);
    
    if (!selectedTemplate) {
        dynamicFields.innerHTML = '<div class="form-help"><p>❌ Template not found</p></div>';
        return;
    }
    
    // Show loading
    dynamicFields.innerHTML = '<div class="form-help"><p>Loading template fields...</p></div>';
    
    // Fetch template fields via AJAX
    fetch(`../controllers/template_ajax.php?action=get_template_fields&template_id=${selectedTemplateId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                generateDynamicForm(data.template);
            } else {
                dynamicFields.innerHTML = '<div class="form-help"><p>❌ Failed to load template fields</p></div>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            dynamicFields.innerHTML = '<div class="form-help"><p>❌ Error loading template fields</p></div>';
        });
}

// Generate dynamic form fields
function generateDynamicForm(templateData) {
    const container = document.getElementById('dynamic-form-fields');
    container.innerHTML = '';
    
    // Add template header
    const header = document.createElement('div');
    header.className = 'template-info';
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
        const existingValue = existingData[field.field_name] || '';
        
        switch(field.field_type) {
            case 'text':
            case 'number':
            case 'date':
            case 'email':
                input = document.createElement('input');
                input.type = field.field_type;
                input.id = 'dynamic_' + field.field_name;
                input.name = 'dynamic_' + field.field_name;
                input.value = existingValue;
                input.required = field.is_required;
                break;
                
            case 'select':
                input = document.createElement('select');
                input.id = 'dynamic_' + field.field_name;
                input.name = 'dynamic_' + field.field_name;
                input.required = field.is_required;
                
                // Add default option
                const defaultOption = document.createElement('option');
                defaultOption.value = '';
                defaultOption.textContent = '-- Select --';
                input.appendChild(defaultOption);
                
                // Add options from field definition
                if (field.options_json) {
                    try {
                        const options = JSON.parse(field.options_json);
                        options.forEach(optionValue => {
                            const option = document.createElement('option');
                            option.value = optionValue;
                            option.textContent = optionValue;
                            if (existingValue === optionValue) {
                                option.selected = true;
                            }
                            input.appendChild(option);
                        });
                    } catch (e) {
                        console.error('Invalid options JSON:', field.options_json);
                    }
                }
                break;
                
            case 'textarea':
                input = document.createElement('textarea');
                input.id = 'dynamic_' + field.field_name;
                input.name = 'dynamic_' + field.field_name;
                input.value = existingValue;
                input.rows = 4;
                input.required = field.is_required;
                break;
        }
        
        fieldDiv.appendChild(label);
        fieldDiv.appendChild(input);
        container.appendChild(fieldDiv);
    });
}

// Handle form submission
function handleFormSubmission(event) {
    event.preventDefault();
    
    const form = event.target;
    const hiddenInput = document.getElementById('data_json_hidden');
    const stepSelect = document.getElementById('step_name');
    const dynamicFields = document.getElementById('dynamic-form-fields');
    
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
    
    // Add metadata
    formData.template_id = stepSelect.value;
    formData.step_name = stepSelect.options[stepSelect.selectedIndex].text;
    formData.updated_at = new Date().toISOString();
    formData.updated_by = '<?php echo $_SESSION['username']; ?>';
    
    // Convert to JSON and set hidden field
    hiddenInput.value = JSON.stringify(formData);
    
    console.log('Form data collected:', formData);
    
    // Submit the form
    form.submit();
    return true;
}
</script>

<?php include '../includes/footer.php'; ?>
