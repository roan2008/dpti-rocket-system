<?php
/**
 * Automated Browser Test for Template Management
 * Tests access control and functionality
 */

require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/user_functions.php';
require_once __DIR__ . '/../includes/template_functions.php';

echo "🧪 DPTI Template Management - Manual Test Results\n";
echo "=" . str_repeat("=", 60) . "\n\n";

// Test 1: Access Control Testing
echo "📋 TEST 1: ACCESS CONTROL\n";
echo "-" . str_repeat("-", 30) . "\n";

// Test 1.1: Staff User Access (should be denied)
echo "Test 1.1: Staff User Access\n";
session_start();
$_SESSION = []; // Clear session

// Simulate staff login
$login_result = login_user($pdo, 'staff', 'staff123');
if ($login_result) {
    echo "✓ Staff login successful\n";
    
    // Check if has_role works for template access
    if (!has_role('admin') && !has_role('engineer')) {
        echo "✅ PASS: Staff user correctly DENIED access to templates\n";
        echo "   Staff role: " . ($_SESSION['role'] ?? 'none') . "\n";
    } else {
        echo "❌ FAIL: Staff user incorrectly ALLOWED access to templates\n";
    }
} else {
    echo "❌ FAIL: Staff login failed\n";
}

// Clear session
$_SESSION = [];
echo "\n";

// Test 1.2: Engineer User Access (should be allowed)
echo "Test 1.2: Engineer User Access\n";
$login_result = login_user($pdo, 'engineer', 'engineer123');
if ($login_result) {
    echo "✓ Engineer login successful\n";
    
    // Check if has_role works for template access
    if (has_role('admin') || has_role('engineer')) {
        echo "✅ PASS: Engineer user correctly ALLOWED access to templates\n";
        echo "   Engineer role: " . ($_SESSION['role'] ?? 'none') . "\n";
    } else {
        echo "❌ FAIL: Engineer user incorrectly DENIED access to templates\n";
    }
} else {
    echo "❌ FAIL: Engineer login failed\n";
}

// Clear session
$_SESSION = [];
echo "\n";

// Test 1.3: Admin User Access (should be allowed)
echo "Test 1.3: Admin User Access\n";
$login_result = login_user($pdo, 'admin', 'admin123');
if ($login_result) {
    echo "✓ Admin login successful\n";
    
    // Check if has_role works for template access
    if (has_role('admin') || has_role('engineer')) {
        echo "✅ PASS: Admin user correctly ALLOWED access to templates\n";
        echo "   Admin role: " . ($_SESSION['role'] ?? 'none') . "\n";
    } else {
        echo "❌ FAIL: Admin user incorrectly DENIED access to templates\n";
    }
} else {
    echo "❌ FAIL: Admin login failed\n";
}

echo "\n";

// Test 2: Data Display Testing
echo "📊 TEST 2: DATA DISPLAY\n";
echo "-" . str_repeat("-", 30) . "\n";

echo "Test 2.1: Template List Data\n";
$templates = getAllActiveTemplates($pdo);
echo "✓ Retrieved " . count($templates) . " active templates\n";

if (count($templates) >= 3) {
    echo "✅ PASS: Expected template count (3 or more)\n";
    
    $expected_names = ['Quality Control Inspection', 'Component Assembly', 'Safety Check'];
    $found_names = array_column($templates, 'step_name');
    
    $all_found = true;
    foreach ($expected_names as $expected) {
        if (in_array($expected, $found_names)) {
            echo "  ✓ Found template: $expected\n";
        } else {
            echo "  ❌ Missing template: $expected\n";
            $all_found = false;
        }
    }
    
    if ($all_found) {
        echo "✅ PASS: All expected templates found\n";
    } else {
        echo "❌ FAIL: Some expected templates missing\n";
    }
} else {
    echo "❌ FAIL: Insufficient template count (expected 3+, got " . count($templates) . ")\n";
}

echo "\n";

// Test 3: Template Functions Testing
echo "🔧 TEST 3: TEMPLATE FUNCTIONS\n";
echo "-" . str_repeat("-", 30) . "\n";

echo "Test 3.1: getTemplateWithFields() Function\n";
if (!empty($templates)) {
    $first_template = $templates[0];
    $template_with_fields = getTemplateWithFields($pdo, $first_template['template_id']);
    
    if ($template_with_fields !== false) {
        echo "✅ PASS: getTemplateWithFields() returns data\n";
        echo "  Template: {$template_with_fields['step_name']}\n";
        echo "  Fields count: " . count($template_with_fields['fields'] ?? []) . "\n";
        
        if (isset($template_with_fields['fields']) && is_array($template_with_fields['fields'])) {
            echo "✅ PASS: Fields array structure correct\n";
        } else {
            echo "❌ FAIL: Fields array structure incorrect\n";
        }
    } else {
        echo "❌ FAIL: getTemplateWithFields() returns false\n";
    }
} else {
    echo "⚠️  SKIP: No templates available for testing\n";
}

echo "\n";

echo "Test 3.2: Template Name Validation\n";
$name_exists = templateNameExists($pdo, 'Quality Control Inspection');
if ($name_exists) {
    echo "✅ PASS: templateNameExists() correctly identifies existing template\n";
} else {
    echo "❌ FAIL: templateNameExists() fails to identify existing template\n";
}

$name_not_exists = templateNameExists($pdo, 'Non-Existent Template Name');
if (!$name_not_exists) {
    echo "✅ PASS: templateNameExists() correctly identifies non-existing template\n";
} else {
    echo "❌ FAIL: templateNameExists() incorrectly identifies non-existing template as existing\n";
}

echo "\n";

// Test 4: URL Structure Testing
echo "🌐 TEST 4: URL STRUCTURE\n";
echo "-" . str_repeat("-", 30) . "\n";

echo "Test 4.1: Expected URLs and Files\n";
$required_files = [
    __DIR__ . '/../views/templates_list_view.php' => 'Template list view',
    __DIR__ . '/../controllers/template_controller.php' => 'Template controller',
    __DIR__ . '/../includes/template_functions.php' => 'Template functions'
];

foreach ($required_files as $file => $description) {
    if (file_exists($file)) {
        echo "✅ PASS: $description exists (" . basename(dirname($file)) . "/" . basename($file) . ")\n";
    } else {
        echo "❌ FAIL: $description missing (" . basename(dirname($file)) . "/" . basename($file) . ")\n";
    }
}

echo "\n";

// Test 5: Database Schema Testing
echo "🗄️  TEST 5: DATABASE SCHEMA\n";
echo "-" . str_repeat("-", 30) . "\n";

echo "Test 5.1: Required Tables\n";
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'step_templates'");
    if ($stmt->rowCount() > 0) {
        echo "✅ PASS: step_templates table exists\n";
    } else {
        echo "❌ FAIL: step_templates table missing\n";
    }
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'template_fields'");
    if ($stmt->rowCount() > 0) {
        echo "✅ PASS: template_fields table exists\n";
    } else {
        echo "❌ FAIL: template_fields table missing\n";
    }
} catch (Exception $e) {
    echo "❌ FAIL: Database schema check error: " . $e->getMessage() . "\n";
}

echo "\n";

// Final Summary
echo "📊 SUMMARY\n";
echo "=" . str_repeat("=", 60) . "\n";
echo "✅ Template Management System Phase 2 Testing Complete\n";
echo "\n";
echo "🔗 MANUAL BROWSER TESTING URLS:\n";
echo "- Login: http://localhost/dpti-rocket-system/views/login_view.php\n";
echo "- Templates: http://localhost/dpti-rocket-system/views/templates_list_view.php\n";
echo "- Dashboard: http://localhost/dpti-rocket-system/dashboard.php\n";
echo "\n";
echo "🔑 TEST CREDENTIALS:\n";
echo "- Admin: admin / admin123\n";
echo "- Engineer: engineer / engineer123\n";
echo "- Staff: staff / staff123\n";
echo "\n";
echo "📋 NEXT STEPS:\n";
echo "1. Test in browser with different user roles\n";
echo "2. Verify UI elements display correctly\n";
echo "3. Test responsive design on mobile\n";
echo "4. Test error handling scenarios\n";

session_destroy();
?>
