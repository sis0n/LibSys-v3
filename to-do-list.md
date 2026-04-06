# LibSys v3.0 - Campus Integration Roadmap

## 🔴 [PRIORITY 1: CRITICAL & FOUNDATION]
*Essential infrastructure and architectural refactoring.*

### 🏢 Campus Management (CRUD)
- [x] **Dynamic Campus CRUD:** Create a management interface for Superadmins to add, edit, and delete campuses.
- [x] **Campus API Endpoints:** Implement API routes for creating and deleting records in the `campuses` table.
- [x] **Integrity Checks:** Add validation to prevent deleting a campus if it still has books or users assigned to it.

### 🏗️ Architectural Hardening (Service Layer)
- [ ] **Service Layer Implementation:** Refactor business logic from Controllers into dedicated Services.
    - [x] `BorrowingService`: Centralize policy checks, overdue validation, and stock updates.
    - [x] `ReturningService`: Calculate fines, handle damaged/lost items, and inventory return.
    - [x] `OverdueService`: Background logic for scanning and flagging overdue transactions.
    - [x] `UserService`: RBAC enforcement, campus-based filtering, and bulk import validation.
    - [x] `AuthService`: Centralized login, session security, and role normalization.
    - [x] `BookService` & `EquipmentService`: Resource management and availability logic.
    - [x] `CampusService`: Multi-campus integrity and validation logic.
    - [x] `NotificationService`: Orchestrate email, SMS, and in-app alerts.
    - [x] `AuditLogService`: Detailed activity tracking with state snapshots.
    - [x] `ReportService`: Data aggregation logic for complex PDF reports.
    - [x] `PromotionService`: Year-level updates and student archiving logic.
    - [x] `TicketService`: QR code generation and validation.
    - [x] `SearchService`: Advanced filtering and cross-campus catalog logic.
    - [x] `DashboardService`: Statistical aggregation and analytics for the dashboard.
    - [x] `CartService`: Reservation rules and inter-campus cart validation.
    - [x] `StorageService`: Centralized file management (Local vs Cloud abstraction).
- [ ] **API Standardization:** Transition existing `api/` routes to a strict RESTful standard (consistent status codes and JSON structure).
- [ ] **Role Normalization:** Audit and centralize role-checking logic (Superadmin vs Super_admin) across the entire system to prevent 403/401 leaks.

### 🛠️ Development Workflow & Quality Assurance
- [ ] **Automated Testing Framework:** Set up PHPUnit for backend logic and a JS testing library (e.g., Jest) to verify Service Layer rules before deployment.
- [ ] **API Documentation (Swagger/OpenAPI):** Implement automated API documentation so that any developer (or future mobile app) knows how to use the Service Layer endpoints.
- [ ] **Enhanced CI/CD Pipeline:** Update GitHub Actions to run automated tests on every push and block deployments if any test fails.

### 📜 Library Policy (Per Campus)
- [x] **Schema Update:** Add `campus_id` to the `library_policies` table to allow per-campus rules.
- [x] **Policy Management:** Update the Library Policy settings page to allow Superadmins to select a campus and set its specific rules (Max books, borrowing duration).
- [x] **Rule Enforcement:** Update borrowing logic to fetch policies based on the user's campus or the book's campus.

### 🗑️ Data Management Refactoring
- [x] **Remove Soft Delete:** Replace `deleted_at` (soft delete) with `is_active` status toggle for Books and Equipment to align with the new Campus Management pattern. User Management will retain soft delete functionality.
- [x] **Remove Restore Module:** Completely remove the 'Restore Books' module as it will be obsolete once soft delete is replaced by the `is_active` toggle.
- [x] **Books Table Cleanup:** Drop the `deleted_at` and `deleted_by` columns from the `books` table after the transition to the `is_active` toggle pattern.
- [x] **Bulk Delete Approval Workflow:** Implement a request-based bulk delete system for 10+ records requiring approval from a higher role.
- [x] **Approval Queue Module:** Create a separate module where approvers can view, approve, or reject pending bulk delete requests with a time-limited approval window.


### 🎨 UI & Usability Fixes
- [ ] **Dropdown Z-Index Fix:** Fix the layering issue where book catalog dropdowns (Campus, Sort, Status) appear behind the book cards in `public/js/student/bookCatalog.js`.
- [ ] **Modal Layering Fix:** Ensure the Sidebar and Header are correctly covered by the backdrop when any modal (Book details, Returning, etc.) is active.

---

## 🟠 [PRIORITY 2: CORE OPERATIONS]
*Improving the daily borrowing and searching experience for students and librarians.*


### 📖 Book-Specific Policies
- [x] **Individual Book Borrowing Duration:** Allow management to override the default campus borrowing policy for specific books (e.g., change a 3-day limit to 2 days for high-demand books).
- [x] **Schema Update:** Add `borrowing_duration_override` or similar column to the `books` table.
- [x] **Policy Enforcement:** Update borrowing logic to prioritize book-specific duration before falling back to campus-wide `library_policies`.

### 📚 Book Catalog Enhancements
- [ ] **Inter-campus Warning:** In `public/js/student/bookCatalog.js`, add a SweetAlert2 confirmation modal when a user attempts to "Add to Cart" a book from a different campus.
    - *Message:* "Are you sure? This book belongs to [Campus Name]."

### 🎫 QR Borrowing & Scanning
- [ ] **Attendance Overdue Warning:** In `src/Controllers/ScannerController.php`, check if the student has overdue items during an attendance scan and display a "Soft Warning" badge in the SweetAlert.
- [ ] **Actionable Profile Errors:** Improve the "Incomplete Profile" error message in the scanner to specify which fields are missing (Course/Year) and where the student can update them.
- [ ] **Profile Requirement Audit:** Review mandatory fields in `ScannerController.php`. Ensure critical data (Course, Year) blocks attendance for report accuracy, but evaluate if non-critical missing data (e.g., Profile Picture) should still allow entry.
- [ ] **Campus Visibility on Scan:** In `src/Controllers/QRScannerController.php`, update the scan result to display the student's home campus and the specific campus where each item in the ticket belongs.

### 📜 Transaction History
- [ ] **Data Visibility:** Update `src/Repositories/TransactionHistoryRepository.php` to include student and item campus information.
- [ ] **Display Details:** Show the student's home campus and the book's campus in the transaction history table/details.

---

## 🟡 [PRIORITY 3: ADMIN & MANAGEMENT]
*Filtering and management tools for administrators.*

### 👥 User Management
- [ ] **Campus Filtering:** Add a dropdown to filter users by campus in the Admin/Superadmin User Management table.
- [ ] **Role-Based Dropdown Lockdown:** Restrict or disable campus selection dropdowns for `campus_admin` and `librarian` roles across all modules (User Management, Books, Reports). If a user is restricted to one campus, the dropdown should be locked to their assigned campus to prevent "empty" states from invalid selections.
- [ ] **Bulk Import Alignment:** Ensure `campus_id` is correctly mapped during bulk student/staff imports.

### 🛠️ Librarian Controls
- [ ] **Manual Borrowing Campus:** Include campus display/filter in the Manual Borrowing form to clarify where books are being pulled from.
- [ ] **Campus Lockdown (Optional):** Add a toggle to restrict Librarians to only view/manage data from their assigned campus.

---

## 🟢 [PRIORITY 4: FUTURE & LOGISTICS]
*Inter-campus operations and smart features.*

### 🚚 Inter-Campus Logistics
- [ ] **Inter-Campus Transfer Request:** Allow students to request books from other campuses, creating a "Transfer Task" for librarians to move physical books between branches.
- [ ] **"Return Anywhere" Logic:** Allow students to return books to any campus library; the system will automatically update the book's current physical location.

### 📊 Smart Analytics & Visibility
- [ ] **Campus Capacity Tracker:** Use QR Attendance data to show a "Real-time Occupancy" percentage for each campus library on the dashboard.
- [ ] **Campus-specific Reports:** Add campus filters to all report types (Most Borrowed, Top Visitors, etc.).
- [ ] **Audit Log Campus Tracking:** Include the campus name in Audit Logs to see which branch initiated an action.

---

## 🔵 [PRIORITY 5: ADVANCED SCALABILITY]
*Performance optimization and high-load hardening.*

### ⚡ Performance & Data
- [ ] **Database Indexing Audit:** Full audit to ensure all frequently filtered columns (`status`, `campus_id`, `role`) have optimized indexes.
- [ ] **Caching Layer (Redis):** Implement Redis to cache slow-changing data like Library Policies and User Permissions.
- [ ] **Asynchronous Task Queue:** Set up a background worker for non-blocking tasks like sending overdue emails.
- [ ] **API Rate Limiting:** Implement request throttling to protect the server from abuse or accidental DoS attacks.

### ☁️ Storage & Infrastructure
- [ ] **Cloud Storage Integration:** Support AWS S3 or Google Cloud Storage for media assets.
- [ ] **Image Optimization Pipeline:** Automatic resizing and compression for book covers on upload.
- [ ] **Centralized Error Tracking:** Integrate Sentry for real-time production error capturing.
