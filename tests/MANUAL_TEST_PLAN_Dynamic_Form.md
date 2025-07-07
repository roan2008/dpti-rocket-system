# 🧪 Dynamic Template Form Builder - Manual Test Plan

## 🎯 **Test Objectives**
Verify that the Dynamic Template Form Builder works correctly with all features including field management, form validation, and database integration.

---

## 📋 **Pre-Test Setup**

### **1. Environment Check**
- ✅ XAMPP running (Apache + MySQL)
- ✅ Browser with Developer Tools available
- ✅ Database contains sample data

### **2. Test User Credentials**
- **Admin**: `admin` / `admin123` (Full access)
- **Engineer**: `engineer` / `engineer123` (Create/Edit access)
- **Staff**: `staff` / `staff123` (No access - should be denied)

### **3. Test URLs**
- **Template List**: http://localhost/dpti-rocket-system/views/templates_list_view.php
- **Template Form**: http://localhost/dpti-rocket-system/views/template_form_view.php
- **Login**: http://localhost/dpti-rocket-system/views/login_view.php

---

## 🔒 **Phase 1: Access Control Testing**

### **Test 1.1: Staff User Access (Expected: DENIED)**
**Steps:**
1. Login as `staff` / `staff123`
2. Navigate directly to: `template_form_view.php`
3. **Expected Result**: ❌ Should redirect to dashboard with "insufficient_permissions"

### **Test 1.2: Engineer User Access (Expected: ALLOWED)**
**Steps:**
1. Login as `engineer` / `engineer123`
2. Navigate to template list
3. Click "Add New Template" button
4. **Expected Result**: ✅ Should load the form successfully

### **Test 1.3: Admin User Access (Expected: ALLOWED)**
**Steps:**
1. Login as `admin` / `admin123`
2. Access form via direct URL and via "Add New Template" button
3. **Expected Result**: ✅ Full access to all form features

---

## 🎨 **Phase 2: Dynamic Field Builder Testing**

### **Test 2.1: Initial Form State**
**Verification Points:**
- [ ] Form loads with empty template information section
- [ ] Fields container shows "No form fields added yet" message
- [ ] "Add Field" button is visible and clickable
- [ ] Form has proper styling and layout

### **Test 2.2: Add Field Functionality**
**Steps:**
1. Click "Add Field" button
2. **Expected Results:**
   - [ ] New field row appears with Field #1
   - [ ] Row contains: Label, Name, Type dropdown, Required checkbox
   - [ ] Options textarea is initially hidden
   - [ ] Remove button is present
   - [ ] Empty message disappears

**Test 2.3: Multiple Field Management**
**Steps:**
1. Add 3 fields using "Add Field" button
2. **Expected Results:**
   - [ ] Fields are numbered 1, 2, 3
   - [ ] Each field has unique form inputs
   - [ ] All fields display correctly without overlap

### **Test 2.4: Remove Field Functionality**
**Steps:**
1. Add 3 fields
2. Remove the middle field (Field #2)
3. **Expected Results:**
   - [ ] Field #2 is removed
   - [ ] Remaining fields are renumbered (1, 2)
   - [ ] No gaps in numbering
   - [ ] Form still functions correctly

### **Test 2.5: Remove All Fields**
**Steps:**
1. Add 2 fields, then remove both
2. **Expected Results:**
   - [ ] Empty message reappears
   - [ ] Container returns to initial state
   - [ ] "Add Field" button still works

---

## 🔧 **Phase 3: Field Type Conditional Logic Testing**

### **Test 3.1: Options Field Visibility**
**Steps:**
1. Add a new field
2. Set Field Type to "Text Input"
3. **Expected**: Options textarea remains hidden
4. Change Field Type to "Dropdown Select"
5. **Expected**: Options textarea becomes visible and required
6. Change back to "Number Input"
7. **Expected**: Options textarea hides again and becomes non-required

### **Test 3.2: Options Field for Multiple Fields**
**Steps:**
1. Add 3 fields
2. Set Field #1 to "Text", Field #2 to "Select", Field #3 to "Date"
3. **Expected Results:**
   - [ ] Only Field #2 shows options textarea
   - [ ] Field #1 and #3 have hidden options
   - [ ] Each field's visibility is independent

---

## ⌨️ **Phase 4: Auto-Sanitization Testing**

### **Test 4.1: Field Name Auto-Generation**
**Steps:**
1. Add a field
2. Type in Field Label: "Product Weight (grams)"
3. **Expected**: Field Name automatically becomes "product_weight_grams"
4. Clear Field Label and type: "Test@#$%Field!"
5. **Expected**: Field Name becomes "test_field"

### **Test 4.2: Manual Field Name Editing**
**Steps:**
1. Add a field
2. Type Field Label: "Test Label"
3. Manually edit Field Name to: "custom_field_name"
4. Change Field Label to: "New Label"
5. **Expected**: Field Name stays "custom_field_name" (manual override)

### **Test 4.3: Field Name Validation Patterns**
**Test these Field Name inputs:**
- `123invalid` → Should become `_123invalid` or be rejected
- `valid_field` → Should remain `valid_field`
- `Mixed-Case Field` → Should become `mixed_case_field`
- `field__with___multiple___underscores` → Should become `field_with_multiple_underscores`

---

## 🔍 **Phase 5: Developer Tools Inspection**

### **Test 5.1: Hidden Input JSON Inspection**
**Steps:**
1. Add 2 fields with these specifications:
   - **Field 1**: Label="Weight", Name="weight_kg", Type="Number", Required=Yes
   - **Field 2**: Label="Status", Name="status", Type="Select", Required=Yes, Options=`["Pass", "Fail"]`
2. Open browser Developer Tools (F12)
3. Click Submit (but don't complete - form should be intercepted)
4. In Console, look for "Fields data:" log message
5. **Expected JSON structure**:
```json
[
  {
    "field_label": "Weight",
    "field_name": "weight_kg", 
    "field_type": "number",
    "is_required": true,
    "display_order": 1,
    "options_json": null
  },
  {
    "field_label": "Status",
    "field_name": "status",
    "field_type": "select", 
    "is_required": true,
    "display_order": 2,
    "options_json": "[\"Pass\", \"Fail\"]"
  }
]
```

### **Test 5.2: Hidden Input Field Value**
**Steps:**
1. After creating fields above, in DevTools Elements tab
2. Find `<input type="hidden" name="fields_data" id="fields-data-input">`
3. **Expected**: Value attribute contains the JSON string from above

---

## ✅ **Phase 6: Happy Path Testing**

### **Test 6.1: Complete Template Creation**
**Steps:**
1. Login as `engineer`
2. Click "Add New Template"
3. Fill Template Information:
   - **Step Name**: "Quality Control Test"
   - **Step Description**: "Comprehensive quality control inspection"
4. Add 3 fields:
   - **Field 1**: Label="Length (mm)", Name="length_mm", Type="Number", Required=Yes
   - **Field 2**: Label="Width (mm)", Name="width_mm", Type="Number", Required=Yes  
   - **Field 3**: Label="Overall Result", Name="result", Type="Select", Required=Yes, Options=`["Pass", "Fail", "Needs Review"]`
5. Click "Create Template"
6. **Expected Results:**
   - [ ] Redirected to template list with success message
   - [ ] New template appears in the list
   - [ ] Template shows correct name and description

### **Test 6.2: Database Verification**
**After successful creation, check database:**

**Step templates table:**
```sql
SELECT * FROM step_templates WHERE step_name = 'Quality Control Test';
```
**Expected**: 1 row with correct data

**Template fields table:**
```sql
SELECT * FROM template_fields WHERE template_id = [new_template_id] ORDER BY display_order;
```
**Expected**: 3 rows with correct field data

### **Test 6.3: Edit Mode Testing**
**Steps:**
1. From template list, click "Edit" on the template just created
2. **Expected Results:**
   - [ ] Form loads with existing data
   - [ ] Template name and description are pre-filled
   - [ ] All 3 fields are loaded correctly
   - [ ] Options for select field are displayed
   - [ ] Form title shows "Edit Template"
3. Modify the template:
   - Change step name to "Quality Control Test - Updated"
   - Add a 4th field: Label="Notes", Name="notes", Type="Textarea", Required=No
4. Click "Update Template"
5. **Expected**: Changes are saved and reflected in the list

---

## ❌ **Phase 7: Error Handling Testing**

### **Test 7.1: Missing Required Data**
**Test Case 7.1a: Empty Template Name**
1. Click "Add New Template"
2. Leave Step Name empty
3. Add one field with valid data
4. Click "Create Template"
5. **Expected**: Form shows "Please fill in all required fields" error

**Test Case 7.1b: Missing Field Data**
1. Fill template information correctly
2. Add a field but leave Field Label empty
3. Click "Create Template"
4. **Expected**: JavaScript alert: "Field #1: Please fill in all required fields"

**Test Case 7.1c: Select Field Without Options**
1. Add a field with Type="Dropdown Select"
2. Leave Options textarea empty
3. **Expected**: JavaScript alert about missing options

### **Test 7.2: Invalid JSON Options**
**Steps:**
1. Add a field with Type="Dropdown Select"
2. Enter invalid JSON in options: `["Option 1", "Option 2"` (missing closing bracket)
3. Click "Create Template"
4. **Expected**: JavaScript alert about invalid JSON format

### **Test 7.3: Duplicate Template Name**
**Steps:**
1. Try to create a template with name "Quality Control Inspection" (already exists)
2. **Expected**: Error message "A template with this name already exists"

### **Test 7.4: Network/Database Errors**
**Simulation Steps:**
1. Stop MySQL service temporarily
2. Try to create a template
3. **Expected**: "Failed to save template" error
4. Restart MySQL and verify system recovers

---

## 📊 **Phase 8: Performance & Usability Testing**

### **Test 8.1: Large Form Testing**
**Steps:**
1. Add 10+ fields to test performance
2. **Expected Results:**
   - [ ] Form remains responsive
   - [ ] JavaScript execution is smooth
   - [ ] Scrolling works properly
   - [ ] All fields submit correctly

### **Test 8.2: Mobile Responsiveness**
**Steps:**
1. Open form on mobile device or use DevTools mobile simulation
2. **Expected Results:**
   - [ ] Form is usable on small screens
   - [ ] Buttons are touch-friendly
   - [ ] Text inputs are properly sized
   - [ ] Form submission works on mobile

---

## 📋 **Test Execution Checklist**

### **Pre-Test:**
- [ ] Environment setup complete
- [ ] Test credentials verified
- [ ] Browser DevTools ready

### **Access Control:**
- [ ] Test 1.1: Staff denied
- [ ] Test 1.2: Engineer allowed
- [ ] Test 1.3: Admin allowed

### **Dynamic Field Builder:**
- [ ] Test 2.1: Initial state
- [ ] Test 2.2: Add field
- [ ] Test 2.3: Multiple fields
- [ ] Test 2.4: Remove field
- [ ] Test 2.5: Remove all fields

### **Conditional Logic:**
- [ ] Test 3.1: Options visibility
- [ ] Test 3.2: Multiple field independence

### **Auto-Sanitization:**
- [ ] Test 4.1: Auto-generation
- [ ] Test 4.2: Manual override
- [ ] Test 4.3: Validation patterns

### **Developer Inspection:**
- [ ] Test 5.1: JSON structure
- [ ] Test 5.2: Hidden input value

### **Happy Path:**
- [ ] Test 6.1: Complete creation
- [ ] Test 6.2: Database verification
- [ ] Test 6.3: Edit mode

### **Error Handling:**
- [ ] Test 7.1: Missing data
- [ ] Test 7.2: Invalid JSON
- [ ] Test 7.3: Duplicate name
- [ ] Test 7.4: Network errors

### **Performance:**
- [ ] Test 8.1: Large forms
- [ ] Test 8.2: Mobile responsive

---

## 🎉 **Success Criteria**

✅ **All tests must pass for the feature to be considered complete:**

1. **Access Control**: Only admin/engineer can access
2. **Dynamic Functionality**: Add/remove fields works smoothly
3. **Conditional Logic**: Options field shows/hides correctly
4. **Data Integrity**: JSON structure is correct and complete
5. **Database Integration**: Data saves and loads correctly
6. **Error Handling**: Graceful handling of all error scenarios
7. **User Experience**: Intuitive and responsive interface

---

**📅 Test Date**: July 1, 2025  
**⏱️ Estimated Duration**: 2-3 hours for complete test suite  
**🎯 Status**: Ready for execution
