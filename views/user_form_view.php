<?php
/**
 * User Form View - Add/Edit User
 * Handles both creating new users and editing existing users
 */

// Start session only if not already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include required files
require_once '../includes/db_connect.php';
require_once '../includes/user_functions.php';

// Access Control: Only admins can access this page
if (!isset($_SESSION['user_id']) || !is_logged_in()) {
    header('Location: login_view.php');
    exit;
}

if (!has_role('admin')) {
    header('Location: ../dashboard.php?error=insufficient_permissions');
    exit;
}

// Determine if this is edit mode
$edit_mode = isset($_GET['id']) && !empty($_GET['id']);
$user_id = $edit_mode ? (int)$_GET['id'] : null;
$user_data = null;

// If edit mode, get user data
if ($edit_mode) {
    $user_data = get_user_by_id($pdo, $user_id);
    if (!$user_data) {
        header('Location: user_management_view.php?error=user_not_found');
        exit;
    }
}

// Set form values (either from user data or empty for new user)
$form_values = [
    'full_name' => $user_data['full_name'] ?? '',
    'username' => $user_data['username'] ?? '',
    'role' => $user_data['role'] ?? 'staff'
];

include '../includes/header.php';
?>

<div class="container">
    <!-- Page Header -->
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title-section">
                <h1><?php echo $edit_mode ? 'Edit User' : 'Add New User'; ?></h1>
                <p class="page-description">
                    <?php if ($edit_mode): ?>
                        Update user information and permissions for 
                        <strong><?php echo htmlspecialchars($user_data['full_name']); ?></strong>
                    <?php else: ?>
                        Create a new user account with appropriate role and permissions
                    <?php endif; ?>
                </p>
            </div>
            <div class="page-actions">
                <a href="user_management_view.php" class="btn btn-secondary">
                    Back to User Management
                </a>
            </div>
        </div>
    </div>

    <!-- Success/Error Messages -->
    <?php if (isset($_GET['error'])): ?>
        <div class="message error">
            <?php
            $error_messages = [
                'missing_fields' => 'Please fill in all required fields.',
                'passwords_dont_match' => 'Password and confirm password do not match.',
                'weak_password' => 'Password must be at least 8 characters long.',
                'invalid_username' => 'Username can only contain letters, numbers, and underscores.',
                'username_exists' => 'Username already exists. Please choose a different one.',
                'invalid_role' => 'Invalid role selected.',
                'user_not_found' => 'User not found.',
                'save_failed' => 'Failed to save user. Please try again.',
                'database_error' => 'A database error occurred. Please try again.'
            ];
            
            $error_code = $_GET['error'];
            echo $error_messages[$error_code] ?? 'An unknown error occurred.';
            
            // Show additional error details if provided
            if (isset($_GET['details'])) {
                echo '<br><small>' . htmlspecialchars($_GET['details']) . '</small>';
            }
            ?>
        </div>
    <?php endif; ?>

    <!-- Form Section -->
    <div class="main-content-area">
        <div class="content-card">
            <div class="card-header">
                <h2><?php echo $edit_mode ? 'User Information' : 'New User Details'; ?></h2>
                <div class="card-actions">
                    <?php if ($edit_mode): ?>
                        <span class="card-subtitle">User ID: <?php echo $user_data['user_id']; ?></span>
                    <?php else: ?>
                        <span class="card-subtitle">All fields marked with * are required</span>
                    <?php endif; ?>
                </div>
            </div>

            <form method="POST" action="../controllers/user_controller.php" class="user-form" id="userForm">
                <input type="hidden" name="action" value="<?php echo $edit_mode ? 'update' : 'create'; ?>">
                <?php if ($edit_mode): ?>
                    <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                <?php endif; ?>

                <div class="form-sections">
                    <!-- Basic Information Section -->
                    <div class="form-section">
                        <h3>Basic Information</h3>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="full_name">Full Name <span class="required">*</span></label>
                                <input 
                                    type="text" 
                                    id="full_name" 
                                    name="full_name" 
                                    value="<?php echo htmlspecialchars($form_values['full_name']); ?>"
                                    required 
                                    maxlength="100"
                                    placeholder="Enter the user's full name"
                                >
                                <small class="form-hint">The user's complete legal name</small>
                            </div>

                            <div class="form-group">
                                <label for="username">Username <span class="required">*</span></label>
                                <input 
                                    type="text" 
                                    id="username" 
                                    name="username" 
                                    value="<?php echo htmlspecialchars($form_values['username']); ?>"
                                    required 
                                    maxlength="50"
                                    pattern="[a-zA-Z0-9_]+"
                                    placeholder="Enter username (letters, numbers, underscore only)"
                                    title="Username can only contain letters, numbers, and underscores"
                                >
                                <small class="form-hint">
                                    Used for login. Only letters, numbers, and underscores allowed.
                                    <?php if ($edit_mode): ?>
                                        <strong>Note:</strong> Changing username will affect user's login credentials.
                                    <?php endif; ?>
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- Role and Permissions Section -->
                    <div class="form-section">
                        <h3>Role and Permissions</h3>
                        
                        <div class="form-group">
                            <label for="role">User Role <span class="required">*</span></label>
                            <select id="role" name="role" required>
                                <option value="staff" <?php echo ($form_values['role'] === 'staff') ? 'selected' : ''; ?>>
                                    Staff - Can record production steps
                                </option>
                                <option value="engineer" <?php echo ($form_values['role'] === 'engineer') ? 'selected' : ''; ?>>
                                    Engineer - Can manage production and approve steps
                                </option>
                                <option value="admin" <?php echo ($form_values['role'] === 'admin') ? 'selected' : ''; ?>>
                                    Administrator - Full system access
                                </option>
                            </select>
                            <small class="form-hint">
                                <strong>Staff:</strong> Basic access to record production steps<br>
                                <strong>Engineer:</strong> Can manage production processes and approve steps<br>
                                <strong>Administrator:</strong> Full access including user management
                            </small>
                        </div>
                    </div>

                    <!-- Password Section -->
                    <div class="form-section">
                        <h3>
                            <?php echo $edit_mode ? 'Change Password (Optional)' : 'Password Setup'; ?>
                        </h3>
                        
                        <?php if ($edit_mode): ?>
                            <div class="info-box">
                                <p><strong>Leave password fields blank to keep the current password unchanged.</strong></p>
                                <p>Only fill these fields if you want to change the user's password.</p>
                            </div>
                        <?php endif; ?>

                        <div class="form-grid">
                            <div class="form-group">
                                <label for="password">
                                    Password 
                                    <?php if (!$edit_mode): ?><span class="required">*</span><?php endif; ?>
                                </label>
                                <input 
                                    type="password" 
                                    id="password" 
                                    name="password" 
                                    <?php if (!$edit_mode): ?>required<?php endif; ?>
                                    minlength="8"
                                    placeholder="<?php echo $edit_mode ? 'Enter new password (optional)' : 'Enter password (minimum 8 characters)'; ?>"
                                >
                                <small class="form-hint">
                                    Password must be at least 8 characters long for security.
                                </small>
                            </div>

                            <div class="form-group">
                                <label for="confirm_password">
                                    Confirm Password 
                                    <?php if (!$edit_mode): ?><span class="required">*</span><?php endif; ?>
                                </label>
                                <input 
                                    type="password" 
                                    id="confirm_password" 
                                    name="confirm_password" 
                                    <?php if (!$edit_mode): ?>required<?php endif; ?>
                                    minlength="8"
                                    placeholder="<?php echo $edit_mode ? 'Confirm new password' : 'Confirm password'; ?>"
                                >
                                <small class="form-hint">
                                    Must match the password entered above.
                                </small>
                            </div>
                        </div>
                    </div>

                    <?php if ($edit_mode): ?>
                        <!-- User Information Section (View Only) -->
                        <div class="form-section">
                            <h3>Account Information</h3>
                            
                            <div class="info-grid">
                                <div class="info-item">
                                    <label>User ID:</label>
                                    <span><?php echo htmlspecialchars($user_data['user_id']); ?></span>
                                </div>
                                <div class="info-item">
                                    <label>Account Created:</label>
                                    <span><?php echo date('M j, Y g:i A', strtotime($user_data['created_at'])); ?></span>
                                </div>
                                <div class="info-item">
                                    <label>Current Role:</label>
                                    <span class="role-badge role-<?php echo strtolower($user_data['role']); ?>">
                                        <?php echo htmlspecialchars(ucfirst($user_data['role'])); ?>
                                    </span>
                                </div>
                            </div>

                            <?php if ($user_data['user_id'] == $_SESSION['user_id']): ?>
                                <div class="warning-box">
                                    <p><strong>Note:</strong> You are editing your own account.</p>
                                    <p>Be careful when changing your role or other critical settings.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Form Actions -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <?php echo $edit_mode ? 'Update User' : 'Create User'; ?>
                    </button>
                    <a href="user_management_view.php" class="btn btn-secondary">Cancel</a>
                    
                    <?php if ($edit_mode): ?>
                        <button type="button" onclick="resetForm()" class="btn btn-outline">
                            Reset Changes
                        </button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Form validation and enhancement
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('userForm');
    const passwordField = document.getElementById('password');
    const confirmPasswordField = document.getElementById('confirm_password');
    const submitBtn = document.getElementById('submitBtn');
    
    // Password matching validation
    function validatePasswords() {
        const password = passwordField.value;
        const confirmPassword = confirmPasswordField.value;
        
        // Only validate if both fields have content
        if (password || confirmPassword) {
            if (password !== confirmPassword) {
                confirmPasswordField.setCustomValidity('Passwords do not match');
            } else if (password.length > 0 && password.length < 8) {
                passwordField.setCustomValidity('Password must be at least 8 characters long');
                confirmPasswordField.setCustomValidity('');
            } else {
                passwordField.setCustomValidity('');
                confirmPasswordField.setCustomValidity('');
            }
        } else {
            passwordField.setCustomValidity('');
            confirmPasswordField.setCustomValidity('');
        }
    }
    
    // Add event listeners for password validation
    passwordField.addEventListener('input', validatePasswords);
    confirmPasswordField.addEventListener('input', validatePasswords);
    
    // Username validation (letters, numbers, underscore only)
    const usernameField = document.getElementById('username');
    usernameField.addEventListener('input', function() {
        const username = this.value;
        const validPattern = /^[a-zA-Z0-9_]*$/;
        
        if (!validPattern.test(username)) {
            this.setCustomValidity('Username can only contain letters, numbers, and underscores');
        } else {
            this.setCustomValidity('');
        }
    });
    
    // Form submission validation
    form.addEventListener('submit', function(e) {
        const isEditMode = <?php echo $edit_mode ? 'true' : 'false'; ?>;
        const password = passwordField.value;
        const confirmPassword = confirmPasswordField.value;
        
        // For new users, password is required
        if (!isEditMode && !password) {
            e.preventDefault();
            alert('Password is required for new users');
            passwordField.focus();
            return false;
        }
        
        // If password is provided, validate it
        if (password || confirmPassword) {
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Password and confirm password do not match');
                confirmPasswordField.focus();
                return false;
            }
            
            if (password.length < 8) {
                e.preventDefault();
                alert('Password must be at least 8 characters long');
                passwordField.focus();
                return false;
            }
        }
        
        // Show loading state
        submitBtn.innerHTML = isEditMode ? 'Updating...' : 'Creating...';
        submitBtn.disabled = true;
    });
});

// Reset form function (for edit mode)
function resetForm() {
    if (confirm('Are you sure you want to reset all changes?')) {
        // Reset to original values
        document.getElementById('full_name').value = '<?php echo addslashes($form_values['full_name']); ?>';
        document.getElementById('username').value = '<?php echo addslashes($form_values['username']); ?>';
        document.getElementById('role').value = '<?php echo $form_values['role']; ?>';
        document.getElementById('password').value = '';
        document.getElementById('confirm_password').value = '';
        
        // Clear validation states
        document.getElementById('password').setCustomValidity('');
        document.getElementById('confirm_password').setCustomValidity('');
        document.getElementById('username').setCustomValidity('');
    }
}

// Auto-focus first field
document.getElementById('full_name').focus();
</script>

<!-- Additional CSS for User Form Specific Styling -->
<style>
/* Form Sections */
.form-sections {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.form-section {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 8px;
    border: 1px solid #e9ecef;
}

.form-section h3 {
    margin: 0 0 1rem 0;
    color: #495057;
    font-size: 1.125rem;
    font-weight: 600;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
}

/* Form Groups */
.form-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.form-group label {
    font-weight: 600;
    color: #495057;
    font-size: 0.875rem;
}

.form-group input,
.form-group select {
    padding: 0.75rem;
    border: 1px solid #ced4da;
    border-radius: 0.375rem;
    font-size: 1rem;
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

.form-group input:focus,
.form-group select:focus {
    outline: 0;
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.form-hint {
    color: #6c757d;
    font-size: 0.75rem;
    line-height: 1.4;
}

.required {
    color: #dc3545;
    font-weight: bold;
}

/* Info Boxes */
.info-box {
    background-color: #e7f3ff;
    border: 1px solid #b3d9ff;
    border-radius: 0.375rem;
    padding: 1rem;
    margin-bottom: 1rem;
}

.info-box p {
    margin: 0 0 0.5rem 0;
    color: #0056b3;
}

.info-box p:last-child {
    margin-bottom: 0;
}

.warning-box {
    background-color: #fff3cd;
    border: 1px solid #ffeaa7;
    border-radius: 0.375rem;
    padding: 1rem;
    margin-top: 1rem;
}

.warning-box p {
    margin: 0 0 0.5rem 0;
    color: #856404;
}

.warning-box p:last-child {
    margin-bottom: 0;
}

/* Info Grid */
.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.info-item {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.info-item label {
    font-weight: 600;
    color: #6c757d;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.info-item span {
    color: #495057;
    font-weight: 500;
}

/* Role Badges */
.role-badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 1rem;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.role-badge.role-admin {
    background-color: #dc3545;
    color: white;
}

.role-badge.role-engineer {
    background-color: #007bff;
    color: white;
}

.role-badge.role-staff {
    background-color: #28a745;
    color: white;
}

/* Form Actions */
.form-actions {
    display: flex;
    gap: 1rem;
    justify-content: flex-start;
    align-items: center;
    padding-top: 2rem;
    border-top: 1px solid #e9ecef;
    margin-top: 2rem;
}

/* Responsive Design */
@media (max-width: 768px) {
    .form-grid {
        grid-template-columns: 1fr;
    }
    
    .form-actions {
        flex-direction: column;
        align-items: stretch;
    }
    
    .info-grid {
        grid-template-columns: 1fr;
    }
}

/* Input validation states */
.form-group input:invalid {
    border-color: #dc3545;
}

.form-group input:valid {
    border-color: #28a745;
}

/* Select styling enhancement */
.form-group select {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
    background-position: right 0.5rem center;
    background-repeat: no-repeat;
    background-size: 1.5em 1.5em;
    padding-right: 2.5rem;
    appearance: none;
}
</style>

<?php include '../includes/footer.php'; ?>
