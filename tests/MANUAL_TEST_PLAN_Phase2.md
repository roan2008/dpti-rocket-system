# Phase 2 Template Management UI - Manual Test Plan

## 🎯 **Test Objectives**
Verify that the Template List Page works correctly with proper access control and displays data as expected.

## 🧪 **Test Plan Results**

### **Task 1: Access Control Testing**

#### **Test 1.1: Staff User Access (Expected: DENIED)**
**Steps:**
1. Navigate to: `http://localhost/dpti-rocket-system/views/login_view.php`
2. Login with credentials:
   - Username: `staff`
   - Password: `staff123`
3. Try to access: `http://localhost/dpti-rocket-system/views/templates_list_view.php`

**Expected Result:** ❌ Should be redirected to dashboard with "insufficient_permissions" error
**Status:** ⏳ Ready to test

#### **Test 1.2: Engineer User Access (Expected: ALLOWED)**
**Steps:**
1. Navigate to: `http://localhost/dpti-rocket-system/views/login_view.php`
2. Login with credentials:
   - Username: `engineer`
   - Password: `engineer123`
3. Navigate to: `http://localhost/dpti-rocket-system/views/templates_list_view.php`

**Expected Result:** ✅ Should successfully access the template list page
**Status:** ⏳ Ready to test

#### **Test 1.3: Admin User Access (Expected: ALLOWED)**
**Steps:**
1. Navigate to: `http://localhost/dpti-rocket-system/views/login_view.php`
2. Login with credentials:
   - Username: `admin`
   - Password: `admin123`
3. Navigate to: `http://localhost/dpti-rocket-system/views/templates_list_view.php`

**Expected Result:** ✅ Should successfully access the template list page
**Status:** ⏳ Ready to test

### **Task 2: Data Display Testing**

#### **Test 2.1: Template List Display**
**Verification Points:**
- [ ] Page displays "Step Template Management" header
- [ ] Table shows the 3 sample templates we created:
  - Quality Control Inspection
  - Component Assembly  
  - Safety Check
- [ ] Each template row shows:
  - Template ID
  - Step Name
  - Description (truncated if > 60 chars)
  - Created By (user name)
  - Created Date
  - Status (Active)
- [ ] Template data is correctly retrieved from database

#### **Test 2.2: Template Statistics Section**
**Verification Points:**
- [ ] Shows "3" in Active Templates count
- [ ] Shows field count statistics
- [ ] Statistics cards display properly

### **Task 3: UI Elements Testing**

#### **Test 3.1: Navigation Elements**
**Verification Points:**
- [ ] "Add New Template" button exists in header
- [ ] "Add New Template" button points to: `template_add_view.php`
- [ ] Breadcrumb shows: Dashboard → Template Management
- [ ] User info displays correctly (username and role)

#### **Test 3.2: Action Buttons (Per Template Row)**
**Verification Points:**
- [ ] "View" button exists and points to: `template_view.php?id=[template_id]`
- [ ] "Edit" button exists and points to: `template_edit_view.php?id=[template_id]`
- [ ] "Delete" button exists (admin only)
- [ ] Action buttons are role-appropriate:
  - Staff: No access to page
  - Engineer: View, Edit buttons
  - Admin: View, Edit, Delete buttons

#### **Test 3.3: Delete Functionality (Admin Only)**
**Verification Points:**
- [ ] Delete button triggers confirmation modal
- [ ] Modal shows template name correctly
- [ ] Modal warns about irreversible action
- [ ] Form posts to: `../controllers/template_controller.php` with action=delete

### **Task 4: Error Handling Testing**

#### **Test 4.1: Success Messages**
**Test accessing with URL parameters:**
- [ ] `?success=template_created` shows "Step template created successfully!"
- [ ] `?success=template_updated` shows "Step template updated successfully!"
- [ ] `?success=template_deleted` shows "Step template deleted successfully!"

#### **Test 4.2: Error Messages**
**Test accessing with URL parameters:**
- [ ] `?error=insufficient_permissions` shows appropriate error
- [ ] `?error=template_not_found` shows appropriate error
- [ ] `?error=invalid_template_id` shows appropriate error

### **Task 5: Responsive Design Testing**

#### **Test 5.1: Desktop Display**
**Verification Points:**
- [ ] Table displays properly on desktop screens
- [ ] All columns are visible and well-spaced
- [ ] Action buttons are properly aligned

#### **Test 5.2: Mobile/Tablet Display**
**Verification Points:**
- [ ] Page is responsive on smaller screens
- [ ] Table scrolls horizontally if needed
- [ ] Mobile navigation works properly

## 🔗 **Test URLs**

### **Primary Test URL:**
- Template List: `http://localhost/dpti-rocket-system/views/templates_list_view.php`

### **Supporting URLs:**
- Login: `http://localhost/dpti-rocket-system/views/login_view.php`
- Dashboard: `http://localhost/dpti-rocket-system/dashboard.php`

### **Test URLs with Parameters:**
- Success message: `http://localhost/dpti-rocket-system/views/templates_list_view.php?success=template_created`
- Error message: `http://localhost/dpti-rocket-system/views/templates_list_view.php?error=insufficient_permissions`

## 📊 **Test Data**
Our test database contains:
- **Template ID 11:** Quality Control Inspection (5 fields)
- **Template ID 12:** Component Assembly (0 fields)  
- **Template ID 13:** Safety Check (0 fields)
- **Users:** admin (ID: 3), engineer (ID: 4), staff (ID: 5)

## ✅ **Test Execution Checklist**

### **Pre-Test Setup:**
- [ ] XAMPP running (Apache + MySQL)
- [ ] Database contains sample templates (✅ DONE)
- [ ] All template functions implemented (✅ DONE)
- [ ] Browser ready for testing

### **Test Execution:**
- [ ] Test 1.1: Staff access denied
- [ ] Test 1.2: Engineer access allowed  
- [ ] Test 1.3: Admin access allowed
- [ ] Test 2.1: Data displays correctly
- [ ] Test 2.2: Statistics show properly
- [ ] Test 3.1: Navigation works
- [ ] Test 3.2: Action buttons present
- [ ] Test 3.3: Delete modal works (admin)
- [ ] Test 4.1: Success messages
- [ ] Test 4.2: Error messages
- [ ] Test 5.1: Desktop responsive
- [ ] Test 5.2: Mobile responsive

### **Post-Test:**
- [ ] Document any issues found
- [ ] Note UI/UX improvements needed
- [ ] Verify all critical functionality works

## 📝 **Notes for Testing**
1. **User Passwords:** Check the users table for actual password hashes or create test passwords
2. **Session Management:** May need to clear browser cookies between role tests
3. **Database State:** Sample data should remain consistent during testing
4. **Error Scenarios:** Test both valid and invalid access patterns

---
**Created:** July 1, 2025  
**Status:** Ready for manual execution  
**Expected Duration:** 30-45 minutes for complete test suite
