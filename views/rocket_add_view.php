<?php
// Start session and check authentication
session_start();

// Include required files
require_once '../includes/user_functions.php';

// Check if user is logged in
if (!is_logged_in()) {
    header('Location: login_view.php');
    exit;
}

// Check if user has permission to add rockets (admin or engineer)
if (!has_role('admin') && !has_role('engineer')) {
    header('Location: ../dashboard.php?error=insufficient_permissions');
    exit;
}

include '../includes/header.php';
?>

<div class="form-container">
    <div class="form-header">
        <h2>Add New Rocket</h2>
        <a href="../dashboard.php" class="btn-secondary">← Back to Dashboard</a>
    </div>
    
    <?php if (isset($_GET['error'])): ?>
        <div class="error-message">
            <?php 
            switch ($_GET['error']) {
                case 'missing_fields':
                    echo htmlspecialchars('Please fill in all required fields.');
                    break;
                case 'serial_exists':
                    echo htmlspecialchars('Serial number already exists. Please use a different serial number.');
                    break;
                case 'creation_failed':
                    echo htmlspecialchars('Failed to create rocket. Please try again.');
                    break;
                case 'invalid_serial':
                    echo htmlspecialchars('Invalid serial number format. Please use alphanumeric characters and hyphens only.');
                    break;
                default:
                    echo htmlspecialchars('An error occurred. Please try again.');
                    break;
            }
            ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['success'])): ?>
        <div class="success-message">
            Rocket created successfully! <a href="../dashboard.php">View all rockets</a>
        </div>
    <?php endif; ?>

    <form method="POST" action="../controllers/rocket_controller.php" class="rocket-form">
        <input type="hidden" name="action" value="add">
        
        <div class="form-group">
            <label for="serial_number">Serial Number: <span class="required">*</span></label>
            <input 
                type="text" 
                id="serial_number" 
                name="serial_number" 
                required 
                maxlength="50"
                placeholder="e.g., RKT-006"
                pattern="[A-Za-z0-9\-]+"
                title="Use only letters, numbers, and hyphens"
                value="<?php echo isset($_GET['serial_number']) ? htmlspecialchars($_GET['serial_number']) : ''; ?>"
            >
            <small class="field-help">Unique identifier for the rocket (letters, numbers, and hyphens only)</small>
        </div>
        
        <div class="form-group">
            <label for="project_name">Project Name: <span class="required">*</span></label>
            <input 
                type="text" 
                id="project_name" 
                name="project_name" 
                required 
                maxlength="100"
                placeholder="e.g., Mars Mission Delta"
                value="<?php echo isset($_GET['project_name']) ? htmlspecialchars($_GET['project_name']) : ''; ?>"
            >
            <small class="field-help">Descriptive name for the rocket project</small>
        </div>
        
        <div class="form-group">
            <label for="current_status">Initial Status:</label>
            <select id="current_status" name="current_status">
                <option value="New" selected>New</option>
                <option value="Planning">Planning</option>
                <option value="Design">Design</option>
                <option value="In Production">In Production</option>
                <option value="Testing">Testing</option>
            </select>
            <small class="field-help">Current stage of the rocket development</small>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn-primary">Create Rocket</button>
            <a href="../dashboard.php" class="btn-secondary">Cancel</a>
        </div>
    </form>
    
    <div class="form-info">
        <h4>Guidelines:</h4>
        <ul>
            <li>Serial numbers must be unique across all rockets</li>
            <li>Use a consistent naming convention (e.g., RKT-XXX)</li>
            <li>Project names should be descriptive and professional</li>
            <li>Initial status can be changed later from the rocket details page</li>
        </ul>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
