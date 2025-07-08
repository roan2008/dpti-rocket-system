<?php
/**
 * User Management View
 * Display and manage system users (admin only)
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

// Get filter parameters
$role_filter = $_GET['role'] ?? '';
$search_term = $_GET['search'] ?? '';

// Get users based on filters
if (!empty($search_term)) {
    $users = search_users($pdo, $search_term);
    $total_users = count($users);
} elseif (!empty($role_filter)) {
    $users = get_users_by_role($pdo, $role_filter);
    $total_users = count($users);
} else {
    $users = get_all_users($pdo);
    $total_users = count_users($pdo);
}

include '../includes/header.php';
?>

<div class="container">
    <!-- Page Header -->
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title-section">
                <h1>User Management</h1>
                <p class="page-description">Manage system users, roles, and permissions</p>
            </div>
            <div class="page-actions">
                <a href="user_form_view.php" class="btn btn-primary">
                    Add New User
                </a>
            </div>
        </div>
    </div>

    <!-- Success/Error Messages -->
    <?php if (isset($_GET['success'])): ?>
        <div class="message success">
            <?php
            switch ($_GET['success']) {
                case 'user_created':
                    echo "User successfully created!";
                    if (isset($_GET['username'])) {
                        echo " (Username: " . htmlspecialchars($_GET['username']) . ")";
                    }
                    break;
                case 'user_updated':
                    echo "User successfully updated!";
                    break;
                case 'user_deleted':
                    echo "User successfully deleted!";
                    break;
                default:
                    echo "Operation completed successfully!";
            }
            ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="message error">
            <?php
            switch ($_GET['error']) {
                case 'insufficient_permissions':
                    echo "You don't have permission to perform this action.";
                    break;
                case 'user_not_found':
                    echo "User not found.";
                    break;
                case 'cannot_delete_self':
                    echo "You cannot delete your own account.";
                    break;
                case 'cannot_delete_last_admin':
                    echo "Cannot delete the last admin account.";
                    break;
                case 'user_has_data':
                    echo "Cannot delete user: they have production steps recorded.";
                    break;
                case 'create_failed':
                    echo "Failed to create user.";
                    break;
                case 'update_failed':
                    echo "Failed to update user.";
                    break;
                case 'delete_failed':
                    echo "Failed to delete user.";
                    break;
                default:
                    echo "An error occurred. Please try again.";
            }
            ?>
        </div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">👥</div>
            <div class="stat-content">
                <div class="stat-number"><?php echo $total_users; ?></div>
                <div class="stat-label">Total Users</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">👨‍💼</div>
            <div class="stat-content">
                <div class="stat-number"><?php echo count(get_users_by_role($pdo, 'admin')); ?></div>
                <div class="stat-label">Administrators</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">🔧</div>
            <div class="stat-content">
                <div class="stat-number"><?php echo count(get_users_by_role($pdo, 'engineer')); ?></div>
                <div class="stat-label">Engineers</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">👷</div>
            <div class="stat-content">
                <div class="stat-number"><?php echo count(get_users_by_role($pdo, 'staff')); ?></div>
                <div class="stat-label">Staff Members</div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content-area">
        <div class="content-card">
            <div class="card-header">
                <h2>System Users</h2>
                <div class="card-actions">
                    <span class="card-subtitle"><?php echo count($users); ?> users displayed</span>
                </div>
            </div>

            <!-- Filter and Search Section -->
            <div class="filters-section">
                <form method="GET" class="filters-form">
                    <div class="filter-group">
                        <label for="role_filter">Filter by Role:</label>
                        <select id="role_filter" name="role" onchange="this.form.submit()">
                            <option value="">All Roles</option>
                            <option value="admin" <?php echo ($role_filter === 'admin') ? 'selected' : ''; ?>>Admin</option>
                            <option value="engineer" <?php echo ($role_filter === 'engineer') ? 'selected' : ''; ?>>Engineer</option>
                            <option value="staff" <?php echo ($role_filter === 'staff') ? 'selected' : ''; ?>>Staff</option>
                        </select>
                    </div>
                    
                    <div class="search-group">
                        <label for="search_input">Search Users:</label>
                        <div class="search-input-group">
                            <input 
                                type="text" 
                                id="search_input" 
                                name="search" 
                                placeholder="Search by name or username..." 
                                value="<?php echo htmlspecialchars($search_term); ?>"
                            >
                            <button type="submit" class="btn btn-secondary">Search</button>
                            <?php if (!empty($search_term) || !empty($role_filter)): ?>
                                <a href="user_management_view.php" class="btn btn-outline">Clear Filters</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Users Table -->
            <?php if (empty($users)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">👥</div>
                    <h3>No users found</h3>
                    <?php if (!empty($search_term) || !empty($role_filter)): ?>
                        <p>No users match your current filters.</p>
                        <a href="user_management_view.php" class="btn btn-secondary">Clear Filters</a>
                    <?php else: ?>
                        <p>There are no users in the system yet.</p>
                        <a href="user_form_view.php" class="btn btn-primary">Add First User</a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table-modern">
                        <thead>
                            <tr>
                                <th>Full Name</th>
                                <th>Username</th>
                                <th>Role</th>
                                <th>Created Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td class="user-name">
                                        <span class="font-medium"><?php echo htmlspecialchars($user['full_name']); ?></span>
                                        <?php if ($user['user_id'] == $_SESSION['user_id']): ?>
                                            <span class="user-badge-self">You</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="username">
                                        <span class="font-mono"><?php echo htmlspecialchars($user['username']); ?></span>
                                    </td>
                                    <td class="role">
                                        <span class="role-badge role-<?php echo strtolower($user['role']); ?>">
                                            <?php echo htmlspecialchars(ucfirst($user['role'])); ?>
                                        </span>
                                    </td>
                                    <td class="created-date">
                                        <span class="text-gray-600"><?php echo date('M j, Y', strtotime($user['created_at'])); ?></span>
                                    </td>
                                    <td class="actions">
                                        <div class="action-buttons">
                                            <a href="user_form_view.php?id=<?php echo $user['user_id']; ?>" 
                                               class="btn btn-sm btn-secondary">Edit</a>
                                            
                                            <?php if ($user['user_id'] != $_SESSION['user_id']): ?>
                                                <button onclick="confirmDelete(<?php echo $user['user_id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')" 
                                                        class="btn btn-sm btn-danger">Delete</button>
                                            <?php else: ?>
                                                <span class="btn btn-sm btn-disabled" title="Cannot delete your own account">Delete</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Table Summary -->
                <div class="table-summary">
                    <div class="summary-stats">
                        <div class="stat-item">
                            <label>Showing:</label>
                            <span><?php echo count($users); ?> of <?php echo $total_users; ?> users</span>
                        </div>
                        <?php if (!empty($search_term)): ?>
                            <div class="stat-item">
                                <label>Search:</label>
                                <span>"<?php echo htmlspecialchars($search_term); ?>"</span>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($role_filter)): ?>
                            <div class="stat-item">
                                <label>Role:</label>
                                <span><?php echo htmlspecialchars(ucfirst($role_filter)); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Confirm User Deletion</h3>
            <button onclick="closeDeleteModal()" class="modal-close">&times;</button>
        </div>
        
        <div class="modal-body">
            <p>Are you sure you want to delete user <strong id="deleteUsername">username</strong>?</p>
            <div class="warning-box">
                <p><strong>Warning:</strong> This action cannot be undone.</p>
                <ul>
                    <li>The user will lose access to the system immediately</li>
                    <li>If this user has recorded production steps, deletion will be prevented</li>
                    <li>If this is the last admin account, deletion will be prevented</li>
                </ul>
            </div>
        </div>
        
        <div class="modal-footer">
            <form method="POST" action="../controllers/user_controller.php" style="display: inline;">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="user_id" id="deleteUserId" value="">
                <button type="button" onclick="closeDeleteModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-danger">Yes, Delete User</button>
            </form>
        </div>
    </div>
</div>

<script>
// Delete confirmation functions
function confirmDelete(userId, username) {
    document.getElementById('deleteUserId').value = userId;
    document.getElementById('deleteUsername').textContent = username;
    document.getElementById('deleteModal').style.display = 'flex';
}

function closeDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('deleteModal');
    if (event.target === modal) {
        closeDeleteModal();
    }
}

// Close modal on Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        const modal = document.getElementById('deleteModal');
        if (modal.style.display === 'flex') {
            closeDeleteModal();
        }
    }
});

// Auto-submit search form on Enter key
document.getElementById('search_input').addEventListener('keypress', function(event) {
    if (event.key === 'Enter') {
        event.preventDefault();
        this.form.submit();
    }
});
</script>

<!-- Additional CSS for User Management Specific Styling -->
<style>
/* Filter Section */
.filters-section {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    border: 1px solid #e9ecef;
}

.filters-form {
    display: flex;
    flex-wrap: wrap;
    gap: 1.5rem;
    align-items: end;
}

.filter-group, .search-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.filter-group label, .search-group label {
    font-weight: 600;
    color: #495057;
    font-size: 0.875rem;
}

.search-input-group {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

.search-input-group input {
    min-width: 250px;
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

/* User Badge */
.user-badge-self {
    display: inline-block;
    padding: 0.125rem 0.5rem;
    background-color: #ffc107;
    color: #212529;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    font-weight: 600;
    margin-left: 0.5rem;
}

/* Warning Box in Modal */
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
    font-weight: 600;
}

.warning-box ul {
    margin: 0;
    padding-left: 1.25rem;
    color: #856404;
}

.warning-box li {
    margin-bottom: 0.25rem;
}

/* Disabled Button */
.btn-disabled {
    background-color: #e9ecef;
    color: #6c757d;
    border-color: #e9ecef;
    cursor: not-allowed;
    opacity: 0.65;
}

.btn-disabled:hover {
    background-color: #e9ecef;
    color: #6c757d;
    border-color: #e9ecef;
}

/* Responsive Design */
@media (max-width: 768px) {
    .filters-form {
        flex-direction: column;
        align-items: stretch;
    }
    
    .search-input-group {
        flex-direction: column;
    }
    
    .search-input-group input {
        min-width: auto;
        width: 100%;
    }
    
    .action-buttons {
        flex-direction: column;
        gap: 0.25rem;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>

<?php include '../includes/footer.php'; ?>
