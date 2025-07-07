<?php
/**
 * Debug Script for Form Submission
 * เพื่อตรวจสอบข้อมูลที่ส่งจาก step_add_view.php
 */

echo "=== FORM SUBMISSION DEBUG ===\n\n";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "📤 POST Data Received:\n";
    echo "==================\n";
    
    foreach ($_POST as $key => $value) {
        echo "$key: ";
        if (is_array($value)) {
            echo json_encode($value);
        } else {
            echo "'$value'";
        }
        echo "\n";
    }
    
    echo "\n📋 Detailed Analysis:\n";
    echo "===================\n";
    
    // Check action
    echo "Action: " . ($_POST['action'] ?? 'NOT SET') . "\n";
    
    // Check rocket_id
    echo "Rocket ID: " . ($_POST['rocket_id'] ?? 'NOT SET') . "\n";
    
    // Check step_name (should be template_id now)
    echo "Step Name (Template ID): " . ($_POST['step_name'] ?? 'NOT SET') . "\n";
    
    // Check JSON data
    echo "JSON Data: " . ($_POST['data_json'] ?? 'NOT SET') . "\n";
    
    if (isset($_POST['data_json']) && !empty($_POST['data_json'])) {
        echo "\n🔍 JSON Data Parsed:\n";
        echo "=================\n";
        $json_data = json_decode($_POST['data_json'], true);
        if ($json_data) {
            foreach ($json_data as $key => $value) {
                echo "  $key: $value\n";
            }
        } else {
            echo "  ❌ JSON Parse Error: " . json_last_error_msg() . "\n";
        }
    }
    
    echo "\n📊 Validation Status:\n";
    echo "==================\n";
    
    // Validate required fields
    $required_fields = ['action', 'rocket_id', 'step_name', 'data_json'];
    foreach ($required_fields as $field) {
        $status = isset($_POST[$field]) && !empty($_POST[$field]) ? '✅' : '❌';
        echo "$status $field\n";
    }
    
} else {
    echo "⚠️ No POST data received. Please submit the form first.\n";
    echo "\n📝 Testing Instructions:\n";
    echo "1. Go to step_add_view.php\n";
    echo "2. Change form action to: debug/debug_form_submission.php\n";
    echo "3. Submit the form\n";
    echo "4. Check the output here\n";
}

echo "\n=== DEBUG COMPLETE ===\n";
?>
