# LibSys v3.0 - Campus Integration Roadmap

## 🔴 [PRIORITY 1: CRITICAL & FOUNDATION]
*Essential infrastructure and architectural refactoring.*

### 🏢 Campus Management (CRUD)
- [x] **Dynamic Campus CRUD:** Create a management interface for Superadmins to add, edit, and delete campuses.
- [x] **Campus API Endpoints:** Implement API routes for creating and deleting records in the `campuses` table.
- [x] **Integrity Checks:** Add validation to prevent deleting a campus if it still has books or users assigned to it.

### 🏗️ Architectural Hardening (Service Layer)
- [x] **Service Layer Implementation:** Refactor business logic from Controllers into dedicated Services.
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
- [x] **API Standardization:** Transition existing `api/` routes to a strict RESTful standard (consistent status codes and JSON structure).
- [ ] **Admin Role Consolidation:** Unified `campus_admin` into the `admin` role.
    - [ ] **Global Admin:** `admin` with `campus_id = NULL` (Full system management).
    - [ ] **Local Admin:** `admin` with assigned `campus_id` (Restricted to branch data).
- [ ] **Role Normalization:** Audit and centralize role-checking logic across the entire system.
- [ ] **Global AJAX Interceptor:** Centralize frontend error handling (401/403) and session expiration alerts in a global `api.js`.
- [ ] **Centralized Session Manager:** Move `session_start()` and security flags (HttpOnly, Secure) to a core `SessionService`.

### 📜 Library Policy (Per Campus)
- [x] **Schema Update:** Add `campus_id` to the `library_policies` table to allow per-campus rules.
- [x] **Policy Management:** Update the Library Policy settings page to allow Superadmins to select a campus and set its specific rules (Max books, borrowing duration).
- [x] **Rule Enforcement:** Update borrowing logic to fetch policies based on the user's campus or the book's campus.

### 🗑️ Data Management Refactoring
- [x] **Remove Soft Delete:** Replace `deleted_at` (soft delete) with `is_active` status toggle for Books and Equipment.
- [x] **Remove Restore Module:** Completely remove the 'Restore Books' module.
- [x] **Books Table Cleanup:** Drop the `deleted_at` and `deleted_by` columns from the `books` table.
- [x] **Bulk Delete Approval Workflow:** Implement a request-based bulk delete system for 10+ records.
- [x] **Approval Queue Module:** Create a separate module where approvers (Global Admin/Superadmin) can view/approve requests.


### 🎨 UI & Usability Fixes
- [ ] **Dropdown Z-Index Fix:** Fix the layering issue where book catalog dropdowns appear behind book cards.
- [ ] **Modal Layering Fix:** Ensure Sidebar and Header are correctly covered by the backdrop when a modal is active.

---

## 🟠 [PRIORITY 2: CORE OPERATIONS]
*Improving the daily borrowing and searching experience for students and librarians.*


### 📖 Book-Specific Policies
- [x] **Individual Book Borrowing Duration:** Allow management to override default campus borrowing policy for specific books.
- [x] **Schema Update:** Add `borrowing_duration_override` to the `books` table.
- [x] **Policy Enforcement:** Update borrowing logic to prioritize book-specific duration.

### 📚 Book Catalog Enhancements
- [x] **Inter-campus Warning:** Added a SweetAlert2 confirmation modal when a user attempts to "Add to Cart" a book from a different campus.

### 🎫 QR Borrowing & Scanning
- [ ] **Attendance Overdue Warning:** Check if the student has overdue items during attendance scan and display a "Soft Warning".
- [ ] **Actionable Profile Errors:** Improve the "Incomplete Profile" error message to specify missing fields.
- [ ] **Campus Visibility on Scan:** Update scan results to display student's home campus and book's originating campus.

### 📜 Transaction History
- [ ] **Data Visibility:** Update Repository to include student and item campus information.
- [ ] **Display Details:** Show the student's home campus and the book's campus in the history table.

---

## 🟡 [PRIORITY 3: ADMIN & MANAGEMENT]
*Filtering and management tools for administrators.*

### 👥 User Management
- [ ] **Campus Filtering:** Add a dropdown to filter users by campus in the Admin/Superadmin User Management table.
- [ ] **Role-Based Dropdown Lockdown:** Restrict or disable campus selection dropdowns for **Local Admins** and **Librarians** across all modules.
- [ ] **Bulk Import Alignment:** Ensure `campus_id` is correctly mapped during bulk student/staff imports.
- [ ] **Global Admin "Campus View" Context:** Allow Superadmins and Global Admins to switch their active campus context via the UI to view logs from any branch.
- [ ] **Granular Action Permissions:** Upgrade RBAC to support action-level checks (e.g., "View only" vs "Edit").

### 🛠️ Librarian Controls
- [ ] **Manual Borrowing Campus:** Include campus display/filter in the Manual Borrowing form.
- [ ] **Campus Lockdown (Optional):** Add a toggle to restrict Librarians to only view data from their assigned campus.

---

## 🟢 [PRIORITY 4: FUTURE & LOGISTICS]
*Inter-campus operations and smart features.*

### 🚚 Inter-Campus Logistics
- [ ] **Inter-Campus Transfer Request:** Allow students to request books from other campuses (Transfer Task).
- [ ] **"Return Anywhere" Logic:** Allow students to return books to any campus library.

### 📊 Smart Analytics & Visibility
- [ ] **Campus Capacity Tracker:** Use QR Attendance data to show real-time occupancy.
- [ ] **Campus-specific Reports:** Add campus filters to all report types.
- [ ] **Audit Log Campus Tracking:** Include the campus name in Audit Logs.
- [ ] **Campus-Specific Report Branding:** Update PDF report headers with branch-specific info.
- [ ] **Email Notification Templates:** Transition templates into HTML with UCC branding and branch signatures.

---

## 🔵 [PRIORITY 5: ADVANCED SCALABILITY]
*Performance optimization and high-load hardening.*

### ⚡ Performance & Data
- [ ] **Database Indexing Audit:** Optimize indexes for `status`, `campus_id`, and `role`.
- [ ] **Caching Layer (Redis):** Implement Redis for slow-changing data.
- [ ] **Asynchronous Task Queue:** Background worker for sending emails.
- [ ] **API Rate Limiting:** Implement request throttling.
- [ ] **Database Migration System:** Version-controlled schema management.
- [ ] **Structured Error Logging:** Integrate Monolog for better tracing.

### ☁️ Storage & Infrastructure
- [ ] **Cloud Storage Integration:** Support AWS S3 or Google Cloud for media assets.
- [ ] **Image Optimization Pipeline:** Automatic resizing for book covers.
- [ ] **Centralized Error Tracking:** Integrate Sentry Capturing.
