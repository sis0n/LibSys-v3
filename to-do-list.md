# LibSys v3.0 - Campus Integration To-Do List

## 🚀 Immediate Tasks (Requested)

### 📚 Book Catalog
- [ ] **Inter-campus Warning:** In `public/js/student/bookCatalog.js`, add a SweetAlert2 confirmation modal when a user attempts to "Add to Cart" a book from a different campus.
    - *Message:* "Are you sure? This book belongs to [Campus Name]."
- [ ] **Dropdown Z-Index Fix:** Fix the layering issue where book catalog dropdowns (Campus, Sort, Status) appear behind the book cards. Adjust `z-index` in `public/js/student/bookCatalog.js` or related CSS.

### 🎫 QR Borrowing Ticket
- [ ] **Campus Visibility:** In `src/Controllers/QRScannerController.php` (and the corresponding view), display the student's home campus and the specific campus where each book belongs during the scanning process.

### 📜 Transaction History
- [ ] **Campus Information:** Update `src/Repositories/TransactionHistoryRepository.php` to join with the `campuses` table.
- [ ] **Display Details:** In the Transaction History view, display the student's home campus in the "Student Information" section and the book's campus in the "Item Information" section.

### 🎨 UI/UX Improvements
- [ ] **Modal Layering:** Fix the issue where the Sidebar and Header remain highlighted or interactable when a modal (e.g., Book Catalog details, Returning modal) is active. Ensure the backdrop covers all elements except the modal.

---

## 💡 Suggested Campus Features

### 📊 Reports & Analytics
- [ ] **Campus-specific Reports:** Add a campus filter to the Reports page (Circulated Books, Top Borrowers, Most Borrowed) to allow librarians to see data for a specific campus or all campuses.
- [ ] **Dashboard Breakdown:** Update the Dashboard charts to show a breakdown of transactions per campus.

### 👥 User Management
- [ ] **Campus Filter in User List:** Add a dropdown to filter users by campus in the Admin/Superadmin User Management table.
- [ ] **Campus-based Bulk Import:** Ensure the bulk import feature correctly maps the `campus_id` for students, faculty, and staff.

### 🛠️ Librarian Controls
- [ ] **Campus Lockdown:** (Optional) Add a setting to restrict Librarians/Staff to only manage transactions for their assigned campus, while Superadmins can manage all.
- [ ] **Manual Borrowing Campus:** Add a "Campus" display/filter in the Manual Borrowing form to ensure books are being borrowed from the correct location.

### 🏢 Campus Management (New)
- [ ] **Dynamic Campus CRUD:** Create a management interface for Superadmins to add, edit, and delete campuses.
- [ ] **Campus API Endpoints:** Implement API routes for creating and deleting records in the `campuses` table.
- [ ] **Integrity Checks:** Add validation to prevent deleting a campus if it still has books or users assigned to it, or provide a way to reassign them.

### 📜 Library Policy (Per Campus)
- [ ] **Schema Update:** Add `campus_id` to the `library_policies` table to allow per-campus rules.
- [ ] **Policy Management:** Update the Library Policy settings page to allow Superadmins to select a campus and set its specific rules (Max books, borrowing duration).
- [ ] **Rule Enforcement:** Update borrowing logic to fetch policies based on the user's campus or the book's campus (inter-campus borrowing rules).

### 📝 Audit Logs
- [ ] **Campus in Logs:** Include the campus ID/Name in the Audit Logs to track which campus initiated specific actions (e.g., adding a book, processing a return).
