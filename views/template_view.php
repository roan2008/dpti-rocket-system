<?php
/**
 * Template View - Read-only view of step template details
 * Shows template information and fields without editing capability
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

// Check if user has permission to view templates (admin or engineer)
if (!has_role('admin') && !has_role('engineer')) {
    header('Location: ../dashboard.php?error=insufficient_permissions');
    exit;
}

// Get template ID from URL
$template_id = (int) ($_GET['id'] ?? 0);
if ($template_id <= 0) {
    header('Location: templates_list_view.php?error=invalid_template_id');
    exit;
}

// Get template with fields
$template = getTemplateWithFields($pdo, $template_id);
if (!$template) {
    header('Location: templates_list_view.php?error=template_not_found');
    exit;
}

// Get user details for created_by lookup
function getUserName($pdo, $user_id) {
    try {
        $stmt = $pdo->prepare("SELECT full_name FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ? $user['full_name'] : 'Unknown User';
    } catch (PDOException $e) {
        return 'Unknown User';
    }
}

include '../includes/header.php';
?>

<div class="dashboard-container">
    <div class="dashboard-header">
        <h1>View Step Template</h1>
        <div class="user-info">
            <p>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</p>
            <p>Role: <?php echo htmlspecialchars($_SESSION['role']); ?></p>
            <a href="../controllers/logout_controller.php" class="btn-logout">Logout</a>
        </div>
    </div>

    <!-- Breadcrumb Navigation -->
    <div class="page-header">
        <div class="header-left">
            <h2><?php echo htmlspecialchars($template['step_name']); ?></h2>
            <p class="breadcrumb">
                <a href="../dashboard.php">Dashboard</a> → 
                <a href="templates_list_view.php">Template Management</a> → 
                <span>View Template</span>
            </p>
        </div>
        <div class="header-actions">
            <a href="templates_list_view.php" class="btn btn-secondary">← Back to Templates</a>
            <?php if (has_role('admin') || has_role('engineer')): ?>
                <a href="template_form_view.php?id=<?php echo $template_id; ?>" class="btn btn-primary">Edit Template</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Template Information Section -->
    <div class="section">
        <div class="section-header">
            <h2>Template Information</h2>
        </div>
        
        <div class="section-content">
            <div class="detail-grid">
                <div class="detail-item">
                    <label class="detail-label">Template ID</label>
                    <div class="detail-value"><?php echo htmlspecialchars($template['template_id']); ?></div>
                </div>

                <div class="detail-item">
                    <label class="detail-label">Step Name</label>
                    <div class="detail-value"><?php echo htmlspecialchars($template['step_name']); ?></div>
                </div>

                <div class="detail-item">
                    <label class="detail-label">Description</label>
                    <div class="detail-value">
                        <?php 
                        $description = $template['step_description'] ?? '';
                        echo $description ? htmlspecialchars($description) : '<em>No description provided</em>';
                        ?>
                    </div>
                </div>

                <div class="detail-item">
                    <label class="detail-label">Status</label>
                    <div class="detail-value">
                        <span class="badge badge-<?php echo $template['is_active'] ? 'active' : 'inactive'; ?>">
                            <?php echo $template['is_active'] ? 'Active' : 'Inactive'; ?>
                        </span>
                    </div>
                </div>

                <div class="detail-item">
                    <label class="detail-label">Created By</label>
                    <div class="detail-value"><?php echo htmlspecialchars(getUserName($pdo, $template['created_by'])); ?></div>
                </div>

                <div class="detail-item">
                    <label class="detail-label">Created Date</label>
                    <div class="detail-value"><?php echo date('M j, Y g:i A', strtotime($template['created_at'])); ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Template Fields Section -->
    <div class="section">
        <div class="section-header">
            <h2>Form Fields (<?php echo count($template['fields']); ?> fields)</h2>
        </div>
        
        <div class="section-content">
            <?php if (empty($template['fields'])): ?>
                <div class="empty-state">
                    <p>No form fields defined for this template.</p>
                    <?php if (has_role('admin') || has_role('engineer')): ?>
                        <p><a href="template_form_view.php?id=<?php echo $template_id; ?>">Edit this template</a> to add form fields.</p>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="fields-preview">
                    <?php foreach ($template['fields'] as $index => $field): ?>
                        <div class="field-preview-card">
                            <div class="field-preview-header">
                                <div class="field-number"><?php echo $index + 1; ?></div>
                                <div class="field-title"><?php echo htmlspecialchars($field['field_label']); ?></div>
                                <?php if ($field['is_required']): ?>
                                    <span class="required-badge">Required</span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="field-preview-details">
                                <div class="field-detail">
                                    <label>Field Name:</label>
                                    <code><?php echo htmlspecialchars($field['field_name']); ?></code>
                                </div>
                                
                                <div class="field-detail">
                                    <label>Field Type:</label>
                                    <span class="field-type-badge"><?php echo htmlspecialchars($field['field_type']); ?></span>
                                </div>

                                <?php if ($field['field_type'] === 'select' && !empty($field['options'])): ?>
                                    <div class="field-detail">
                                        <label>Options:</label>
                                        <div class="select-options">
                                            <?php foreach ($field['options'] as $option): ?>
                                                <span class="option-tag"><?php echo htmlspecialchars($option); ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <div class="field-detail">
                                    <label>Display Order:</label>
                                    <span><?php echo htmlspecialchars($field['display_order']); ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Template Usage Information -->
    <div class="section">
        <div class="section-header">
            <h2>Usage Information</h2>
        </div>
        
        <div class="section-content">
            <div class="info-cards">
                <div class="info-card">
                    <div class="info-card-icon">📋</div>
                    <div class="info-card-content">
                        <h4>Template Purpose</h4>
                        <p>This template defines the form fields that will appear when staff members select "<strong><?php echo htmlspecialchars($template['step_name']); ?></strong>" as their production step.</p>
                    </div>
                </div>
                
                <div class="info-card">
                    <div class="info-card-icon">🔧</div>
                    <div class="info-card-content">
                        <h4>Field Configuration</h4>
                        <p>The template has <strong><?php echo count($template['fields']); ?> form fields</strong> that will be dynamically generated when this step type is selected.</p>
                    </div>
                </div>
                
                <div class="info-card">
                    <div class="info-card-icon">⚡</div>
                    <div class="info-card-content">
                        <h4>Dynamic Forms</h4>
                        <p>When staff add production steps, selecting this template will automatically generate the appropriate form fields based on this configuration.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Additional styles for template view */
.detail-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.detail-item {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    border-left: 4px solid #007bff;
}

.detail-label {
    font-weight: 600;
    color: #495057;
    font-size: 14px;
    margin-bottom: 5px;
    display: block;
}

.detail-value {
    color: #212529;
    font-size: 16px;
}

.badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.badge-active {
    background-color: #28a745;
    color: white;
}

.badge-inactive {
    background-color: #6c757d;
    color: white;
}

.fields-preview {
    display: grid;
    gap: 20px;
}

.field-preview-card {
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.field-preview-header {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 15px;
    padding-bottom: 15px;
    border-bottom: 1px solid #e9ecef;
}

.field-number {
    background: #007bff;
    color: white;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 14px;
}

.field-title {
    font-size: 18px;
    font-weight: 600;
    color: #212529;
    flex: 1;
}

.required-badge {
    background: #dc3545;
    color: white;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.field-preview-details {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}

.field-detail label {
    font-weight: 600;
    color: #6c757d;
    font-size: 12px;
    text-transform: uppercase;
    margin-bottom: 5px;
    display: block;
}

.field-type-badge {
    background: #17a2b8;
    color: white;
    padding: 4px 8px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 600;
}

.select-options {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.option-tag {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    padding: 4px 8px;
    border-radius: 6px;
    font-size: 12px;
    color: #495057;
}

.info-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
}

.info-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    border-radius: 12px;
    display: flex;
    gap: 15px;
}

.info-card-icon {
    font-size: 24px;
}

.info-card-content h4 {
    margin: 0 0 10px 0;
    font-size: 16px;
    font-weight: 600;
}

.info-card-content p {
    margin: 0;
    font-size: 14px;
    line-height: 1.5;
    opacity: 0.9;
}
</style>

<?php include '../includes/footer.php'; ?>
