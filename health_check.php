<?php
// ==============================================================================
// DPTI Rocket System - Health Check Script
// ==============================================================================
//
// สามารถรันผ่าน Command Line (CLI) เพื่อตรวจสอบความพร้อมของระบบ
// วิธีใช้: php health_check.php
//
// ==============================================================================

// ฟังก์ชันสำหรับแสดงผลลัพธ์พร้อมสีใน CLI
function print_status($message, $is_success) {
    // ตรวจสอบว่ารันบน CLI หรือไม่
    if (php_sapi_name() !== 'cli') {
        $status = $is_success ? "SUCCESS" : "FAILED";
        echo "{$message}... [{$status}]\n";
        return;
    }
    
    $color = $is_success ? "\033[32m" : "\033[31m"; // Green: success, Red: failure
    $status = $is_success ? "SUCCESS" : "FAILED";
    $reset_color = "\033[0m";
    echo "{$message}... {$color}[{$status}]{$reset_color}\n";
}

// ฟังก์ชันสำหรับแสดงข้อความสรุป
function print_summary($errors) {
    echo "==================================================\n";
    if ($errors === 0) {
        $color = "\033[32m"; // Green
        $message = "✅ All checks passed successfully! The system is ready.";
    } else {
        $color = "\033[31m"; // Red
        $message = "❌ Found {$errors} error(s). Please review the logs above.";
    }
    $reset_color = "\033[0m";

    if (php_sapi_name() !== 'cli') {
        echo $message . "\n";
    } else {
        echo "{$color}{$message}{$reset_color}\n";
    }
    echo "==================================================\n";
}

echo "==================================================\n";
echo " DPTI Rocket System - Health Check Script \n";
echo "==================================================\n";

$errors = 0;

// 1. ตรวจสอบเวอร์ชัน PHP
$min_php_version = '7.4';
$current_php_version = phpversion();
$is_php_ok = version_compare($current_php_version, $min_php_version, '>=');
print_status("Checking PHP version (>= {$min_php_version})", $is_php_ok);
if (!$is_php_ok) {
    echo "   - Your PHP version is {$current_php_version}. Please upgrade.\n";
    $errors++;
}

// 2. ตรวจสอบ PHP Extensions ที่จำเป็น
$required_extensions = ['pdo_mysql', 'session', 'json'];
foreach ($required_extensions as $ext) {
    $is_ext_ok = extension_loaded($ext);
    print_status("Checking for PHP extension: {$ext}", $is_ext_ok);
    if (!$is_ext_ok) {
        echo "   - Extension '{$ext}' is not loaded. Please enable it in php.ini.\n";
        $errors++;
    }
}

// 3. ตรวจสอบไฟล์ตั้งค่าฐานข้อมูล
$db_connect_path = __DIR__ . '/includes/db_connect.php';
$is_db_config_ok = file_exists($db_connect_path);
print_status("Checking for database config file (db_connect.php)", $is_db_config_ok);
if (!$is_db_config_ok) {
    echo "   - File not found at: {$db_connect_path}\n";
    $errors++;
    print_summary($errors);
    exit(1); // ออกจากโปรแกรมทันทีถ้าไฟล์ config ไม่มี
}

// 4. ทดสอบการเชื่อมต่อฐานข้อมูล
require_once $db_connect_path;
$pdo = null;
try {
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, $username, $password, $options);
    print_status("Attempting database connection", true);
} catch (PDOException $e) {
    print_status("Attempting database connection", false);
    echo "   - " . $e->getMessage() . "\n";
    echo "   - Please check credentials in 'includes/db_connect.php'.\n";
    $errors++;
    print_summary($errors);
    exit(1); // ออกจากโปรแกรมถ้าเชื่อมต่อไม่ได้
}

// 5. ตรวจสอบตารางหลัก
$required_tables = ['users', 'rockets', 'production_steps', 'step_templates'];
foreach ($required_tables as $table) {
    try {
        $stmt = $pdo->query("SELECT 1 FROM `{$table}` LIMIT 1");
        print_status("Checking for '{$table}' table", true);
    } catch (Exception $e) {
        print_status("Checking for '{$table}' table", false);
        echo "   - Table '{$table}' not found or is inaccessible.\n";
        $errors++;
    }
}

// สรุปผล
print_summary($errors);

// คืนค่า exit code เพื่อให้ script อื่นนำไปใช้ต่อได้
exit($errors > 0 ? 1 : 0);
