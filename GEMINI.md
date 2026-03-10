# LibSys - Library Information System

A comprehensive Library Information System for UCC (University of Caloocan City), featuring book and equipment management, attendance tracking, and borrowing transactions.

## Project Overview

*   **Type:** PHP Web Application (Custom MVC Framework)
*   **Backend:** PHP 8.x
*   **Frontend:** Tailwind CSS, JavaScript (Vanilla/AJAX)
*   **Database:** MySQL (via PDO)
*   **Architecture:** MVC with Repository Pattern
*   **Key Libraries:** 
    *   `phpdotenv` (Environment config)
    *   `endroid/qr-code` (QR generation)
    *   `dompdf/dompdf` (PDF Reports)
    *   `phpmailer/phpmailer` (Email notifications)
    *   `tailwindcss` (Styling)

## Directory Structure

*   `public/`: Entry point (`index.php`), assets (CSS, JS, Images), and uploaded files.
*   `src/`: Application source code.
    *   `Config/`: Configuration files (App, Routes, Database).
    *   `Controllers/`: Logic for handling requests.
    *   `Core/`: Framework core classes (Router, Database, Base Controller).
    *   `Models/`: (Currently used minimally, logic often in Repositories).
    *   `Repositories/`: Database abstraction layer (handles SQL queries).
    *   `Services/`: External services (e.g., MailService).
    *   `Views/`: PHP templates/view files.
*   `vendor/`: PHP dependencies (Composer).
*   `node_modules/`: Frontend dependencies (NPM).

## Core Framework Details

### Routing
Routing is defined in `src/Config/RouteConfig.php`. The `App\Core\Router` supports:
- HTTP Methods: GET, POST
- Dynamic parameters: `{id}`
- Role-based Access Control (RBAC): Middleware-like check in `Router::resolve`.

### Database
Managed by `App\Core\Database` using a Singleton pattern. Configuration is loaded from the `.env` file in the root directory.

### Controllers
Base controller `App\Core\Controller` handles:
- Layout management (Head, Sidebar, Header, Footer).
- Session-based user activity validation.
- Data extraction to views.

### Repositories
Follows the Repository pattern to decouple database logic from controllers. See `src/Repositories/` for examples like `UserRepository`.

## Building and Running

### Prerequisites
- PHP 8.0+
- MySQL
- Composer
- Node.js & NPM

### Setup
1.  Clone the repository.
2.  Install PHP dependencies:
    ```bash
    composer install
    ```
3.  Install NPM dependencies:
    ```bash
    npm install
    ```
4.  Configure environment:
    - Copy `.env.example` to `.env` (if available) or create one with DB credentials.
    - Ensure `APP_URL` and `STORAGE_URL` are set.
5.  Database setup:
    - Import the database schema (look for a `.sql` file in `backups/` or root if provided).

### Development Commands
- **Build CSS:**
    ```bash
    npm run build
    ```
- **Watch CSS changes:**
    ```bash
    npm run watch
    ```

## Development Conventions

- **Naming:** 
    - Controllers: `PascalCaseController.php`
    - Repositories: `PascalCaseRepository.php`
    - Views: Located in `src/Views/`, grouped by role or feature.
- **Routing:** Add new routes in `src/Config/RouteConfig.php`.
- **Database:** Use PDO prepared statements in Repositories to prevent SQL injection.
- **Styling:** Use Tailwind CSS utility classes. Avoid custom CSS unless necessary (modify `public/css/input.css`).
- **JS:** AJAX is heavily used for dynamic updates; look in `public/js/` for client-side logic.
