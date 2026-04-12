# Plan: Smart Unified Management Views & Components

Consolidate role-specific management files (Superadmin, Admin, Campus Admin, Librarian) into a single, role-aware architectural pattern to reduce redundancy and improve maintainability.

## Objective
Replace multiple files (e.g., `Superadmin/userManagement.php`, `campus_admin/userManagement.php`) with a single "Smart View" and "Unified JS" that adapts dynamically based on the user's role and permissions.

## 1. Controller Strategy (Unified Entry Point)
Instead of routing to different methods or views based on role, use a single controller method that prepares an "Access Payload".

- **Logic:**
    - Use `RoleHelper` to calculate granular permissions (e.g., `can_edit`, `can_delete`, `is_global`).
    - Pass these flags to a single view path (e.g., `Views/management/users/index.php`).
- **Example Payload:**
    ```php
    $data = [
        'permissions' => [
            'add' => true,
            'edit' => true,
            'delete' => RoleHelper::isSuperadmin($role),
            'export' => RoleHelper::hasGlobalAccess($role)
        ],
        'filters' => [
            'campus_locked' => !RoleHelper::hasGlobalAccess($role),
            'default_campus' => $_SESSION['user_data']['campus_id']
        ]
    ];
    ```

## 2. Smart Unified View (PHP/HTML)
A single template that uses the "Access Payload" to conditionally render UI elements.

- **UI Adaptations:**
    - **Buttons:** Show/Hide "Add", "Delete", or "Import" buttons based on permissions.
    - **Campus Select:** Disable or hide the campus dropdown if the user is locked to their home campus.
    - **Data Columns:** Hide sensitive columns (e.g., "Created By") if not authorized.

## 3. Unified JS Logic (Action-Based)
Consolidate JS files into a single component (e.g., `public/js/management/userManagement.js`) that is role-agnostic.

- **Pattern:** 
    - JS should not check `if (role === 'admin')`. 
    - Instead, check for the presence of elements: `if (document.getElementById('deleteBtn')) { ... }`.
    - Adapt AJAX payloads based on available filters.

## 4. API & Repository Hardening
The "Smart" aspect must be backed by the server to prevent UI-only security.
- **Repository Level:** Always use `getCampusFilter()` to ensure a Librarian never sees data from another campus, even if they try to manipulate the AJAX request.
- **Service Level:** Re-verify `RoleHelper` permissions before performing mutations (Store/Update/Delete).

---

## Implementation Phases

### Phase 1: User Management Prototype [COMPLETED]
1.  **Consolidate Views:** Move shared HTML from `Superadmin/userManagement.php` to a new `management/userManagement/index.php`. [DONE]
2.  **Unified JS:** Merge logic from `superadmin/userManagement.js` and others into a single file. [DONE]
3.  **Controller Refactor:** Update `UserManagementController@index` to serve the unified view with a permission payload. [DONE]
4.  **Cleanup:** Removed redundant role-specific views and JS files. [DONE]

### Phase 2: System-wide Rollout
1.  **Book Management:** Apply the pattern to book catalog and management. [DONE]
2.  **Equipment Management:** Consolidate equipment views. [DONE]
3.  **Reports:** Create a unified report dashboard that filters options based on role. [TODO]

## Benefits
- **DRY (Don't Repeat Yourself):** Drastically reduces the number of files to maintain.
- **Consistency:** Ensures all admin staff see the same UI patterns and UX.
- **Speed:** Faster development of new features since they only need to be implemented once.
- **Security:** Centralizes permission checks, reducing the surface area for bugs.
