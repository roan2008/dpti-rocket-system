# Tests Directory

This directory contains all test files for the DPTI Rocket System project.

## Test Files Overview

### Core System Tests
- `test_motor_charging_report_full.php` - Complete test suite for Motor Charging Report feature
- `check_database_schema.php` - Database schema validation and structure checker
- `create_test_data.php` - Test data generation script

### Feature-Specific Tests
- `test_motor_charging_report.php` - Motor charging report basic tests
- `test_motor_report_complete.php` - Complete motor report tests
- `test_approvals.php` - Approval system tests
- `test_audit_system.php` - Audit trail tests
- `test_controller.php` - Controller functionality tests

### UI/UX Tests
- `test_localization.php` - Language and localization tests
- `test_translation_dashboard.php` - Translation system tests
- `test_nav.php` - Navigation system tests

### Workflow Tests
- `test_approval_workflow.php` - Complete approval workflow tests
- `test_status_update.php` - Status update functionality tests
- `test_rollback.php` - Rollback system tests

### User Management Tests
- `test_user_management.php` - User management system tests
- `test_user_management_complete.php` - Complete user management tests
- `test_login.php` - Login system tests
- `test_login_views.php` - Login views tests

### Template & Form Tests
- `test_template_management.php` - Template management tests
- `test_template_integration.php` - Template integration tests
- `test_template_ui.php` - Template UI tests
- `test_dynamic_forms.php` - Dynamic form generation tests

### System Integration Tests
- `test_step_ajax.php` - AJAX step functionality tests
- `test_ajax_endpoint.php` - AJAX endpoints tests
- `test_analytics_dashboard.php` - Analytics dashboard tests
- `test_css_refactoring.php` - CSS refactoring tests

### Report Generation Tests
- `test_report_generation.php` - General report generation tests

## Running Tests

### Individual Test Files
```bash
php tests/test_motor_charging_report_full.php
php tests/check_database_schema.php
```

### Database Schema Check
```bash
php tests/check_database_schema.php
```

### Create Test Data
```bash
php tests/create_test_data.php
```

## Test Guidelines

1. **Always run schema check first** when testing after database changes
2. **Use test data creation script** to ensure consistent test environment
3. **Run motor charging report full test** to verify complete feature functionality
4. **Check error logs** after running tests for detailed debugging information

## Test Environment Requirements

- PHP 8.x with PDO MySQL extension
- MySQL database `dpti_rocket_prod`
- XAMPP or similar local development environment
- All project dependencies loaded

## Notes

- Test files are designed to be run from the project root directory
- Some tests require specific database states - use create_test_data.php to set up
- Tests include comprehensive error reporting and debugging output
- Session simulation is included for authentication-required features
