# Module Unification Template (Smart Unified Management)

This template outlines the standard process for consolidating role-specific modules (Superadmin, Admin, Librarian) into a single, unified codebase that respects Role-Based Access Control (RBAC) and campus-specific data isolation.

---

## 1. Directory Structure Standards

Always use the `management` subdirectories to house shared logic.

- **Views:** `src/Views/management/{moduleName}/index.php`
- **JavaScript:** `public/js/management/{moduleName}.js`
- **Controller:** Single controller (e.g., `OverdueController.php`) handling all roles.

---

## 2. Backend Implementation (Controller)

### A. Automatic Campus Filtering
Use the `getCampusFilter()` method from the base `Controller` to automatically determine if the user should see global data or only their local campus data.

```php
public function getTableData() {
    try {
        // Automatically returns NULL for Superadmin/Global Admin
        // Returns Campus ID for Local Admin/Librarian
        $campusId = $this->getCampusFilter();
        
        $data = $this->service->fetchData($filters, $campusId);
        return $this->jsonResponse(['data' => $data]);
    } catch (Exception $e) {
        return $this->errorResponse($e->getMessage());
    }
}
```

### B. View Loading
The `index()` method should point to the unified view and pass the `currentPage` for sidebar highlighting.

```php
public function index() {
    $this->view('management/{moduleName}/index', [
        'title' => 'Module Title',
        'currentPage' => '{moduleName}'
    ]);
}
```

---

## 3. Routing Configuration (`RouteConfig.php`)

Consolidate multiple role-based routes into unified endpoints.

```php
// ❌ OLD (Role-specific)
$router->get('api/superadmin/overdue/fetch', ...);
$router->get('api/admin/overdue/fetch', ...);

// ✅ NEW (Unified)
$router->get('overdue', 'OverdueController@index', ['overdue tracking']);
$router->get('api/overdue/getTableData', 'OverdueController@getTableData', ['overdue tracking']);
```

---

## 4. Frontend Implementation (`.js`)

### A. API Path Constant
Define the API base at the top of the PHP view or inside the JS file to ensure it hits the correct unified endpoint.

```javascript
const API_BASE = `${BASE_URL_JS}/api/{moduleName}`;
```

### B. Handling API Responses
Ensure the JS can handle the unified `jsonResponse` structure (`{ success: true, data: [...] }`).

```javascript
async function fetchData() {
    const res = await fetch(`${API_BASE}/getTableData`);
    const result = await res.json();
    
    if (result.success) {
        renderTable(result.data);
    } else {
        showErrorToast("Error", result.message);
    }
}
```

---

## 5. Unification Checklist

1.  [ ] **Create Unified View:** Move the best-looking UI (usually the Superadmin version) to `src/Views/management/{module}/index.php`.
2.  [ ] **Create Unified JS:** Move logic to `public/js/management/{module}.js`.
3.  [ ] **Update Controller:** 
    *   Inject `getCampusFilter()` into all data-fetching methods.
    *   Update `index()` to load the new view.
4.  [ ] **Update Routes:** Remove `api/superadmin/...` and `api/admin/...` in favor of `api/{module}/...`.
5.  [ ] **Update Sidebar:** Ensure `src/Views/partials/sidebar.php` uses the correct `RoleHelper` constant and points to the new unified URL.
6.  [ ] **Cleanup:** Delete the old role-specific files:
    *   `src/Views/Admin/{module}.php`
    *   `src/Views/Superadmin/{module}.php`
    *   `public/js/admin/{module}.js`
    *   `public/js/superadmin/{module}.js`

---

## 6. RBAC Reference Table

| Role | `getCampusFilter()` Result | UI Access |
| :--- | :--- | :--- |
| **Superadmin** | `NULL` (All Campuses) | ✅ Full |
| **Global Admin** | `NULL` (All Campuses) | ✅ Full |
| **Local Admin** | `campus_id` (Specific) | ✅ Campus Only |
| **Librarian** | `campus_id` (Specific) | ✅ Campus Only |
