<?php
/**
 * Debug Script for Combobox Field Issue in Add Field Functionality
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== COMBOBOX FIELD DEBUG SCRIPT ===\n\n";

echo "TEST 1: Locate Add Field Functionality\n";
echo "======================================\n";

$project_root = dirname(__DIR__);
echo "Project root: $project_root\n\n";

// Search for files containing add-field-btn
echo "Searching for files with 'add-field-btn':\n";

$search_patterns = [
    'add-field-btn',
    'Add Field',
    'combobox',
    'field_type'
];

$search_dirs = [
    'views',
    'assets/js', 
    'controllers'
];

foreach ($search_dirs as $dir) {
    $dir_path = $project_root . '/' . $dir;
    if (is_dir($dir_path)) {
        echo "\nSearching in $dir/:\n";
        
        $files = glob($dir_path . '/*.*');
        foreach ($files as $file) {
            $content = file_get_contents($file);
            
            foreach ($search_patterns as $pattern) {
                if (stripos($content, $pattern) !== false) {
                    echo "  ✅ Found '$pattern' in: " . basename($file) . "\n";
                }
            }
        }
    }
}

echo "\nTEST 2: Check Template Management Files\n";
echo "=======================================\n";

// Check likely files for template/field management
$likely_files = [
    'views/templates_list_view.php',
    'views/template_add_view.php', 
    'views/template_edit_view.php',
    'views/template_form_view.php',
    'assets/js/template_management.js',
    'controllers/template_controller.php'
];

foreach ($likely_files as $file_path) {
    $full_path = $project_root . '/' . $file_path;
    
    echo "Checking: $file_path\n";
    
    if (file_exists($full_path)) {
        echo "  ✅ File exists\n";
        
        $content = file_get_contents($full_path);
        
        // Check for add field functionality
        if (stripos($content, 'add-field-btn') !== false) {
            echo "  🎯 Contains add-field-btn\n";
        }
        
        // Check for combobox handling
        if (stripos($content, 'combobox') !== false) {
            echo "  📋 Contains combobox logic\n";
        }
        
        // Check for JavaScript field type handling
        if (stripos($content, 'field_type') !== false || stripos($content, 'fieldType') !== false) {
            echo "  🔧 Contains field type logic\n";
        }
        
        // Check for options handling
        if (stripos($content, 'options') !== false && stripos($content, 'field') !== false) {
            echo "  📝 Contains field options logic\n";
        }
        
    } else {
        echo "  ❌ File not found\n";
    }
    echo "\n";
}

echo "TEST 3: Analyze JavaScript Field Type Handling\n";
echo "==============================================\n";

// Look for JavaScript files that handle dynamic field addition
$js_files = glob($project_root . '/assets/js/*.js');

foreach ($js_files as $js_file) {
    echo "Analyzing: " . basename($js_file) . "\n";
    
    $content = file_get_contents($js_file);
    
    // Check for field type change handlers
    if (preg_match_all('/change.*?field.*?type|field.*?type.*?change/i', $content, $matches)) {
        echo "  🔄 Found field type change handlers\n";
    }
    
    // Check for combobox/select specific logic
    if (preg_match_all('/(combobox|select).*?(show|hide|display)/i', $content, $matches)) {
        echo "  📋 Found combobox display logic\n";
    }
    
    // Check for options container handling
    if (preg_match_all('/(options|option).*?(container|div|show|hide)/i', $content, $matches)) {
        echo "  📝 Found options container logic\n";
    }
    
    // Look for potential issues
    if (stripos($content, 'style.display') !== false) {
        echo "  👁️ Uses style.display for show/hide\n";
    }
    
    if (stripos($content, 'addEventListener') !== false) {
        echo "  🎧 Uses event listeners\n";
    }
    
    echo "\n";
}

echo "TEST 4: Common Combobox Issues Analysis\n";
echo "=======================================\n";

echo "Common problems with dynamic combobox fields:\n\n";

echo "1. EVENT LISTENER ISSUE:\n";
echo "   - New fields added dynamically don't have event listeners\n";
echo "   - Need to use event delegation or re-attach listeners\n\n";

echo "2. CSS DISPLAY ISSUE:\n";
echo "   - Options container has display:none by default\n";
echo "   - Field type change doesn't trigger show/hide properly\n\n";

echo "3. HTML STRUCTURE ISSUE:\n";
echo "   - Options container not included in new field HTML\n";
echo "   - Missing options input in dynamically added fields\n\n";

echo "4. JAVASCRIPT SCOPE ISSUE:\n";
echo "   - Event handlers only bound to existing elements\n";
echo "   - Dynamic elements not in scope\n\n";

echo "TEST 5: Generate Debug Solution\n";
echo "===============================\n";

echo "To fix this issue, we need to:\n\n";

echo "STEP 1: Find the JavaScript that handles 'Add Field'\n";
echo "STEP 2: Check if field type change events work on new fields\n";
echo "STEP 3: Ensure options container is included in new field HTML\n";
echo "STEP 4: Fix event delegation for dynamic elements\n\n";

echo "COMMON FIXES:\n";
echo "1. Use document.addEventListener with event delegation\n";
echo "2. Include options container in field template\n";
echo "3. Re-run field type change logic after adding new field\n";
echo "4. Check CSS classes and display properties\n\n";

echo "=== NEXT STEPS ===\n";
echo "1. Find the exact file with add-field-btn\n";
echo "2. Locate the JavaScript that handles field type changes\n";
echo "3. Check if event delegation is used for dynamic elements\n";
echo "4. Verify options container HTML is included in new fields\n";

echo "\n=== DEBUG COMPLETE ===\n";
?>
