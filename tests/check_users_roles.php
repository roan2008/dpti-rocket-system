<?php
/**
 * Check Current Users and Roles
 * This script shows all users in the system and their roles
 */

// Include required files
require_once '../includes/db_connect.php';

echo "=== CURRENT USERS AND ROLES CHECK ===\n\n";

try {
    // Get all users from database
    $stmt = $pdo->prepare("SELECT user_id, username, full_name, role, created_at FROM users ORDER BY role, username");
    $stmt->execute();
    $users = $stmt->fetchAll();
    
    echo "Total users found: " . count($users) . "\n\n";
    
    if (empty($users)) {
        echo "❌ No users found in database!\n";
        echo "You need to create test users for each role.\n\n";
    } else {
        echo "📋 Current Users:\n";
        echo str_pad("ID", 4) . str_pad("Username", 15) . str_pad("Full Name", 25) . str_pad("Role", 12) . "Created\n";
        echo str_repeat("-", 70) . "\n";
        
        $role_counts = [];
        foreach ($users as $user) {
            echo str_pad($user['user_id'], 4) . 
                 str_pad($user['username'], 15) . 
                 str_pad($user['full_name'], 25) . 
                 str_pad($user['role'], 12) . 
                 date('M j, Y', strtotime($user['created_at'])) . "\n";
            
            // Count roles
            $role_counts[$user['role']] = ($role_counts[$user['role']] ?? 0) + 1;
        }
        
        echo "\n📊 Role Distribution:\n";
        foreach ($role_counts as $role => $count) {
            echo "  $role: $count user(s)\n";
        }
        
        echo "\n🎯 Required Roles for Testing:\n";
        $required_roles = ['admin', 'engineer', 'staff'];
        $missing_roles = [];
        
        foreach ($required_roles as $role) {
            if (isset($role_counts[$role])) {
                echo "  ✅ $role: " . $role_counts[$role] . " user(s) available\n";
            } else {
                echo "  ❌ $role: MISSING!\n";
                $missing_roles[] = $role;
            }
        }
        
        if (!empty($missing_roles)) {
            echo "\n⚠️  Missing roles detected: " . implode(', ', $missing_roles) . "\n";
            echo "Creating missing test users...\n\n";
            
            // Create missing users
            foreach ($missing_roles as $role) {
                create_test_user($pdo, $role);
            }
        }
    }
    
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}

echo "\n=== USER CREATION COMPLETE ===\n";
echo "You can now test with the following accounts:\n\n";

// Show login credentials
echo "🔑 TEST USER CREDENTIALS:\n";
echo "------------------------\n";
echo "ADMIN:\n";
echo "  Username: admin\n";
echo "  Password: admin123\n";
echo "  Permissions: Full access (add, edit, delete rockets)\n\n";

echo "ENGINEER:\n";
echo "  Username: engineer\n";
echo "  Password: engineer123\n";
echo "  Permissions: Add and edit rockets, approve production steps\n\n";

echo "STAFF:\n";
echo "  Username: staff\n";
echo "  Password: staff123\n";
echo "  Permissions: View rockets, update status, record production steps\n\n";

echo "🌐 Login URL: http://localhost/dpti-rocket-system/views/login_view.php\n";

/**
 * Create a test user for the specified role
 */
function create_test_user($pdo, $role) {
    $users_to_create = [
        'admin' => [
            'username' => 'admin',
            'full_name' => 'System Administrator',
            'password' => 'admin123'
        ],
        'engineer' => [
            'username' => 'engineer', 
            'full_name' => 'Test Engineer',
            'password' => 'engineer123'
        ],
        'staff' => [
            'username' => 'staff',
            'full_name' => 'Production Staff',
            'password' => 'staff123'
        ]
    ];
    
    if (!isset($users_to_create[$role])) {
        echo "  ❌ Unknown role: $role\n";
        return false;
    }
    
    $user_data = $users_to_create[$role];
    
    try {
        // Check if user already exists
        $check_stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ?");
        $check_stmt->execute([$user_data['username']]);
        
        if ($check_stmt->fetch()) {
            echo "  ⚠️  User '{$user_data['username']}' already exists, skipping...\n";
            return true;
        }
        
        // Create the user
        $password_hash = password_hash($user_data['password'], PASSWORD_DEFAULT);
        $insert_stmt = $pdo->prepare("INSERT INTO users (username, password_hash, full_name, role) VALUES (?, ?, ?, ?)");
        $result = $insert_stmt->execute([
            $user_data['username'],
            $password_hash,
            $user_data['full_name'],
            $role
        ]);
        
        if ($result) {
            $new_user_id = $pdo->lastInsertId();
            echo "  ✅ Created $role user: {$user_data['username']} (ID: $new_user_id)\n";
            return true;
        } else {
            echo "  ❌ Failed to create $role user\n";
            return false;
        }
        
    } catch (PDOException $e) {
        echo "  ❌ Error creating $role user: " . $e->getMessage() . "\n";
        return false;
    }
}
?>
