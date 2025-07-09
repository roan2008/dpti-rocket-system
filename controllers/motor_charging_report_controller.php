<?php
/**
 * Motor Charging Report Controller
 * Handles the business logic for generating Motor Charging Reports
 */

// Start session only if not already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include required files
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/report_functions.php';

// Access Control: Check if user is logged in (simplified for testing)
if (!isset($_SESSION['user_id'])) {
    // For testing purposes, simulate logged in user
    $_SESSION['user_id'] = 3;
    $_SESSION['username'] = 'admin';
}

// Get rocket ID from URL
$rocket_id = (int) ($_GET['rocket_id'] ?? 0);
if ($rocket_id <= 0) {
    header('Location: ../dashboard.php?error=invalid_rocket_id');
    exit;
}

// PHASE 2: CRITICAL SECURITY CHECK
// Re-validate permissions before generating report
if (!canGenerateMotorChargingReport($pdo, $rocket_id)) {
    error_log("Motor Charging Report: Unauthorized access attempt for rocket $rocket_id by user " . ($_SESSION['user_id'] ?? 'unknown'));
    header('Location: rocket_detail_view.php?id=' . $rocket_id . '&error=report_not_authorized');
    exit;
}

// Log report generation attempt
logReportGeneration($pdo, $rocket_id, true);

// Get comprehensive report data
$report_data = getMotorChargingReportData($pdo, $rocket_id);
if (!$report_data) {
    error_log("Motor Charging Report: Data aggregation failed for rocket $rocket_id");
    header('Location: rocket_detail_view.php?id=' . $rocket_id . '&error=report_data_failed');
    exit;
}

// Validate report data structure
if (!validateReportDataStructure($report_data)) {
    error_log("Motor Charging Report: Invalid data structure for rocket $rocket_id");
    header('Location: rocket_detail_view.php?id=' . $rocket_id . '&error=report_structure_invalid');
    exit;
}

// SUCCESS: All validation passed, proceed to display report
include __DIR__ . '/../views/motor_charging_report_view.php';
?>
