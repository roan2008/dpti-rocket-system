# Copilot Instructions for DPTI Rocket System (Version 2.0)

## 0. Project State & Onboarding
- **Current State of the Project:** To understand the current progress, what has been done, and what the next steps are, refer to the **`docs/DEVELOPMENT_GUIDELINE.md`** file. This document contains the master sprint plan and is updated regularly.
- **Goal:** Your primary goal is to assist in completing the tasks outlined in the sprint plan within `DEVELOPMENT_GUIDELINE.md`, following all architectural and security rules defined below.

## 1. Core Commands & Project Lifecycle
- **Build:** No explicit build step is required for this PHP project.
- **Lint:** Use the built-in PHP linter for basic syntax checking (`php -l <file>`).
- **Test:** Manual testing follows a strict 3-tier approach:
  1.  **Developer's Check:** Use `var_dump()` and direct database inspection immediately after coding small components.
  2.  **End-of-Feature Testing:** Create and follow specific, written test cases for each completed feature (e.g., login, data recording).
  3.  **User Acceptance Testing (UAT):** Perform end-to-end workflow tests by role-playing as 'admin', 'engineer', and 'staff' personas.
- **Docs:** All project documentation is located in the `README.md` file and within the `docs/` folder.
- **Migrations:** Database schema and subsequent changes are managed manually via the `docs/database_schema.sql` file.

## 2. High-Level Architecture
- **Framework:** This is a custom "vanilla" PHP project, not using any external frameworks like Laravel or Symfony.
- **Core Principle:** The project strictly follows the **Separation of Concerns (SoC)** principle.
- **Structure:**
  - `assets/`: For all static files (CSS, JavaScript, images).
  - `controllers/`: For PHP files that handle business logic, process form submissions, and manage application flow.
  - `includes/`: For shared, modular PHP files (DB connection, reusable functions/models, header, and footer).
  - `views/`: For PHP files that act as the presentation layer, containing primarily HTML.
- **Database:** The project uses MySQL. The database name is `dpti_rocket_prod`. The complete schema is defined in `docs/database_schema.sql`.

## 3. Style, Quality & Modularity Rules
- **Anti-Spaghetti Code Principle (Crucial):**
  - **Views (`views/`):** Must contain almost exclusively HTML. PHP should only be used for simple display logic (`echo`, `if/else`, `foreach`). **Strictly no database queries or business logic.**
  - **Controllers (`controllers/`):** Must handle user input (`$_POST`, `$_GET`), call functions from the `includes/` directory, and decide which view to load or where to redirect. **Strictly no complex HTML.**
  - **Functions/Models (`includes/`):** Must contain reusable logic, especially for database interactions. Functions should `return` data, not `echo` HTML.
- **File Size and Complexity:**
  - **Keep files focused and small.** A single PHP file should ideally not exceed 200-300 lines of code. A hard limit is around 600 lines.
  - If a file is growing too large, it is a strong indicator that it is doing too many things. **Refactor it** by breaking it down into smaller, more specialized functions or files.
- **Modularity and Reusability (`includes/` folder):**
  - **Group related functions:** Do not put all functions into a single monolithic file. Create separate, focused files for different concerns.
    - Example: `user_functions.php` for all user-related logic (login, registration, data retrieval).
    - Example: `rocket_functions.php` for all rocket data logic (get, create, update status).
    - Example: `utility_functions.php` for general helper functions (date formatting, input validation, etc.).
  - **Functions should be pure where possible:** They should take inputs and produce outputs, leaving side effects (like `header()` redirects or `echo`) to the controllers.
- **Formatting:** Use 4 spaces for indentation across all files (PHP, HTML, CSS, JS).
- **Naming Conventions:**
  - PHP variables and functions: `snake_case` (e.g., `$user_data`, `get_all_rockets()`).
  - PHP Classes (if any): `PascalCase`.
  - Database tables: plural, `snake_case` (e.g., `production_steps`).
  - Database columns: `snake_case` (e.g., `serial_number`).
- **Git Commit Messages:** Strictly follow the Conventional Commits specification.
  - Use prefixes: `feat:` (new feature), `fix:` (bug fix), `docs:` (documentation), `style:` (formatting), `refactor:` (code restructuring), `test:` (adding tests).
  - Example: `feat: Implement user login functionality`.
  

## 4. Security Rules (Non-negotiable)
- **SQL Injection:** Always use **PDO prepared statements** with parameter binding for all database queries. Never use string concatenation to build queries with user input.
- **Password Hashing:** Always use `password_hash()` when storing new user passwords and `password_verify()` when authenticating them.
- **Cross-Site Scripting (XSS):** Always use `htmlspecialchars()` when echoing any data that originated from a user or the database back into the HTML to prevent XSS attacks.
- **Session Management:** Always use `session_start()` at the very top of any script that requires access to session data. Implement access control checks on restricted pages to verify user authentication and authorization (role).

## 5. Documentation Summary
- **`README.md`:** The public face of the project. Contains the project overview, technology stack, and setup instructions.
- **`docs/DEVELOPMENT_GUIDELINE.md`:** The internal "blueprint". Contains detailed guidelines for the development process, including the sprint plan and testing strategy.
- **`docs/database_schema.sql`:** The single source of truth for the database structure.

---
For more, see [Copilot instructions docs](https://aka.ms/vscode-instructions-docs)
