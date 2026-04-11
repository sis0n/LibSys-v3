# Plan: API Standardization

This plan outlines the steps to transition the LibSys v3.0 API routes to a consistent JSON structure and status codes.

## Objective
Standardize all `api/` endpoints to use a predictable JSON envelope and appropriate HTTP status codes, improving maintainability.

## Proposed Standard (Flat Structure)

To ensure consistency across the application without breaking existing frontend logic, we will adopt a flat JSON structure.

### JSON Envelope
All API responses will include these base fields:

**Success Response:**
```json
{
    "success": true,
    "message": "Optional success message",
    "additional_field_1": ...,
    "additional_field_2": ...
}
```

**Error Response:**
```json
{
    "success": false,
    "message": "Required error message",
    "errors": { ... } // Optional: Validation details
}
```

### HTTP Status Codes
Even for web-only apps, using correct HTTP status codes improves debugging and browser handling:
- **200 OK:** Standard success.
- **201 Created:** Successful creation (POST).
- **400 Bad Request:** Validation or client-side errors.
- **401/403:** Authorization issues.
- **500:** Server-side exceptions.

---

## Key Files & Context
- `src/Core/Controller.php`: Add helper methods that return this flat structure.
- `src/Controllers/`: Update controllers to use the new helpers.
- `public/js/`: Update any JS that specifically checks for `data.status === "success"` to also (or instead) check for `data.success`.

---

## Implementation Steps

### Phase 1: Foundation (Core Helpers)
1.  **Modify `src/Core/Controller.php`:**
    - Add `jsonResponse($data, $statusCode)` which merges the data with a default `success => true` and `message => ''` if not present.
    - Add `errorResponse($message, $statusCode, $errors)` for consistent error reporting.

### Phase 2: System-wide Migration
1.  **ScannerController:** Update `attendance()` and `manual()` to use `success: true` alongside the existing data.
2.  **BookManagementController:** Ensure all methods use `success: true/false`.
3.  **Frontend Audit:** Update `attendance.php` and other JS files to prioritize checking the `success` boolean.

---

## Verification & Testing
- **Manual API Testing:** Use a tool like Postman or `curl` to verify response structure and status codes.
- **Frontend Regression:** Thoroughly test each UI module after migration to ensure AJAX calls still process data correctly.
- **Log Monitoring:** Check PHP error logs for any `json_encode` failures or unexpected exceptions during migration.

## Alternatives Considered
- **Strict RESTful (Nested):** Rejected by user as they don't plan to build a mobile app.
- **Keeping the flat structure:** Easier for the frontend but less "RESTful" and harder to manage as the API grows (potential key collisions).
- **Backward Compatibility Layer:** Adding both old and new keys to the JSON. This increases payload size and tech debt. Given the "Priority 1" status of this task, a clean break is recommended.
