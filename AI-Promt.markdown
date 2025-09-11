# GitHub Copilot Instructions for Corporate Portal Project

## Project Overview
This is a PHP-based corporate portal project designed for manufacturing companies. The project follows the Model-View-Controller (MVC) architecture and is structured as a Single Page Application (SPA). The goal is to create a system that is easy to install and deploy, similar to WordPress, and can be tested and developed on XAMPP and deployed on standard cPanel hosting.

## General Guidelines
- **Language**: Use PHP (version 7.4 or higher) for backend development.
- **Architecture**: Strictly adhere to the MVC pattern. All PHP files (models, views, controllers) must be organized in appropriate directories (e.g., `app/Models`, `app/Views`, `app/Controllers`) and **no PHP files should reside in the project root directory**.
- **Single Page Application (SPA)**: The frontend should be designed as an SPA, using JavaScript (preferably with a framework like Vue.js or React) for dynamic content loading and client-side routing.
- **Ease of Installation**: The project must have a simple installation process similar to WordPress, with a clear setup wizard or configuration file (e.g., `config.php`) for database and environment settings. Ensure compatibility with standard cPanel hosting.
- **Development Environment**: The project is developed and tested on XAMPP. Ensure all code is compatible with XAMPP's default PHP and MySQL configurations.
- **Deployment**: The project must be deployable on any standard cPanel hosting with minimal configuration, supporting PHP and MySQL.

## Project Features
1. **User Management and Access Control**:
   - Implement a comprehensive user management system with support for user registration, login, password reset, and profile management.
   - Include role-based access control (RBAC) with multiple permission levels (e.g., admin, editor, viewer).
   - Store user data securely in a MySQL database with proper password hashing (use PHP's `password_hash()` and `password_verify()`).
2. **Branding and Attribution**:
   - Display the following developer information in the footer of all pages and on printed pages:
     ```
     Mahdi Alizadeh
     M.alizadeh7@live.com
     Alizadehx.ir
     ```
   - Ensure this information is styled appropriately and consistently across the application.
   - **Do not** mention anywhere in the code, comments, or UI that the project was built using artificial intelligence or tools like GitHub Copilot.

## Coding Standards
- Follow **PSR-12** coding standards for PHP.
- Use meaningful variable and function names, following camelCase for functions and variables, and PascalCase for class names.
- Include comments in Persian or English to explain complex logic, but keep them concise.
- Avoid inline JavaScript or CSS in PHP files; separate them into dedicated files in `public/js` and `public/css` directories.
- Use prepared statements for all database queries to prevent SQL injection.
- Ensure the codebase is modular and reusable, with clear separation of concerns.

## File Structure
- Example directory structure:
  ```
  /project-root
  ├── app/
  │   ├── Controllers/
  │   ├── Models/
  │   ├── Views/
  ├── public/
  │   ├── index.php
  │   ├── js/
  │   ├── css/
  │   ├── assets/
  ├── config/
  │   ├── config.php
  ├── vendor/ (for Composer dependencies)
  ├── .htaccess
  ├── composer.json
  ```
- Ensure `.htaccess` is configured to route all requests through `public/index.php` for SPA functionality and MVC routing.

## Database
- Use MySQL for the database, compatible with XAMPP and cPanel hosting.
- Include a SQL setup script (e.g., `install.sql`) for creating necessary tables during installation.
- Provide a configuration file (`config.php`) for database credentials, similar to WordPress's `wp-config.php`.

## Frontend
- Use a lightweight JavaScript framework (e.g., Vue.js) for the SPA frontend.
- Ensure the frontend communicates with the backend via RESTful APIs built in PHP.
- Use AJAX or Fetch API for dynamic data loading without page refreshes.
- Include responsive CSS (preferably with a framework like Bootstrap or Tailwind CSS) for compatibility with various devices.

## Testing and Debugging
- Test all code in the XAMPP environment to ensure compatibility.
- Include error logging in `config.php` for debugging, but disable it in production.
- Write unit tests for critical components (e.g., user authentication, access control) using PHPUnit.

## Additional Notes
- Ensure the project is secure, with protections against common vulnerabilities like XSS, CSRF, and SQL injection.
- Optimize performance for standard cPanel hosting environments (e.g., avoid heavy dependencies).
- Keep the codebase lightweight and avoid unnecessary third-party libraries unless approved.
- Never include references to AI tools, GitHub Copilot, or any generative AI in the codebase, comments, or documentation.