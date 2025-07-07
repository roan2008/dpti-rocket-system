<?php
require_once 'includes/template_functions.php';

echo "\n🧪 QUICK SYSTEM VERIFICATION\n";
echo "========================================\n";

// Test 1: Valid JSON array
$valid = '["Pass", "Fail", "Needs Review"]';
$result = validateSelectFieldOptions($valid);
echo "✅ Valid JSON: " . ($result['is_valid'] ? 'PASS' : 'FAIL') . "\n";

// Test 2: Invalid JSON object
$invalid = '{"pass": "yes", "fail": "no"}';
$result = validateSelectFieldOptions($invalid);
echo "✅ Invalid JSON Object: " . (!$result['is_valid'] ? 'PASS' : 'FAIL') . "\n";

// Test 3: Database connection
try {
    $pdo = new PDO('mysql:host=localhost;dbname=dpti_rocket', 'root', '');
    echo "✅ Database Connection: PASS\n";
} catch (Exception $e) {
    echo "❌ Database Connection: FAIL\n";
}

// Test 4: Check if template_form_view.php exists
if (file_exists('template_form_view.php')) {
    echo "✅ Template Form View: PASS\n";
} else {
    echo "❌ Template Form View: FAIL\n";
}

echo "\n🎉 System verification complete!\n";
?>
