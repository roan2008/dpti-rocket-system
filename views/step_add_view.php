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

    <form method="POST" action="../controllers/production_controller.php" class="production-step-form">
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
        
        <div class="form-group">
            <label for="data_json">Step Details (JSON Format)</label>
            <textarea 
                id="data_json" 
                name="data_json" 
                rows="8"
                placeholder='{"component": "Engine nozzle", "quality_check": "Passed", "notes": "All specifications met", "duration_minutes": 45, "temperature_celsius": 22, "humidity_percent": 65}'
            ><?php echo isset($_GET['data_json']) ? htmlspecialchars($_GET['data_json']) : ''; ?></textarea>
            <small class="field-help">
                Optional: Enter additional details in JSON format. 
                <a href="#" onclick="showJsonExamples(); return false;">View examples</a>
            </small>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn-primary">Record Production Step</button>
            <a href="rocket_detail_view.php?id=<?php echo $rocket_id; ?>" class="btn-secondary">Cancel</a>
        </div>
    </form>
    
    <div class="form-info">
        <h4>Guidelines:</h4>
        <ul>
            <li>Select the production step that best describes the work being recorded</li>
            <li>JSON data is optional but can include details like components, quality checks, notes, etc.</li>
            <li>The rocket status will be automatically updated based on the step recorded</li>
            <li>All production steps are timestamped and linked to your user account</li>
        </ul>
        
        <div id="json-examples" style="display: none;">
            <h4>JSON Examples:</h4>
            <div class="example-section">
                <h5>Material Preparation:</h5>
                <pre>{"material_type": "Aluminum", "quantity_kg": 15.5, "supplier": "ACME Materials", "batch_number": "AL-2025-001"}</pre>
            </div>
            <div class="example-section">
                <h5>Quality Check:</h5>
                <pre>{"test_type": "Pressure test", "result": "Passed", "max_pressure_psi": 1500, "duration_minutes": 30}</pre>
            </div>
            <div class="example-section">
                <h5>Assembly:</h5>
                <pre>{"components": ["nozzle", "combustion chamber"], "tools_used": "Torque wrench", "torque_nm": 85}</pre>
            </div>
        </div>
    </div>
</div>

<script>
function showJsonExamples() {
    var examples = document.getElementById('json-examples');
    if (examples.style.display === 'none') {
        examples.style.display = 'block';
    } else {
        examples.style.display = 'none';
    }
}

// Validate JSON format on input
document.getElementById('data_json').addEventListener('blur', function() {
    var jsonText = this.value.trim();
    if (jsonText === '') return; // Empty is valid
    
    try {
        JSON.parse(jsonText);
        this.style.borderColor = '#28a745';
        this.style.backgroundColor = '#f8fff8';
    } catch (e) {
        this.style.borderColor = '#dc3545';
        this.style.backgroundColor = '#fff8f8';
    }
});

// Reset validation styling on focus
document.getElementById('data_json').addEventListener('focus', function() {
    this.style.borderColor = '';
    this.style.backgroundColor = '';
});
</script>

<?php include '../includes/footer.php'; ?>
