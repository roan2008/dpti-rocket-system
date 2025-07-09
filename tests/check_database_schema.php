<?php
/**
 * Database Schema Checker
 * Checks the actual database structure to identify column naming issues
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== DATABASE SCHEMA CHECKER ===\n\n";

// Include database connection
require_once __DIR__ . '/includes/db_connect.php';

try {
    // Check database connection
    echo "✅ Database connected successfully\n";
    echo "Database name: dpti_rocket_prod\n\n";
    
    // List all tables
    echo "=== ALL TABLES ===\n";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($tables as $table) {
        echo "- $table\n";
    }
    echo "\n";
    
    // Check each important table structure
    $important_tables = ['rockets', 'production_steps', 'approvals', 'users'];
    
    foreach ($important_tables as $table_name) {
        echo "=== TABLE: $table_name ===\n";
        
        try {
            $stmt = $pdo->query("DESCRIBE $table_name");
            $columns = $stmt->fetchAll();
            
            echo "Columns:\n";
            foreach ($columns as $column) {
                $key_info = '';
                if ($column['Key'] === 'PRI') $key_info = ' [PRIMARY KEY]';
                if ($column['Key'] === 'MUL') $key_info = ' [INDEX]';
                if ($column['Key'] === 'UNI') $key_info = ' [UNIQUE]';
                
                echo "  - {$column['Field']} ({$column['Type']})";
                echo $column['Null'] === 'NO' ? ' NOT NULL' : ' NULL';
                echo $column['Default'] ? " DEFAULT: {$column['Default']}" : '';
                echo $key_info;
                echo "\n";
            }
            
            // Sample data count
            $count_stmt = $pdo->query("SELECT COUNT(*) FROM $table_name");
            $count = $count_stmt->fetchColumn();
            echo "Records: $count\n";
            
            // Show sample data for small tables
            if ($count > 0 && $count <= 10) {
                echo "Sample data:\n";
                $sample_stmt = $pdo->query("SELECT * FROM $table_name LIMIT 3");
                $samples = $sample_stmt->fetchAll();
                foreach ($samples as $sample) {
                    echo "  Record: " . json_encode($sample, JSON_UNESCAPED_UNICODE) . "\n";
                }
            }
            
        } catch (Exception $e) {
            echo "❌ Error accessing table $table_name: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }
    
    // Check for common foreign key relationships
    echo "=== FOREIGN KEY RELATIONSHIPS ===\n";
    
    // Check production_steps to rockets relationship
    try {
        $stmt = $pdo->query("
            SELECT 
                TABLE_NAME,
                COLUMN_NAME,
                CONSTRAINT_NAME,
                REFERENCED_TABLE_NAME,
                REFERENCED_COLUMN_NAME
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE REFERENCED_TABLE_NAME IS NOT NULL
            AND TABLE_SCHEMA = 'dpti_rocket_prod'
        ");
        $fks = $stmt->fetchAll();
        
        if (empty($fks)) {
            echo "⚠️ No foreign key constraints found\n";
        } else {
            foreach ($fks as $fk) {
                echo "- {$fk['TABLE_NAME']}.{$fk['COLUMN_NAME']} -> {$fk['REFERENCED_TABLE_NAME']}.{$fk['REFERENCED_COLUMN_NAME']}\n";
            }
        }
    } catch (Exception $e) {
        echo "❌ Error checking foreign keys: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
    
    // Test actual data relationships
    echo "=== DATA RELATIONSHIP TEST ===\n";
    
    // Check if we have rockets
    try {
        $stmt = $pdo->query("SELECT * FROM rockets LIMIT 3");
        $rockets = $stmt->fetchAll();
        
        if (empty($rockets)) {
            echo "⚠️ No rockets found in database\n";
        } else {
            echo "Sample rockets:\n";
            foreach ($rockets as $rocket) {
                echo "  - Rocket: " . json_encode($rocket, JSON_UNESCAPED_UNICODE) . "\n";
            }
            
            // Check production steps for first rocket
            $first_rocket_id = $rockets[0]['rocket_id'] ?? $rockets[0]['id'] ?? null;
            if ($first_rocket_id) {
                echo "\nProduction steps for rocket ID $first_rocket_id:\n";
                $stmt = $pdo->prepare("SELECT * FROM production_steps WHERE rocket_id = ? LIMIT 5");
                $stmt->execute([$first_rocket_id]);
                $steps = $stmt->fetchAll();
                
                if (empty($steps)) {
                    echo "  ⚠️ No production steps found\n";
                } else {
                    foreach ($steps as $step) {
                        echo "  - Step: " . json_encode($step, JSON_UNESCAPED_UNICODE) . "\n";
                    }
                }
            }
        }
    } catch (Exception $e) {
        echo "❌ Error testing data relationships: " . $e->getMessage() . "\n";
    }
    
    echo "\n=== SCHEMA CHECK COMPLETE ===\n";
    
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>
