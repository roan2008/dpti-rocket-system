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
                <option value="Design Review" <?php echo (($_GET['step_name'] ?? '') === 'Design Review') ? 'selected' : ''; ?>>
                    Design Review
                </option>
                <option value="Material Preparation" <?php echo (($_GET['step_name'] ?? '') === 'Material Preparation') ? 'selected' : ''; ?>>
                    Material Preparation
                </option>
                <option value="Tube Preparation" <?php echo (($_GET['step_name'] ?? '') === 'Tube Preparation') ? 'selected' : ''; ?>>
                    Tube Preparation
                </option>
                <option value="Propellant Mixing" <?php echo (($_GET['step_name'] ?? '') === 'Propellant Mixing') ? 'selected' : ''; ?>>
                    Propellant Mixing
                </option>
                <option value="Propellant Casting" <?php echo (($_GET['step_name'] ?? '') === 'Propellant Casting') ? 'selected' : ''; ?>>
                    Propellant Casting
                </option>
                <option value="Motor Assembly" <?php echo (($_GET['step_name'] ?? '') === 'Motor Assembly') ? 'selected' : ''; ?>>
                    Motor Assembly
                </option>
                <option value="Component Assembly" <?php echo (($_GET['step_name'] ?? '') === 'Component Assembly') ? 'selected' : ''; ?>>
                    Component Assembly
                </option>
                <option value="Quality Check" <?php echo (($_GET['step_name'] ?? '') === 'Quality Check') ? 'selected' : ''; ?>>
                    Quality Check
                </option>
                <option value="System Test" <?php echo (($_GET['step_name'] ?? '') === 'System Test') ? 'selected' : ''; ?>>
                    System Test
                </option>
                <option value="Integration Test" <?php echo (($_GET['step_name'] ?? '') === 'Integration Test') ? 'selected' : ''; ?>>
                    Integration Test
                </option>
                <option value="Final Inspection" <?php echo (($_GET['step_name'] ?? '') === 'Final Inspection') ? 'selected' : ''; ?>>
                    Final Inspection
                </option>
                <option value="Launch Preparation" <?php echo (($_GET['step_name'] ?? '') === 'Launch Preparation') ? 'selected' : ''; ?>>
                    Launch Preparation
                </option>
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
// Step Form Structures - Define fields for each production step
const stepFormStructures = {
    'Design Review': [
        {name: 'reviewer_name', label: 'Reviewer Name', type: 'text', required: true, placeholder: 'Engineer John Doe'},
        {name: 'design_version', label: 'Design Version', type: 'text', required: true, placeholder: 'v2.1'},
        {name: 'approved', label: 'Approval Status', type: 'select', options: ['Approved', 'Rejected', 'Needs Revision'], required: true},
        {name: 'review_notes', label: 'Review Notes', type: 'textarea', required: false, placeholder: 'Design meets all requirements...'}
    ],
    'Material Preparation': [
        {name: 'material_type', label: 'Material Type', type: 'select', options: ['Aluminum', 'Steel', 'Composite', 'Titanium'], required: true},
        {name: 'quantity_kg', label: 'Quantity (kg)', type: 'number', required: true, step: '0.1'},
        {name: 'supplier', label: 'Supplier', type: 'text', required: false, placeholder: 'ACME Materials Co.'},
        {name: 'batch_number', label: 'Batch Number', type: 'text', required: false, placeholder: 'AL-2025-001'},
        {name: 'quality_cert', label: 'Quality Certificate', type: 'text', required: false, placeholder: 'QC-2025-123'}
    ],
    'Tube Preparation': [
        {name: 'length_mm', label: 'Length (mm)', type: 'number', required: true},
        {name: 'diameter_mm', label: 'Outer Diameter (mm)', type: 'number', required: true, step: '0.1'},
        {name: 'wall_thickness_mm', label: 'Wall Thickness (mm)', type: 'number', required: true, step: '0.1'},
        {name: 'surface_finish', label: 'Surface Finish', type: 'select', options: ['Smooth', 'Textured', 'Polished', 'Anodized'], required: true},
        {name: 'inspection_result', label: 'Inspection Result', type: 'select', options: ['Pass', 'Fail', 'Rework Required'], required: true}
    ],
    'Propellant Mixing': [
        {name: 'propellant_type', label: 'Propellant Type', type: 'select', options: ['APCP', 'HTPB', 'Sugar Propellant', 'Black Powder'], required: true},
        {name: 'batch_size_kg', label: 'Batch Size (kg)', type: 'number', required: true, step: '0.1'},
        {name: 'mixing_time_min', label: 'Mixing Time (minutes)', type: 'number', required: true},
        {name: 'temperature_celsius', label: 'Temperature (°C)', type: 'number', required: true},
        {name: 'humidity_percent', label: 'Humidity (%)', type: 'number', required: false, step: '0.1'},
        {name: 'safety_officer', label: 'Safety Officer', type: 'text', required: true}
    ],
    'Propellant Casting': [
        {name: 'cast_weight_kg', label: 'Cast Weight (kg)', type: 'number', required: true, step: '0.01'},
        {name: 'curing_time_hours', label: 'Curing Time (hours)', type: 'number', required: true},
        {name: 'curing_temperature', label: 'Curing Temperature (°C)', type: 'number', required: true},
        {name: 'void_check', label: 'Void Check Result', type: 'select', options: ['No Voids', 'Minor Voids', 'Major Voids'], required: true},
        {name: 'density_measured', label: 'Density (g/cm³)', type: 'number', required: false, step: '0.001'}
    ],
    'Motor Assembly': [
        {name: 'motor_type', label: 'Motor Type', type: 'select', options: ['Single Stage', 'Multi Stage', 'Booster'], required: true},
        {name: 'nozzle_type', label: 'Nozzle Type', type: 'text', required: true, placeholder: 'Convergent-Divergent'},
        {name: 'igniter_type', label: 'Igniter Type', type: 'text', required: true},
        {name: 'assembly_torque_nm', label: 'Assembly Torque (Nm)', type: 'number', required: true},
        {name: 'leak_test_result', label: 'Leak Test Result', type: 'select', options: ['Pass', 'Fail'], required: true}
    ],
    'Component Assembly': [
        {name: 'components_list', label: 'Components List', type: 'textarea', required: true, placeholder: 'List main components assembled...'},
        {name: 'assembly_procedure', label: 'Assembly Procedure ID', type: 'text', required: false, placeholder: 'ASM-PROC-001'},
        {name: 'tools_used', label: 'Tools Used', type: 'text', required: false, placeholder: 'Torque wrench, Allen keys'},
        {name: 'fit_check', label: 'Fit Check Result', type: 'select', options: ['Perfect Fit', 'Acceptable', 'Requires Adjustment'], required: true}
    ],
    'Quality Check': [
        {name: 'inspector_name', label: 'Inspector Name', type: 'text', required: true},
        {name: 'test_type', label: 'Test Type', type: 'select', options: ['Visual Inspection', 'Dimensional Check', 'Pressure Test', 'Weight Check'], required: true},
        {name: 'test_result', label: 'Test Result', type: 'select', options: ['Pass', 'Fail', 'Conditional Pass'], required: true},
        {name: 'defects_found', label: 'Defects Found', type: 'textarea', required: false, placeholder: 'List any defects or issues...'},
        {name: 'corrective_action', label: 'Corrective Action', type: 'textarea', required: false}
    ],
    'System Test': [
        {name: 'test_type', label: 'Test Type', type: 'select', options: ['Static Fire', 'Pressure Test', 'Vibration Test', 'Electrical Test'], required: true},
        {name: 'test_duration_sec', label: 'Test Duration (seconds)', type: 'number', required: true},
        {name: 'max_pressure_psi', label: 'Max Pressure (PSI)', type: 'number', required: false},
        {name: 'test_result', label: 'Test Result', type: 'select', options: ['Pass', 'Fail', 'Partial Success'], required: true},
        {name: 'data_recorded', label: 'Data Recorded', type: 'text', required: false, placeholder: 'Thrust curve, pressure data, etc.'}
    ],
    'Integration Test': [
        {name: 'integration_type', label: 'Integration Type', type: 'select', options: ['Stage Integration', 'Payload Integration', 'System Integration'], required: true},
        {name: 'test_sequence', label: 'Test Sequence', type: 'text', required: true, placeholder: 'INT-SEQ-001'},
        {name: 'communication_test', label: 'Communication Test', type: 'select', options: ['Pass', 'Fail', 'Not Applicable'], required: true},
        {name: 'separation_test', label: 'Separation Test', type: 'select', options: ['Pass', 'Fail', 'Not Applicable'], required: false},
        {name: 'overall_result', label: 'Overall Result', type: 'select', options: ['Pass', 'Fail', 'Needs Retest'], required: true}
    ],
    'Final Inspection': [
        {name: 'inspector_name', label: 'Chief Inspector', type: 'text', required: true},
        {name: 'inspection_checklist', label: 'Checklist ID', type: 'text', required: true, placeholder: 'FINAL-CHK-001'},
        {name: 'weight_final_kg', label: 'Final Weight (kg)', type: 'number', required: true, step: '0.01'},
        {name: 'cg_location_mm', label: 'Center of Gravity (mm)', type: 'number', required: false, step: '0.1'},
        {name: 'flight_readiness', label: 'Flight Readiness', type: 'select', options: ['Ready for Flight', 'Minor Issues', 'Major Issues'], required: true},
        {name: 'certification_notes', label: 'Certification Notes', type: 'textarea', required: false}
    ],
    'Launch Preparation': [
        {name: 'launch_date', label: 'Planned Launch Date', type: 'date', required: false},
        {name: 'weather_conditions', label: 'Weather Conditions', type: 'text', required: false, placeholder: 'Clear, wind 5 mph'},
        {name: 'safety_checklist', label: 'Safety Checklist ID', type: 'text', required: true, placeholder: 'LAUNCH-SAFE-001'},
        {name: 'range_officer', label: 'Range Safety Officer', type: 'text', required: true},
        {name: 'prep_status', label: 'Preparation Status', type: 'select', options: ['Complete', 'In Progress', 'Pending'], required: true},
        {name: 'special_notes', label: 'Special Notes', type: 'textarea', required: false, placeholder: 'Any special considerations...'}
    ]
};

// Generate dynamic form fields based on selected step
function generateFormFields(stepName) {
    const container = document.getElementById('dynamic-form-fields');
    container.innerHTML = ''; // Clear existing fields
    
    if (!stepName || !stepFormStructures[stepName]) {
        container.innerHTML = '<div class="form-help"><p>👆 Select a production step above to see relevant fields</p></div>';
        return;
    }
    
    const fields = stepFormStructures[stepName];
    
    // Add section header
    const header = document.createElement('div');
    header.className = 'dynamic-form-header';
    header.innerHTML = `<h4>📝 ${stepName} Details</h4><p>Fill in the relevant information for this production step:</p>`;
    container.appendChild(header);
    
    // Generate fields
    fields.forEach(field => {
        const fieldDiv = document.createElement('div');
        fieldDiv.className = 'form-group';
        
        const label = document.createElement('label');
        label.textContent = field.label + (field.required ? ' *' : '');
        label.htmlFor = 'dynamic_' + field.name;
        
        let input;
        
        switch(field.type) {
            case 'text':
            case 'number':
            case 'date':
                input = document.createElement('input');
                input.type = field.type;
                input.id = 'dynamic_' + field.name;
                input.name = 'dynamic_' + field.name;
                input.required = field.required;
                if (field.placeholder) input.placeholder = field.placeholder;
                if (field.step) input.step = field.step;
                break;
                
            case 'select':
                input = document.createElement('select');
                input.id = 'dynamic_' + field.name;
                input.name = 'dynamic_' + field.name;
                input.required = field.required;
                
                // Add empty option for required fields
                if (field.required) {
                    const emptyOption = document.createElement('option');
                    emptyOption.value = '';
                    emptyOption.textContent = 'Select...';
                    input.appendChild(emptyOption);
                }
                
                field.options.forEach(option => {
                    const optionEl = document.createElement('option');
                    optionEl.value = option;
                    optionEl.textContent = option;
                    input.appendChild(optionEl);
                });
                break;
                
            case 'textarea':
                input = document.createElement('textarea');
                input.id = 'dynamic_' + field.name;
                input.name = 'dynamic_' + field.name;
                input.required = field.required;
                input.rows = 3;
                if (field.placeholder) input.placeholder = field.placeholder;
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
    
    // Add step name and timestamp
    formData.step_name = stepSelect.value;
    formData.recorded_at = new Date().toISOString();
    
    // Convert to JSON and set hidden field
    hiddenInput.value = JSON.stringify(formData);
    
    console.log('Form data collected:', formData); // Debug
    
    return true; // Allow form submission
}

// Event listener for step selection change
document.addEventListener('DOMContentLoaded', function() {
    const stepSelect = document.getElementById('step_name');
    
    stepSelect.addEventListener('change', function() {
        const selectedStep = this.value;
        generateFormFields(selectedStep);
    });
    
    // If there's a pre-selected step, generate its fields
    if (stepSelect.value) {
        generateFormFields(stepSelect.value);
    }
});
</script>

<?php include '../includes/footer.php'; ?>
