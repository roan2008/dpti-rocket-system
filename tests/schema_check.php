<?php
// Quick database schema check
$pdo = new PDO("mysql:host=localhost;dbname=dpti_rocket_prod", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "<h2>🔍 Database Schema Check</h2>\n";
echo "<h3>production_steps table:</h3>\n";
$stmt = $pdo->query("DESCRIBE production_steps");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1' cellpadding='5'>\n";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>\n";
foreach ($columns as $column) {
    echo "<tr>\n";
    echo "<td>{$column['Field']}</td>\n";
    echo "<td>{$column['Type']}</td>\n";
    echo "<td>{$column['Null']}</td>\n";
    echo "<td>{$column['Key']}</td>\n";
    echo "<td>{$column['Default']}</td>\n";
    echo "<td>{$column['Extra']}</td>\n";
    echo "</tr>\n";
}
echo "</table>\n";

echo "<h3>approvals table:</h3>\n";
$stmt = $pdo->query("DESCRIBE approvals");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1' cellpadding='5'>\n";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>\n";
foreach ($columns as $column) {
    echo "<tr>\n";
    echo "<td>{$column['Field']}</td>\n";
    echo "<td>{$column['Type']}</td>\n";
    echo "<td>{$column['Null']}</td>\n";
    echo "<td>{$column['Key']}</td>\n";
    echo "<td>{$column['Default']}</td>\n";
    echo "<td>{$column['Extra']}</td>\n";
    echo "</tr>\n";
}
echo "</table>\n";
?>
