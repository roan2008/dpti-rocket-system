# 🛠️ Technical Guide - DPTI Rocket System

**คู่มือเทคนิคสำหรับ Developer**

---

## 🏗️ **System Architecture**

### **Separation of Concerns Pattern**
```
📁 views/          ← HTML + Simple PHP (แสดงผลเท่านั้น)
📁 controllers/    ← HTTP Request Handling + Business Logic
📁 includes/       ← Database Functions + Shared Components
```

### **Security First Approach**
- **SQL Injection:** PDO Prepared Statements เท่านั้น
- **Password:** `password_hash()` + `password_verify()`
- **XSS:** `htmlspecialchars()` สำหรับ output ทั้งหมด
- **Sessions:** Proper session management ทุกหน้า

---

## 📋 **Coding Standards**

### **PHP Standards**
```php
// Variables & Functions: snake_case
$user_data = get_rocket_by_id($rocket_id);

// Classes: PascalCase  
class RocketManager {
    
// Constants: UPPER_SNAKE_CASE
const MAX_SERIAL_LENGTH = 50;

// File names: snake_case.php
user_functions.php, rocket_controller.php
```

### **File Size Limits**
- **Soft Limit:** 300 lines per file
- **Hard Limit:** 600 lines per file
- **Solution:** แยกไฟล์ใหญ่เป็นส่วนเล็กๆ

---

## 🗄️ **Database Operations**

### **Standard Pattern**
```php
// 1. เตรียม PDO connection
$pdo = get_db_connection();

// 2. เขียน SQL query พร้อม placeholders
$sql = "INSERT INTO rockets (serial_number, project_name) VALUES (?, ?)";

// 3. เตรียม statement
$stmt = $pdo->prepare($sql);

// 4. Execute พร้อม parameters
$result = $stmt->execute([$serial_number, $project_name]);

// 5. จัดการ errors
if (!$result) {
    error_log("Database error: " . implode(" ", $stmt->errorInfo()));
    return false;
}
```

### **Transaction Pattern**
```php
try {
    $pdo->beginTransaction();
    
    // Multiple operations
    $stmt1 = $pdo->prepare("INSERT INTO rockets...");
    $stmt1->execute([...]);
    
    $stmt2 = $pdo->prepare("INSERT INTO production_steps...");
    $stmt2->execute([...]);
    
    $pdo->commit();
    return true;
    
} catch (Exception $e) {
    $pdo->rollback();
    error_log("Transaction failed: " . $e->getMessage());
    return false;
}
```

---

## 🎯 **Controller Pattern**

### **Standard Controller Structure**
```php
// controllers/rocket_controller.php
<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/rocket_functions.php';

// Authentication check
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// Action handling
$action = $_POST['action'] ?? '';

switch ($action) {
    case 'add_rocket':
        handle_add_rocket();
        break;
    case 'edit_rocket':
        handle_edit_rocket();
        break;
    default:
        header("Location: ../dashboard.php");
        exit();
}

function handle_add_rocket() {
    // 1. Validate inputs
    // 2. Check permissions
    // 3. Call function
    // 4. Handle result
    // 5. Redirect with message
}
```

---

## 🔒 **Security Patterns**

### **Input Validation**
```php
// Required field validation
if (empty($serial_number)) {
    $errors[] = "Serial number is required";
}

// Format validation
if (!preg_match('/^[A-Za-z0-9\-]+$/', $serial_number)) {
    $errors[] = "Invalid serial number format";
}

// Length validation
if (strlen($project_name) > 255) {
    $errors[] = "Project name too long";
}
```

### **Role-based Access**
```php
// Function-level protection
function delete_rocket($rocket_id, $user_role) {
    if ($user_role !== 'admin') {
        return ['success' => false, 'error' => 'Permission denied'];
    }
    // Continue with deletion...
}

// View-level protection
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'engineer') {
    header("Location: ../dashboard.php?error=permission_denied");
    exit();
}
```

---

## 🧪 **Testing Standards**

### **Three-Tier Testing**
1. **Developer Testing:** `var_dump()` + database inspection
2. **Feature Testing:** Automated test scripts
3. **User Acceptance:** Manual role-based testing

### **Test Script Template**
```php
// tests/test_feature.php
<?php
require_once '../includes/db_connect.php';
require_once '../includes/feature_functions.php';

echo "Testing Feature Functions...\n";

// Test 1: Success case
echo "Test 1: Valid input... ";
$result = feature_function('valid_input');
echo $result ? "PASS\n" : "FAIL\n";

// Test 2: Error case
echo "Test 2: Invalid input... ";
$result = feature_function('');
echo !$result ? "PASS\n" : "FAIL\n";

// Cleanup
cleanup_test_data();
echo "All tests completed.\n";
```

---

## 📁 **File Organization**

### **includes/ Directory**
```
db_connect.php         ← Database connection only
user_functions.php     ← User authentication functions
rocket_functions.php   ← Rocket CRUD operations
production_functions.php ← Production step operations
```

### **views/ Directory**
```
login_view.php         ← Login form
rocket_add_view.php    ← Add rocket form
rocket_detail_view.php ← View/edit rocket details
step_add_view.php      ← Add production step form
```

### **controllers/ Directory**
```
login_controller.php      ← Handle login/logout
rocket_controller.php     ← Handle rocket operations
production_controller.php ← Handle production steps
```

---

## 🚀 **Development Workflow**

### **Adding New Feature**
1. **Plan Database Changes** (if needed)
   - Update `docs/database_schema.sql`
   - Run schema changes on dev database

2. **Create/Update Functions** (`includes/`)
   - Write database functions first
   - Include proper error handling
   - Follow security patterns

3. **Create Controller** (`controllers/`)
   - Handle HTTP requests
   - Validate inputs
   - Call appropriate functions
   - Manage redirects/responses

4. **Create Views** (`views/`)
   - HTML structure with PHP for display logic only
   - Include authentication checks
   - Use consistent styling

5. **Testing**
   - Create test script in `tests/`
   - Test both success and failure cases
   - Manual browser testing

6. **Documentation**
   - Update relevant documentation
   - Add comments to complex code

---

## 🔧 **Performance Guidelines**

### **Database Optimization**
- Use appropriate indexes
- Limit query results with LIMIT
- Use JOINs instead of multiple queries
- Cache frequently accessed data

### **PHP Optimization**
- Minimize file includes
- Use isset() instead of array_key_exists()
- Unset large variables when done
- Use require_once for includes

---

## 🐛 **Debugging Tips**

### **Common Debug Points**
```php
// Database queries
var_dump($sql, $params);
var_dump($stmt->errorInfo());

// Session data
var_dump($_SESSION);

// POST data
var_dump($_POST);

// Database results
var_dump($result, $pdo->lastInsertId());
```

### **Error Logging**
```php
// Log errors (never show to users)
error_log("Function error: " . $error_message);

// Show generic message to users
return ['success' => false, 'error' => 'An error occurred. Please try again.'];
```

---

## 📦 **Deployment Checklist**

### **Before Production**
- [ ] Remove all `var_dump()` and `print_r()` statements
- [ ] Set proper error reporting (no display to users)
- [ ] Validate all security measures
- [ ] Test with production data volume
- [ ] Set up proper backup procedures
- [ ] Configure SSL certificates
- [ ] Set strong database passwords

---

## 🔄 **Version Control**

### **Git Commit Standards**
```
feat: Add production step tracking
fix: Resolve login session timeout
docs: Update technical documentation
style: Improve CSS for mobile devices
refactor: Reorganize rocket functions
test: Add comprehensive testing suite
```

---

**Last Updated:** June 30, 2025  
**For Issues:** Check test scripts first, then review error logs
