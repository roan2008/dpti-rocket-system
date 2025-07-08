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
            <select id="step_name" name="step_name" required disabled style="background-color: #f8f9fa; color: #6c757d;">
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
            <!-- Hidden field to ensure the value is still submitted -->
            <input type="hidden" name="template_id" value="<?php echo htmlspecialchars($existing_data['template_id'] ?? ''); ?>">
            <small class="field-help">⚠️ Production step type cannot be changed during editing to preserve data integrity</small>
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
            <li><strong>🔒 Step Type Locked:</strong> Production step type cannot be changed to maintain data integrity</li>
            <li><strong>Data Preservation:</strong> Your existing data is pre-filled in the form fields</li>
            <li><strong>Required Fields:</strong> Fields marked with * must be filled for successful submission</li>
            <li><strong>Auto-Tracking:</strong> Changes are timestamped and linked to your user account</li>
        </ul>
    </div>
    
    <style>
    /* Style for disabled production step dropdown */
    select[disabled] {
        background-color: #f8f9fa !important;
        color: #6c757d !important;
        cursor: not-allowed !important;
        border-color: #dee2e6 !important;
    }
    
    select[disabled]:focus {
        box-shadow: none !important;
        border-color: #dee2e6 !important;
    }
    
    .field-help {
        color: #856404;
        background-color: #fff3cd;
        border: 1px solid #ffeeba;
        padding: 8px 12px;
        border-radius: 4px;
        font-size: 0.875rem;
        margin-top: 5px;
        display: block;
    }
    </style>
</div>

<script>
// Store existing data for reference
const existingData = <?php echo json_encode($existing_data); ?>;
const activeTemplates = <?php echo json_encode($active_templates); ?>;

// Since step type is locked, we don't need the loadTemplateFields function
// The template fields are already pre-populated from PHP

// Handle form submission
function handleFormSubmission(event) {
    event.preventDefault();
    
    const form = event.target;
    const hiddenInput = document.getElementById('data_json_hidden');
    const templateIdInput = document.querySelector('input[name="template_id"]');
    const dynamicFields = document.getElementById('dynamic-form-fields');
    
    // Check if template ID exists (should always exist in edit mode)
    if (!templateIdInput.value) {
        alert('Error: No template ID found. Please refresh the page and try again.');
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
    
    // Add metadata (preserve original template info)
    formData.template_id = templateIdInput.value;
    formData.step_name = existingData.step_name || 'Updated Step';
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
