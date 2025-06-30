# Development Guidelines

## Project Status & Progress

### ✅ Completed (Phase 1 - Foundation)
- **Project Structure**: Created complete directory structure following Separation of Concerns
- **Database**: `dpti_rocket_prod` database with all 4 tables (users, rockets, production_steps, approvals)
- **Core Infrastructure**: 
  - Database connection (`includes/db_connect.php`)
  - Header/Footer templates (`includes/header.php`, `includes/footer.php`)
  - Basic CSS styling (`assets/css/style.css`)
- **User Authentication System**:
  - Login view with error handling (`views/login_view.php`)
  - Login controller with security (`controllers/login_controller.php`)
  - User functions with PDO/sessions (`includes/user_functions.php`)
  - Dashboard with role-based access (`dashboard.php`)
  - Logout functionality (`controllers/logout_controller.php`)
- **Testing Framework**: Command-line test script with automated cleanup (`tests/test_login_function.php`)

### 🔄 Current Phase (Phase 2 - Core Features)
**Priority: Rocket Management System**

#### ✅ Completed Tasks:
1. **Rocket Add Functionality** ✅
   - `views/rocket_add_view.php` - Add new rocket form (COMPLETE)
   - `controllers/rocket_controller.php` - Handle CRUD operations (COMPLETE)
   - `includes/rocket_functions.php` - Database functions for rockets (COMPLETE)
   - Comprehensive testing with `tests/test_rocket_add.php` (COMPLETE)

2. **Rocket Detail/Edit Functionality** ✅
   - `views/rocket_detail_view.php` - View/edit rocket details (COMPLETE)
   - Enhanced CSS styling for detail views (COMPLETE)
   - Role-based edit permissions (admin/engineer) (COMPLETE)
   - Quick status update functionality (COMPLETE)
   - Delete functionality with confirmation modal (admin only) (COMPLETE)
   - Comprehensive testing with `tests/test_rocket_detail.php` (COMPLETE)

#### 🎯 Next Immediate Tasks:
1. **Production Steps Tracking**
   - `views/production_steps_view.php` - List steps for a rocket
   - `views/add_step_view.php` - Add production step form
   - `controllers/production_controller.php` - Handle step operations
   - `includes/production_functions.php` - Production logic

2. **Approval System**
   - `views/approvals_view.php` - List pending approvals (for engineers)
   - `views/approval_detail_view.php` - Review and approve/reject
   - `controllers/approval_controller.php` - Handle approval workflow
   - `includes/approval_functions.php` - Approval logic

### 📋 Future Phases

#### Phase 3 - User Management (Admin Features)
- User registration/management
- Role-based permissions enforcement
- User activity logging

#### Phase 4 - Advanced Features
- Data export/reporting
- Email notifications
- Production workflow optimization
- Advanced search and filtering

#### Phase 5 - Polish & Deployment
- Enhanced UI/UX
- Performance optimization
- Security audit
- Documentation completion

## Coding Standards

### PHP Standards
- **Indentation**: 4 spaces (no tabs)
- **Variables/Functions**: `snake_case` (e.g., `$user_data`, `get_rocket_by_id()`)
- **Classes**: `PascalCase` (if used)
- **Constants**: `UPPER_SNAKE_CASE`
- **File names**: `snake_case.php`

### Security Requirements (Non-negotiable)
- **SQL Injection Prevention**: Always use PDO prepared statements
- **Password Security**: `password_hash()` for storage, `password_verify()` for authentication
- **XSS Prevention**: `htmlspecialchars()` for all user data output
- **Session Security**: Proper session management with `session_start()` checks

### Error Handling
- Use `try-catch` blocks for database operations
- Log errors with `error_log()`, never expose to users
- Return appropriate error messages to controllers

## File Structure

### Separation of Concerns Rules
- **Views (`views/`)**: Only HTML + simple PHP display logic (`echo`, `if/else`, `foreach`)
- **Controllers (`controllers/`)**: Handle HTTP requests, call functions, manage redirects
- **Includes (`includes/`)**: Reusable functions, database operations, shared components
- **Assets (`assets/`)**: Static files (CSS, JS, images)

### File Organization
- Group related functions in focused files (e.g., `user_functions.php`, `rocket_functions.php`)
- Keep files under 300 lines (hard limit: 600 lines)
- Break large files into smaller, specialized components

## Database Guidelines

### Schema Management
- **Master Schema**: `docs/database_schema.sql` is the single source of truth
- **Migrations**: Manual updates to schema file, then re-import
- **Naming**: Tables plural `snake_case`, columns `snake_case`

### Query Standards
- Always use PDO prepared statements
- Use meaningful parameter names in bindings
- Handle PDO exceptions appropriately
- Use transactions for multi-table operations

### Current Database Structure
```
users (user_id, username, password_hash, full_name, role, created_at)
rockets (rocket_id, serial_number, project_name, current_status, created_at)
production_steps (step_id, rocket_id, step_name, data_json, staff_id, step_timestamp)
approvals (approval_id, step_id, engineer_id, status, comments, approval_timestamp)
```

## Testing

### Three-Tier Testing Strategy
1. **Developer Testing**: `var_dump()` + direct database inspection during development
2. **Feature Testing**: Written test cases for each completed feature
3. **User Acceptance Testing**: Role-based workflow testing (admin/engineer/staff)

### Test Script Guidelines
- Create command-line tests in `tests/` directory
- Always include cleanup (delete test data)
- Test both success and failure scenarios
- Print clear PASS/FAIL results

### Current Test Coverage
- ✅ User login function (3 test cases)
- 🔄 Next: Rocket CRUD operations testing

## Development Workflow

### Git Commit Standards
Follow Conventional Commits:
- `feat:` New features
- `fix:` Bug fixes
- `docs:` Documentation updates
- `style:` Code formatting
- `refactor:` Code restructuring
- `test:` Adding tests

### Development Process
1. Create/update function in `includes/`
2. Create/update controller in `controllers/`
3. Create/update view in `views/`
4. Test with command-line script
5. Manual browser testing
6. Update this progress document

## Deployment

### XAMPP Local Development
- Database: `dpti_rocket_prod` on localhost MySQL
- Web root: `c:\xampp\htdocs\dpti-rocket-system\`
- Access: `http://localhost/dpti-rocket-system/`

### Production Preparation (Future)
- Environment configuration
- Database migration scripts
- Security hardening
- Performance optimization

---
**Last Updated**: June 30, 2025  
**Current Phase**: Phase 2 - Core Features (Rocket Management)
