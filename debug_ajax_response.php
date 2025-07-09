<?php
$template_id = 11; // Test with existing template
$response = file_get_contents("http://localhost/dpti-rocket-system/controllers/template_ajax.php?template_id={$template_id}");

echo "=== RESPONSE FROM template_ajax.php for template_id={$template_id} ===\n";
echo $response;
echo "\n\n=== DECODED JSON ===\n";

$data = json_decode($response, true);
if ($data) {
    print_r($data);
} else {
    echo "Error decoding JSON: " . json_last_error_msg();
}
?>
