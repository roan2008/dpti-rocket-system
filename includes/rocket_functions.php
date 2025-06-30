<?php
/**
 * Rocket Functions
 * Contains all rocket-related logic and database operations
 */

/**
 * Get all rockets from the database
 * 
 * @param PDO $pdo Database connection
 * @return array Array of all rockets or empty array on failure
 */
function get_all_rockets($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT rocket_id, serial_number, project_name, current_status, created_at FROM rockets ORDER BY created_at DESC");
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Get all rockets error: " . $e->getMessage());
        return array();
    }
}

/**
 * Get rocket by ID
 * 
 * @param PDO $pdo Database connection
 * @param int $rocket_id Rocket ID
 * @return array|false Rocket data or false if not found
 */
function get_rocket_by_id($pdo, $rocket_id) {
    try {
        $stmt = $pdo->prepare("SELECT rocket_id, serial_number, project_name, current_status, created_at FROM rockets WHERE rocket_id = ?");
        $stmt->execute([$rocket_id]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Get rocket by ID error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get rocket by serial number
 * 
 * @param PDO $pdo Database connection
 * @param string $serial_number Rocket serial number
 * @return array|false Rocket data or false if not found
 */
function get_rocket_by_serial($pdo, $serial_number) {
    try {
        $stmt = $pdo->prepare("SELECT rocket_id, serial_number, project_name, current_status, created_at FROM rockets WHERE serial_number = ?");
        $stmt->execute([$serial_number]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Get rocket by serial error: " . $e->getMessage());
        return false;
    }
}

/**
 * Create new rocket
 * 
 * @param PDO $pdo Database connection
 * @param string $serial_number Unique serial number
 * @param string $project_name Project name
 * @param string $current_status Initial status
 * @return int|false New rocket ID or false on failure
 */
function create_rocket($pdo, $serial_number, $project_name, $current_status = 'New') {
    try {
        $stmt = $pdo->prepare("INSERT INTO rockets (serial_number, project_name, current_status) VALUES (?, ?, ?)");
        $stmt->execute([$serial_number, $project_name, $current_status]);
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        error_log("Create rocket error: " . $e->getMessage());
        return false;
    }
}

/**
 * Update rocket status
 * 
 * @param PDO $pdo Database connection
 * @param int $rocket_id Rocket ID
 * @param string $new_status New status
 * @return bool True on success, false on failure
 */
function update_rocket_status($pdo, $rocket_id, $new_status) {
    try {
        $stmt = $pdo->prepare("UPDATE rockets SET current_status = ? WHERE rocket_id = ?");
        return $stmt->execute([$new_status, $rocket_id]);
    } catch (PDOException $e) {
        error_log("Update rocket status error: " . $e->getMessage());
        return false;
    }
}

/**
 * Update rocket information
 * 
 * @param PDO $pdo Database connection
 * @param int $rocket_id Rocket ID
 * @param string $serial_number Serial number
 * @param string $project_name Project name
 * @param string $current_status Current status
 * @return bool True on success, false on failure
 */
function update_rocket($pdo, $rocket_id, $serial_number, $project_name, $current_status) {
    try {
        $stmt = $pdo->prepare("UPDATE rockets SET serial_number = ?, project_name = ?, current_status = ? WHERE rocket_id = ?");
        return $stmt->execute([$serial_number, $project_name, $current_status, $rocket_id]);
    } catch (PDOException $e) {
        error_log("Update rocket error: " . $e->getMessage());
        return false;
    }
}

/**
 * Delete rocket
 * 
 * @param PDO $pdo Database connection
 * @param int $rocket_id Rocket ID
 * @return bool True on success, false on failure
 */
function delete_rocket($pdo, $rocket_id) {
    try {
        $stmt = $pdo->prepare("DELETE FROM rockets WHERE rocket_id = ?");
        return $stmt->execute([$rocket_id]);
    } catch (PDOException $e) {
        error_log("Delete rocket error: " . $e->getMessage());
        return false;
    }
}

/**
 * Count total rockets
 * 
 * @param PDO $pdo Database connection
 * @return int Number of rockets
 */
function count_rockets($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM rockets");
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Count rockets error: " . $e->getMessage());
        return 0;
    }
}
?>
