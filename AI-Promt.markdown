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
- Implement code optimization techniques to manage scalability and performance, including:
  - **Code Splitting**: Use modern bundlers (e.g., Webpack for React or Vite for Vue) to split code into chunks, loading only components relevant to the current page.
  - **Lazy Loading**: Load non-essential modules (e.g., less-used portal pages like reporting sections) only when needed by the user.
  - **Tree Shaking**: Employ bundling tools like Webpack to eliminate unused code, reducing overall file sizes.
  - **Server-Side Rendering (SSR) or Static Site Generation (SSG)**: Integrate frameworks like Next.js (for React) or Nuxt.js (for Vue) to perform initial rendering on the server, improving load times and reducing client-side JavaScript volume.
- Adopt clean architecture practices:
  - Use design patterns such as Modular Architecture, Domain-Driven Design (DDD), or Micro-Frontends to ensure the project remains scalable.
- Include testing and documentation:
  - Implement automated tests (Unit and Integration) to prevent code chaos.
  - Maintain good documentation for ease of maintenance.
- Optimize performance:
  - Use tools like Lighthouse for performance analysis, combined with techniques like Lazy Loading and SSR.
- Implement monitoring:
  - Integrate tools like Sentry or LogRocket for error tracking in production.

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
- Optimize for scalability in the SPA:
  - Apply Code Splitting, Lazy Loading, and Tree Shaking to manage bundle sizes as the project grows (e.g., for handling 100+ pages/modules).
  - Use SSR/SSG where appropriate to enhance initial load performance.
  - Structure the frontend modularly, potentially using Micro-Frontends for independent development of sections like dashboards or user management.

## Migration Tasks (Updated for SPA Conversion)
### Redesign Old PHP Pages to SPA Components
- Identify all old PHP pages (at least 20, like dashboard.php, users.php) and completely convert them to new SPA components.
- For each old page:
  - Keep server-side logic (models and controllers) in PHP MVC and build RESTful APIs (e.g., GET /api/dashboard for data).
  - Migrate frontend content (HTML/JS/CSS) to modular SPA components (e.g., Dashboard.vue or Users.js).
  - Use client-side routing (Vue Router or React Router) so pages load without page reload.
- Perform this conversion gradually: Start with dashboard, then migrate other pages one by one to avoid disrupting the project.
- To prevent scalability issues, use techniques like Code Splitting and Lazy Loading to load only necessary components.

### Design Modular and Empty Dashboard for Future
- Design the dashboard page as a main component (Dashboard.vue or Dashboard.js) that is initially "empty" (only basic structure like a grid or container for widgets).
- Prepare the dashboard section for creating widgets (e.g., stats cards, charts) and access links (shortcuts to other sections): Use dynamic child components loaded from API.
- Keep the structure flexible for future expansion (e.g., use slots in Vue or children props in React, and State Management for dynamic widget management).
- Add minimal initial content (e.g., welcome message), but focus on expandability.

## Testing and Debugging
- Test all code in the XAMPP environment to ensure compatibility.
- Include error logging in `config.php` for debugging, but disable it in production.
- Write unit tests for critical components (e.g., user authentication, access control) using PHPUnit.
- Use performance optimization tools and monitoring as outlined in Coding Standards.

## Additional Notes
- Ensure the project is secure, with protections against common vulnerabilities like XSS, CSRF, and SQL injection.
- Optimize performance for standard cPanel hosting environments (e.g., avoid heavy dependencies).
- Keep the codebase lightweight and avoid unnecessary third-party libraries unless approved.
- Never include references to AI tools, GitHub Copilot, or any generative AI in the codebase, comments, or documentation.

