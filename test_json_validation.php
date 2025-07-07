<?php
require_once 'includes/template_functions.php';

echo "Testing validateSelectFieldOptions function:\n\n";

// Test cases
$test_cases = [
    '["Option 1", "Option 2"]' => 'Valid array',
    '{"option1": "value1"}' => 'JSON object (should fail)',
    '[]' => 'Empty array (should fail)',
    '["Valid", "", "test"]' => 'Array with empty string (should fail)'
];

foreach ($test_cases as $json => $description) {
    echo "Testing: $description\n";
    echo "JSON: $json\n";
    
    $result = validateSelectFieldOptions($json);
    echo "Valid: " . ($result['valid'] ? 'Yes' : 'No') . "\n";
    
    if (!$result['valid']) {
        echo "Errors: " . implode(', ', $result['errors']) . "\n";
    }
    
    echo str_repeat("-", 40) . "\n";
}
?>
