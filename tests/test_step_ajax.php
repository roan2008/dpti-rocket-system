<?php
// Test step AJAX endpoint directly
echo "=== TESTING STEP AJAX ENDPOINT ===\n";

try {
    // Test 1: Valid step ID
    echo "1. Testing valid step ID (20):\n";
    $context = stream_context_create([
        'http' => [
            'timeout' => 10,
            'ignore_errors' => true
        ]
    ]);
    
    $response = file_get_contents('http://localhost:8080/controllers/step_ajax.php?action=get_step_data&step_id=20', false, $context);
    if ($response !== false) {
        echo "✅ Response received:\n";
        $data = json_decode($response, true);
        if ($data) {
            if (isset($data['success']) && $data['success']) {
                echo "   ✅ SUCCESS: Step data retrieved\n";
                echo "   📊 Step ID: " . $data['step_info']['step_id'] . "\n";
                echo "   📝 Step Name: " . $data['step_info']['step_name'] . "\n";
                echo "   🚀 Rocket: " . $data['step_info']['rocket_serial'] . "\n";
                echo "   📋 Field Count: " . $data['field_count'] . "\n";
            } else {
                echo "   ❌ ERROR: " . ($data['error'] ?? 'Unknown error') . "\n";
            }
        } else {
            echo "   ❌ Invalid JSON response\n";
            echo "   Raw response: " . substr($response, 0, 200) . "\n";
        }
    } else {
        echo "   ❌ Failed to get response\n";
    }
    
    echo "\n";
    
    // Test 2: Invalid step ID
    echo "2. Testing invalid step ID (999999):\n";
    $response = file_get_contents('http://localhost:8080/controllers/step_ajax.php?action=get_step_data&step_id=999999', false, $context);
    if ($response !== false) {
        $data = json_decode($response, true);
        if ($data && isset($data['error'])) {
            echo "   ✅ Correctly returned error: " . $data['error'] . "\n";
        } else {
            echo "   ❌ Expected error response not received\n";
        }
    } else {
        echo "   ❌ Failed to get response\n";
    }
    
    echo "\n";
    
    // Test 3: Missing step ID
    echo "3. Testing missing step ID:\n";
    $response = file_get_contents('http://localhost:8080/controllers/step_ajax.php?action=get_step_data', false, $context);
    if ($response !== false) {
        $data = json_decode($response, true);
        if ($data && isset($data['error'])) {
            echo "   ✅ Correctly returned error: " . $data['error'] . "\n";
        } else {
            echo "   ❌ Expected error response not received\n";
        }
    } else {
        echo "   ❌ Failed to get response\n";
    }
    
} catch (Exception $e) {
    echo "❌ Test failed with exception: " . $e->getMessage() . "\n";
}

echo "\n=== STEP AJAX TEST COMPLETED ===\n";
?>
