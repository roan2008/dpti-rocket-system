# **Golden Rules Page Template Pattern**

Use this template structure for ALL list/management pages in your application.

## **Basic Page Structure:**

```php
<?php
/**
 * [Page Name] - [Brief Description]
 */

// Standard session and auth checks
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/user_functions.php';
// ... other required includes

// Access control checks
if (!is_logged_in()) {
    header('Location: login_view.php');
    exit;
}

// Role-based access if needed
if (!has_role('admin') && !has_role('engineer')) {
    header('Location: ../dashboard.php?error=insufficient_permissions');
    exit;
}

// Get data for this page
$data = get_page_data($pdo); // Replace with actual data function

include '../includes/header.php';
?>

<div class="container">
    <!-- GOLDEN RULE #2: Consistent Page Header -->
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title-section">
                <h1>[Page Title]</h1>
                <p class="page-description">[Brief description of what this page does]</p>
            </div>
            <div class="page-actions">
                <!-- PRIMARY ACTION BUTTONS ONLY -->
                <a href="[primary_action_url]" class="btn btn-primary">
                    <span>[icon]</span> [Primary Action]
                </a>
                <!-- Secondary actions if needed -->
                <a href="[secondary_action_url]" class="btn btn-secondary">
                    <span>[icon]</span> [Secondary Action]
                </a>
            </div>
        </div>
    </div>

    <!-- Success/Error Messages (Standard Pattern) -->
    <?php if (isset($_GET['success'])): ?>
        <div class="message success">
            <!-- Handle success messages -->
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="message error">
            <!-- Handle error messages -->
        </div>
    <?php endif; ?>
    
    <!-- OPTIONAL: Statistics Grid (only if page-specific stats are needed) -->
    <!-- GOLDEN RULE #1: Don't duplicate global stats - those live only on dashboard -->
    <?php if ($show_page_stats): ?>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">[icon]</div>
                <div class="stat-content">
                    <div class="stat-number">[number]</div>
                    <div class="stat-label">[Page-specific statistic]</div>
                </div>
            </div>
            <!-- More page-specific stats -->
        </div>
    <?php endif; ?>

    <!-- Main Content Area -->
    <div class="main-content-area">
        <!-- Primary Content Card -->
        <div class="content-card">
            <div class="card-header">
                <h2>[Content Section Title]</h2>
                <div class="card-actions">
                    <span class="card-subtitle">[Optional subtitle or count]</span>
                </div>
            </div>
            
            <?php if (empty($data)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">[icon]</div>
                    <h3>No [items] found</h3>
                    <p>Get started by [action description].</p>
                    <a href="[action_url]" class="btn btn-primary">[Call to Action]</a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table-modern">
                        <thead>
                            <tr>
                                <th>[Column 1]</th>
                                <th>[Column 2]</th>
                                <th>[Column 3]</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data as $item): ?>
                                <tr>
                                    <td>[item data]</td>
                                    <td>[item data]</td>
                                    <td>[item data]</td>
                                    <td class="actions">
                                        <div class="action-buttons">
                                            <!-- TERTIARY NAVIGATION: inline actions -->
                                            <a href="[view_url]" class="btn btn-sm btn-secondary">View</a>
                                            <a href="[edit_url]" class="btn btn-sm btn-secondary">Edit</a>
                                            <?php if (has_role('admin')): ?>
                                                <button onclick="confirmDelete([id])" class="btn btn-sm btn-danger">Delete</button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- OPTIONAL: Secondary content or quick actions (if truly needed) -->
        <!-- Keep this minimal - avoid recreating navigation cards -->
    </div>
</div>
<!-- End container -->

<?php include '../includes/footer.php'; ?>
```

## **Key Rules for This Template:**

### ✅ **DO:**
- Always use the `.page-header` with title and actions
- Put primary actions in `.page-actions` area  
- Use `.content-card` for main data display
- Use `.empty-state` for no-data scenarios
- Follow the three-tier action hierarchy:
  1. **Primary**: Page header actions (Add New, Import, etc.)
  2. **Secondary**: Navigation bar links (Dashboard, Templates, etc.)  
  3. **Tertiary**: Inline row actions (View, Edit, Delete)

### ❌ **DON'T:**
- Don't put user info/logout outside navigation bar
- Don't recreate global statistics on sub-pages
- Don't create navigation card grids
- Don't put breadcrumbs in content area (use page header only)
- Don't mix action button hierarchies

## **Specific Applications:**

### **For `templates_list_view.php`:**
```php
<h1>Step Templates</h1>
<p class="page-description">Create and manage production step templates</p>
<!-- Primary action: Add New Template -->
<a href="template_form_view.php" class="btn btn-primary">
    <span>📋</span> Add New Template
</a>
```

### **For `pending_approvals_view.php`:**
```php
<h1>Pending Approvals</h1>
<p class="page-description">Review and approve production steps</p>
<!-- Primary action: Bulk operations if needed -->
<a href="?action=approve_all" class="btn btn-primary">
    <span>✅</span> Approve All
</a>
```

### **For Detail Pages (like `rocket_detail_view.php`):**
```php
<h1>Rocket Details</h1>
<p class="page-description">View and manage rocket information</p>
<!-- Primary action: Edit -->
<a href="?edit=1" class="btn btn-primary">
    <span>✏️</span> Edit Rocket
</a>
```

This template eliminates redundancy and creates consistency across your entire application!
