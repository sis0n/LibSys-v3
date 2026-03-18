# LibSys v2.0 - Library Information System (Production Ready)

A comprehensive Library Information System for UCC (University of Caloocan City), featuring book and equipment management, attendance tracking, and borrowing transactions. This version has been hardened for production deployment.

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
*   **Key Features:**
    *   Role-Based Access Control (RBAC)
    *   QR Code Attendance & Borrowing
    *   **Advanced Analytics & PDF Reporting** (Most Borrowed, Top Borrowers, Lost/Damaged)
    *   Email Notifications for Overdue Books

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

## Development Conventions

- **Naming (PSR-4 Standard):** 
    - All core classes **MUST** use `PascalCase.php` for their filenames to ensure compatibility with case-sensitive systems (like Linux) and adhere to PSR-4 autoloading.
    - **Controllers:** `BookManagementController.php`
    - **Models:** `Book.php`
    - **Repositories:** `BookManagementRepository.php`
- **Routing:** Add new routes in `src/Config/RouteConfig.php`. Use short class names (e.g., `BookManagementController@index`) as the Router automatically adds the namespace.
- **Database:** Use PDO prepared statements in Repositories to prevent SQL injection.
- **Styling:** Use Tailwind CSS utility classes. Avoid custom CSS unless necessary (modify `public/css/input.css`).
- **Security:** Sanitize all user input using `$this->getPostData()` and validate all `POST` requests with `$this->validateCsrf()` in the controller.

## Building and Running

### Prerequisites
- PHP 8.0+
- MySQL
- Composer
- Node.js & NPM

### Setup
1.  Clone the repository.
2.  Install PHP dependencies: `composer install`
3.  Install NPM dependencies: `npm install`
4.  Create a `.env` file from the example below and fill in your details.
5.  Database setup:
    - Import the database schema (look for a `.sql` file in `backups/` or root if provided).

### Development Commands
- **Build CSS:** `npm run build`
- **Watch CSS changes:** `npm run watch`

## `.env.example` Configuration
Create a `.env` file in the root directory with the following content:

```env
# ==================================================
# App Settings
# ==================================================
APP_NAME="UCC Lib-Sys"
APP_ENV=local                # Use 'production' on live server
APP_DEBUG=true               # Use 'false' on live server
APP_URL=http://localhost/libsys-v2/public

# ==================================================
# Database Settings
# ==================================================
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=libsys-mobile
DB_USERNAME=root
DB_PASSWORD=

# ==================================================
# Mail Settings (for Overdue Alerts)
# ==================================================
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-google-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@ucc-libsys.com
MAIL_FROM_NAME="${APP_NAME}"
```

## Production Readiness & Deployment

This application has been hardened for production. Key features include:

*   **Error Handling:** The system displays a user-friendly 500 error page for any critical failures. To see detailed error messages during development, set `APP_DEBUG=true` in your `.env` file.
*   **Security Hardening:** `public/index.php` includes security headers (X-Frame, CSP, etc.) and enforces `HttpOnly` session cookies to protect against common web vulnerabilities.
*   **Database Optimization:** Performance indexes have been added to `borrow_transactions`, `borrow_transaction_items`, and `attendance` tables to ensure fast report generation even with large amounts of data.
*   **Deployment Command:** When deploying to a live server, run the following command to optimize the Composer autoloader for speed:
    ```bash
    composer install --no-dev --optimize-autoloader
    ```
