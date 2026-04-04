# LibSys v3.0 - Campus Integration Roadmap

## 🔴 [PRIORITY 1: CRITICAL & FOUNDATION]
*Essential infrastructure to make the multi-campus logic work correctly.*

### 🏢 Campus Management (CRUD)
- [x] **Dynamic Campus CRUD:** Create a management interface for Superadmins to add, edit, and delete campuses.
- [x] **Campus API Endpoints:** Implement API routes for creating and deleting records in the `campuses` table.
- [x] **Integrity Checks:** Add validation to prevent deleting a campus if it still has books or users assigned to it.

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
*Features to make the system more intelligent and scalable.*

### 🚚 Inter-Campus Logistics
- [ ] **Inter-Campus Transfer Request:** Allow students to request books from other campuses, creating a "Transfer Task" for librarians to move physical books between branches.
- [ ] **"Return Anywhere" Logic:** Allow students to return books to any campus library; the system will automatically update the book's current physical location.

### 📊 Smart Analytics & Visibility
- [ ] **Campus Capacity Tracker:** Use QR Attendance data to show a "Real-time Occupancy" percentage for each campus library on the dashboard.
- [ ] **Campus-specific Reports:** Add campus filters to all report types (Most Borrowed, Top Visitors, etc.).
- [ ] **Audit Log Campus Tracking:** Include the campus name in Audit Logs to see which branch initiated an action.
