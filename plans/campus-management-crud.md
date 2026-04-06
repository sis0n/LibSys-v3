# Campus Management CRUD Plan

## Overview
Implement a full CRUD (Create, Read, Update, Delete) interface for Managing Campuses, accessible only to Superadmins. This allows dynamic management of campus locations across the system.

## 1. Database & Repository Updates
- [ ] Update `src/Repositories/CampusRepository.php`:
    - Add `getById(int $id)`: Fetch a single campus.
    - Add `create(string $name)`: Insert a new campus record.
    - Add `update(int $id, string $name)`: Update campus details.
    - Add `delete(int $id)`: Remove a campus record.
    - Add `hasDependencies(int $id)`: Check `books` and `users` tables for linked records.

## 2. Controller Implementation
- [ ] Create `src/Controllers/CampusManagementController.php`:
    - `index()`: Load the management view (Superadmin only).
    - `fetch()`: API for the data table.
    - `store()`: Handle creation requests.
    - `update()`: Handle edit requests.
    - `destroy()`: Handle deletion with integrity checks.

## 3. Routing
- [ ] Update `src/Config/RouteConfig.php`:
    - GET `/campusManagement` -> `CampusManagementController@index`
    - GET `/api/superadmin/campuses/fetch` -> `CampusManagementController@fetch`
    - POST `/api/superadmin/campuses/store` -> `CampusManagementController@store`
    - POST `/api/superadmin/campuses/update/{id}` -> `CampusManagementController@update`
    - POST `/api/superadmin/campuses/delete/{id}` -> `CampusManagementController@destroy`

## 4. UI Implementation
- [ ] Create `src/Views/Superadmin/campusManagement.php`:
    - Responsive table with "Edit" and "Delete" actions.
    - "Add Campus" modal.
- [ ] Create `public/js/superadmin/campusManagement.js`:
    - AJAX CRUD operations using Fetch API.
    - SweetAlert2 for success/error notifications and delete confirmation.
- [ ] Update `src/Views/partials/sidebar.php`:
    - Add "Campus Management" link under the Superadmin "Management" dropdown.

## 5. Validation & Integrity
- [ ] Ensure campus names are not empty and are unique.
- [ ] Explicitly block deletion if `hasDependencies()` returns true to prevent orphaned records.
