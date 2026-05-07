# Plan: Standardizing Paths and URLs

## Objective
Eliminate hardcoded file paths and URLs across the application by consistently using dynamic constants derived from environment configuration (`.env` and `APP_URL`). This will improve portability, maintainability, and ensure the application works correctly across different deployment environments (like local development vs. Hostinger).

## Strategy
1.  **Define a Robust `ROOT_PATH` Constant:** Ensure a consistent constant that points to the application's root directory on the filesystem. This is crucial for file operations (includes, uploads, cache).
2.  **Standardize `BASE_URL`:** All public-facing URLs (assets, API endpoints, redirects) will be constructed using a `BASE_URL` constant, which should be dynamically derived from the `APP_URL` defined in the `.env` file.
3.  **Refactor Hardcoded Strings:** Systematically find and replace explicit paths and URLs with references to these constants.
4.  **Address `.htaccess`:** Ensure `RewriteBase` is correctly configured based on the `APP_URL` or deployment structure.

## Implementation Steps

### Phase 1: Foundation & Configuration
1.  **Review `public/index.php`:**
    *   Ensure `ROOT_PATH` is correctly defined (e.g., `define('ROOT_PATH', dirname(__DIR__));` if `public` is a subfolder of the root).
    *   Ensure `BASE_URL` is correctly derived from `$_ENV['APP_URL']` and handles potential missing values or trailing slashes.
    *   Check `STORAGE_URL` definition for consistency with `BASE_URL`.
2.  **Review `src/Core/Controller.php`:**
    *   Verify usage of `ROOT_PATH` for file system operations (e.g., uploads).
    *   Ensure any hardcoded public paths are updated to use `BASE_URL`.
3.  **Update `.htaccess`:**
    *   Make the `RewriteBase` directive dynamic or relative to the server's document root, ideally derived from `APP_URL` or project structure.

### Phase 2: System-wide Refactoring
1.  **Search for Hardcoded Paths/URLs:**
    *   Use search tools (like `grep_search`) to find instances of:
        *   Absolute file paths (e.g., `C:\...`, `/var/...`).
        *   Explicit URLs like `'http://localhost/...'` or `'/assets/...'`.
        *   Potentially problematic `__DIR__ . '/../...'` constructions that could be replaced by `ROOT_PATH`.
2.  **Refactor PHP Files:**
    *   Replace identified hardcoded paths with `ROOT_PATH`.
    *   Replace identified hardcoded URLs with `BASE_URL`.
3.  **Refactor JavaScript Files:**
    *   Update any JS code that uses hardcoded paths for assets or API endpoints to use `BASE_URL` or a dynamically generated path.
4.  **Review Build Scripts:**
    *   Check `package.json` for paths related to Tailwind CSS or other build processes. Relative paths here are usually fine, but verify they are not absolute and server-dependent.

## Verification & Testing
-   **Local Testing:** Run the application locally and test all features, especially file uploads, asset loading, and routing.
-   **Staging Deployment:** Deploy to a staging server and test thoroughly.
-   **Production Deployment:** Deploy to Hostinger and verify all features, including routing, asset loading, and file operations.
-   **Check Logs:** Monitor PHP error logs for any path-related errors after deployment.

## Notes
-   The `APP_URL` in `.env` is the primary source of truth for the application's base URL.
-   `ROOT_PATH` should point to the absolute filesystem path of the project's root directory.
