# Global vs. Local Admin Consolidation Plan

This document outlines the architectural changes and implementation progress for consolidating the `campus_admin` role into the `admin` role, distinguished by `campus_id`.

## 1. Core Logic (Role Definition)
- **Global Admin:** Role is `admin` and `campus_id` is `NULL`.
  - Has system-wide access to all campuses.
  - Can switch views using the "Smart Campus Filter" dropdown.
- **Local Admin:** Role is `admin` and `campus_id` is assigned (e.g., `1` for South).
  - Access is strictly locked to their assigned campus for management modules.
  - Full transactional access for inter-campus borrowing/returning.

---

## 2. Completed Modules (Smart View Implemented)

### ✅ User Management
- **Smart Filter:** Added a Campus Dropdown for Superadmins/Global Admins to switch between campus views.
- **Role Badges:** Logic implemented to display `Global Admin` or `Local Admin` based on `campus_id`.
- **API Support:** `fetchPaginatedUsers` and `search` now prioritize the manual campus filter if the user is privileged.
- **Campus Locking:** Local Admins are automatically locked to their assigned `campus_id`.

### ✅ Book & Equipment Management
- **Repository Integration:** Uses `getCampusFilter()` to automatically list items belonging to the admin's campus.
- **Privileged Access:** Superadmins and Global Admins bypass filters to see the entire system inventory.

### ✅ Transactional Modules (Scanner, Borrowing, Returning)
- **Cross-Campus Logistics:** Removed `campus_id` restrictions on student and item lookups in `ManualBorrowingRepository`.
- **Policy Logic:** Borrowing rules are now fetched based on the *borrower's* campus, allowing students to borrow/return books at any branch regardless of the admin's location.

### ✅ Student Promotion & Restore User
- **RBAC Enforcement:** Restricted access to Superadmin and Global Admin only.
- **Security:** Local Admins receive a `403 Forbidden` error if they attempt to access these system-wide tools.

---

## 3. Pending & Future Tasks

### ⏳ Reports & Analytics
- [ ] Add "Smart Campus Filter" dropdown to the Reports dashboard for Global Admins.
- [ ] Ensure PDF generation respects the selected campus filter.

### ⏳ Attendance Logs
- [ ] Add Campus Filter dropdown for Global Admins to monitor logs across different branches.

### ⏳ Audit Trail (Superadmin Only)
- [ ] Ensure Global Admins are strictly blocked from the Audit Trail (per user directive).

### ⏳ UI/UX Polish
- [ ] Review all management views for Z-index issues with the new dropdowns.
- [ ] Standardize the "Smart Filter" UI component across all modules.

---

## 4. Technical Reference
- **Core Helper:** `src/Core/RoleHelper.php` (`isGlobalAdmin`, `isLocalAdmin`).
- **Base Controller:** `src/Core/Controller.php` (`getCampusFilter`).
- **User Model:** `src/Models/User.php`.
