# **Golden Rules Implementation Testing Checklist**

## **Manual Testing Guide**

### **✅ Phase 1: Dashboard (Complete)**

**Page:** `dashboard.php`
- [ ] Page header displays correctly with title and description
- [ ] User info appears ONLY in navigation bar (not duplicated)
- [ ] Statistics grid shows 4 cards with proper alignment
- [ ] Quick actions grid replaces old navigation cards
- [ ] No redundant user information in content area
- [ ] Responsive design works on mobile

---

### **⏳ Phase 2: Apply Template to Other Pages**

Use the template pattern from `docs/GOLDEN_RULES_PAGE_TEMPLATE.md` to refactor these pages:

#### **🎯 High Priority Pages:**

**1. `views/templates_list_view.php`**
- [ ] Apply page-header pattern
- [ ] Move "Add New Template" to page-actions
- [ ] Remove any redundant navigation
- [ ] Use content-card for templates table
- [ ] Test empty state when no templates exist

**2. `views/pending_approvals_view.php`**
- [ ] Apply page-header pattern
- [ ] Add bulk actions in page-actions if applicable
- [ ] Remove duplicate approval stats (keep only on dashboard)
- [ ] Use content-card for approvals table
- [ ] Test empty state when no pending approvals

**3. `views/rocket_detail_view.php`**
- [ ] Apply page-header pattern
- [ ] Move "Edit" and other actions to page-actions
- [ ] Remove redundant user/nav elements
- [ ] Use content-card for rocket details

#### **🎯 Medium Priority Pages:**

**4. `views/user_management_view.php`**
- [ ] Apply page-header pattern
- [ ] Move "Add User" to page-actions
- [ ] Use content-card for users table

**5. `views/production_steps_view.php`**
- [ ] Apply page-header pattern
- [ ] Move "Add Step" to page-actions
- [ ] Use content-card for steps table

---

### **✅ Phase 3: Verification Tests**

After applying the template to each page:

#### **Golden Rule #1: Single Information Home**
- [ ] User info appears ONLY in navigation bar
- [ ] Global statistics appear ONLY on dashboard
- [ ] No duplicate navigation elements
- [ ] No redundant user context anywhere

#### **Golden Rule #2: Consistent Page Header**
- [ ] Every page has `.page-header` section
- [ ] Title and description present on every page
- [ ] Primary actions in `.page-actions` area
- [ ] Consistent spacing and alignment

#### **Golden Rule #3: Clear Navigation Hierarchy**
- [ ] **Primary**: Main navigation in header works correctly
- [ ] **Secondary**: Page-level actions in page-header work
- [ ] **Tertiary**: Row-level actions (Edit, Delete, View) work
- [ ] No mixed action hierarchies

#### **Design System Consistency**
- [ ] All buttons use `btn btn-*` classes
- [ ] All tables use `table-modern` class
- [ ] All containers use `container` wrapper
- [ ] All cards use `content-card` class
- [ ] Spacing follows 8pt grid system

#### **Responsive Design**
- [ ] Navigation collapses properly on mobile
- [ ] Page headers stack vertically on mobile
- [ ] Tables scroll horizontally on mobile
- [ ] Quick action grids become single column

---

### **🚀 Quick Testing Commands**

**Test in XAMPP:**
1. Navigate to `http://localhost/dpti-rocket-system/dashboard.php`
2. Check each refactored page
3. Resize browser to test responsive design
4. Test with different user roles (admin, engineer, staff)

**Check for PHP Errors:**
```powershell
cd c:\xampp\htdocs\dpti-rocket-system
php -l views/dashboard.php
php -l views/templates_list_view.php
# ... test each file after changes
```

**Visual Inspection Points:**
- [ ] No broken layouts
- [ ] Consistent spacing everywhere
- [ ] Professional typography (Inter font loading)
- [ ] Proper hover states on interactive elements
- [ ] No UI "clutter" or redundant information

---

### **🔧 Common Issues to Fix**

If you find these patterns, they violate the Golden Rules:

❌ **BAD:**
```php
<!-- User info outside navigation -->
<div class="user-welcome">Welcome, <?php echo $_SESSION['username']; ?></div>

<!-- Navigation cards on sub-pages -->
<div class="nav-cards">
    <a href="dashboard.php">Dashboard</a>
    <a href="templates.php">Templates</a>
</div>

<!-- Duplicate statistics -->
<div class="stats">Total Users: 25</div>
```

✅ **GOOD:**
```php
<!-- Page header pattern -->
<div class="page-header">
    <div class="page-header-content">
        <div class="page-title-section">
            <h1>Templates</h1>
            <p class="page-description">Manage production step templates</p>
        </div>
        <div class="page-actions">
            <a href="template_form.php" class="btn btn-primary">Add New Template</a>
        </div>
    </div>
</div>
```

This systematic approach will transform your entire application into a cohesive, professional interface!
