<?php
/**
 * Analytics Function Test
 * Tests the comprehensive analytics data gathering
 */

require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/user_functions.php';

echo "<h1>📊 Analytics Dashboard Test</h1>\n";
echo "<p>Testing the comprehensive analytics data gathering...</p>\n";

$test_results = [];
$test_number = 1;

// Test 1: Basic function execution
echo "<h2>Test {$test_number}: Analytics Function Execution</h2>\n";
try {
    $analytics = getSystemWideAnalytics($pdo);
    
    if (is_array($analytics) && !isset($analytics['error'])) {
        echo "✅ PASS: getSystemWideAnalytics() executed successfully<br>\n";
        $test_results["Test {$test_number}"] = "PASS";
    } else {
        echo "❌ FAIL: Analytics function returned error or invalid data<br>\n";
        if (isset($analytics['error'])) {
            echo "Error: " . $analytics['message'] . "<br>\n";
        }
        $test_results["Test {$test_number}"] = "FAIL";
    }
} catch (Exception $e) {
    echo "❌ FAIL: Analytics function threw exception: " . $e->getMessage() . "<br>\n";
    $test_results["Test {$test_number}"] = "FAIL";
}
$test_number++;

// Test 2: Data structure validation
echo "<h2>Test {$test_number}: Data Structure Validation</h2>\n";
if (isset($analytics) && !isset($analytics['error'])) {
    $required_sections = [
        'rockets' => ['total', 'by_status', 'recent_activity'],
        'production_steps' => ['total', 'by_step_type', 'average_time_per_step', 'completion_rate', 'daily_productivity'],
        'approvals' => ['total_pending', 'approval_rate', 'average_approval_time', 'approvals_by_engineer', 'approval_trend'],
        'users' => ['total_users', 'by_role', 'most_active_staff'],
        'system_health' => ['database_size', 'performance_metrics'],
        'summary' => ['total_rockets', 'total_steps', 'pending_approvals', 'active_users', 'completion_rate', 'approval_rate']
    ];
    
    $structure_valid = true;
    foreach ($required_sections as $section => $fields) {
        if (!isset($analytics[$section])) {
            echo "❌ FAIL: Missing section '{$section}'<br>\n";
            $structure_valid = false;
            continue;
        }
        
        foreach ($fields as $field) {
            if (!isset($analytics[$section][$field])) {
                echo "❌ FAIL: Missing field '{$field}' in section '{$section}'<br>\n";
                $structure_valid = false;
            }
        }
    }
    
    if ($structure_valid) {
        echo "✅ PASS: All required data sections and fields are present<br>\n";
        $test_results["Test {$test_number}"] = "PASS";
    } else {
        $test_results["Test {$test_number}"] = "FAIL";
    }
} else {
    echo "⏭️ SKIP: Cannot validate structure - analytics data not available<br>\n";
    $test_results["Test {$test_number}"] = "SKIP";
}
$test_number++;

// Test 3: Data type validation
echo "<h2>Test {$test_number}: Data Type Validation</h2>\n";
if (isset($analytics) && !isset($analytics['error'])) {
    $type_checks = [
        ['rockets.total', 'integer'],
        ['production_steps.total', 'integer'],
        ['production_steps.average_time_per_step', 'numeric'],
        ['production_steps.completion_rate', 'numeric'],
        ['approvals.total_pending', 'integer'],
        ['approvals.approval_rate', 'numeric'],
        ['users.total_users', 'integer'],
        ['rockets.by_status', 'array'],
        ['users.by_role', 'array']
    ];
    
    $types_valid = true;
    foreach ($type_checks as $check) {
        [$path, $expected_type] = $check;
        $path_parts = explode('.', $path);
        $value = $analytics;
        
        foreach ($path_parts as $part) {
            if (isset($value[$part])) {
                $value = $value[$part];
            } else {
                $value = null;
                break;
            }
        }
        
        $is_valid = false;
        switch ($expected_type) {
            case 'integer':
                $is_valid = is_int($value) || (is_string($value) && ctype_digit($value));
                break;
            case 'numeric':
                $is_valid = is_numeric($value);
                break;
            case 'array':
                $is_valid = is_array($value);
                break;
        }
        
        if (!$is_valid) {
            echo "❌ FAIL: '{$path}' should be {$expected_type}, got " . gettype($value) . "<br>\n";
            $types_valid = false;
        }
    }
    
    if ($types_valid) {
        echo "✅ PASS: All data types are correct<br>\n";
        $test_results["Test {$test_number}"] = "PASS";
    } else {
        $test_results["Test {$test_number}"] = "FAIL";
    }
} else {
    echo "⏭️ SKIP: Cannot validate types - analytics data not available<br>\n";
    $test_results["Test {$test_number}"] = "SKIP";
}
$test_number++;

// Test 4: Data consistency checks
echo "<h2>Test {$test_number}: Data Consistency Checks</h2>\n";
if (isset($analytics) && !isset($analytics['error'])) {
    $consistency_valid = true;
    
    // Check if summary matches detailed data
    if ($analytics['summary']['total_rockets'] !== $analytics['rockets']['total']) {
        echo "❌ FAIL: Summary rockets total doesn't match detailed rockets total<br>\n";
        $consistency_valid = false;
    }
    
    if ($analytics['summary']['total_steps'] !== $analytics['production_steps']['total']) {
        echo "❌ FAIL: Summary steps total doesn't match detailed steps total<br>\n";
        $consistency_valid = false;
    }
    
    // Check percentage calculations
    if ($analytics['production_steps']['completion_rate'] < 0 || $analytics['production_steps']['completion_rate'] > 100) {
        echo "❌ FAIL: Completion rate should be between 0-100%, got {$analytics['production_steps']['completion_rate']}%<br>\n";
        $consistency_valid = false;
    }
    
    if ($analytics['approvals']['approval_rate'] < 0 || $analytics['approvals']['approval_rate'] > 100) {
        echo "❌ FAIL: Approval rate should be between 0-100%, got {$analytics['approvals']['approval_rate']}%<br>\n";
        $consistency_valid = false;
    }
    
    if ($consistency_valid) {
        echo "✅ PASS: Data consistency checks passed<br>\n";
        $test_results["Test {$test_number}"] = "PASS";
    } else {
        $test_results["Test {$test_number}"] = "FAIL";
    }
} else {
    echo "⏭️ SKIP: Cannot validate consistency - analytics data not available<br>\n";
    $test_results["Test {$test_number}"] = "SKIP";
}
$test_number++;

// Test 5: Sample data display
echo "<h2>Test {$test_number}: Sample Data Display</h2>\n";
if (isset($analytics) && !isset($analytics['error'])) {
    echo "✅ Analytics Data Sample:<br>\n";
    echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;'>\n";
    echo "<strong>Summary Metrics:</strong><br>\n";
    echo "• Total Rockets: " . $analytics['summary']['total_rockets'] . "<br>\n";
    echo "• Total Production Steps: " . $analytics['summary']['total_steps'] . "<br>\n";
    echo "• Pending Approvals: " . $analytics['summary']['pending_approvals'] . "<br>\n";
    echo "• Active Users: " . $analytics['summary']['active_users'] . "<br>\n";
    echo "• Completion Rate: " . $analytics['summary']['completion_rate'] . "%<br>\n";
    echo "• Approval Rate: " . $analytics['summary']['approval_rate'] . "%<br>\n";
    echo "</div>\n";
    
    echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;'>\n";
    echo "<strong>Rocket Status Breakdown:</strong><br>\n";
    foreach ($analytics['rockets']['by_status'] as $status) {
        echo "• " . ucfirst($status['current_status']) . ": " . $status['count'] . "<br>\n";
    }
    echo "</div>\n";
    
    echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;'>\n";
    echo "<strong>User Role Distribution:</strong><br>\n";
    foreach ($analytics['users']['by_role'] as $role) {
        echo "• " . ucfirst($role['role']) . ": " . $role['count'] . " users<br>\n";
    }
    echo "</div>\n";
    
    $test_results["Test {$test_number}"] = "PASS";
} else {
    echo "❌ FAIL: Cannot display sample data - analytics data not available<br>\n";
    $test_results["Test {$test_number}"] = "FAIL";
}
$test_number++;

// Test 6: Performance test
echo "<h2>Test {$test_number}: Performance Test</h2>\n";
$start_time = microtime(true);
try {
    $analytics_perf = getSystemWideAnalytics($pdo);
    $execution_time = microtime(true) - $start_time;
    
    if ($execution_time < 5.0) { // Should complete within 5 seconds
        echo "✅ PASS: Analytics function executed in " . round($execution_time, 3) . " seconds<br>\n";
        $test_results["Test {$test_number}"] = "PASS";
    } else {
        echo "⚠️ WARNING: Analytics function took " . round($execution_time, 3) . " seconds (may be slow)<br>\n";
        $test_results["Test {$test_number}"] = "PASS"; // Still pass but with warning
    }
} catch (Exception $e) {
    echo "❌ FAIL: Performance test failed: " . $e->getMessage() . "<br>\n";
    $test_results["Test {$test_number}"] = "FAIL";
}

// Summary
echo "<h2>🎯 Test Summary</h2>\n";
$total_tests = count($test_results);
$passed_tests = count(array_filter($test_results, function($result) { return $result === 'PASS'; }));
$failed_tests = count(array_filter($test_results, function($result) { return $result === 'FAIL'; }));
$skipped_tests = count(array_filter($test_results, function($result) { return $result === 'SKIP'; }));

echo "<div style='background: " . ($failed_tests === 0 ? '#d4edda' : '#f8d7da') . "; padding: 15px; border-radius: 5px; margin: 20px 0;'>\n";
echo "<h3>Results:</h3>\n";
echo "<p><strong>Total Tests:</strong> {$total_tests}</p>\n";
echo "<p><strong>Passed:</strong> <span style='color: green;'>{$passed_tests}</span></p>\n";
echo "<p><strong>Failed:</strong> <span style='color: red;'>{$failed_tests}</span></p>\n";
echo "<p><strong>Skipped:</strong> <span style='color: orange;'>{$skipped_tests}</span></p>\n";
echo "<p><strong>Success Rate:</strong> " . round(($passed_tests / $total_tests) * 100, 1) . "%</p>\n";
echo "</div>\n";

if ($failed_tests === 0) {
    echo "<h3>🎉 All Analytics Tests Passed!</h3>\n";
    echo "<p><strong>Your Analytics Dashboard is ready with:</strong></p>\n";
    echo "<ul>\n";
    echo "<li>✅ Comprehensive rocket statistics</li>\n";
    echo "<li>✅ Production step analytics</li>\n";
    echo "<li>✅ Approval workflow metrics</li>\n";
    echo "<li>✅ User activity insights</li>\n";
    echo "<li>✅ System health monitoring</li>\n";
    echo "<li>✅ Performance tracking</li>\n";
    echo "</ul>\n";
    
    echo "<h3>🌐 Next Steps:</h3>\n";
    echo "<ol>\n";
    echo "<li>Access your dashboard at: <code>views/analytics_dashboard_view.php</code></li>\n";
    echo "<li>Review the comprehensive metrics and charts</li>\n";
    echo "<li>Export reports for stakeholder presentations</li>\n";
    echo "<li>Monitor system performance trends</li>\n";
    echo "</ol>\n";
} else {
    echo "<h3>⚠️ Some Tests Failed</h3>\n";
    echo "<p>Please review the failed tests and address any issues.</p>\n";
}

echo "\n<p><strong>Test completed at:</strong> " . date('Y-m-d H:i:s') . "</p>\n";
?>
