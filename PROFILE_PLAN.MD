# Plan: Local Storage Migration for Student Profile (PROFILE_PLAN.MD)

## Objective
Migrate the storage of Student Profile pictures and Registration Forms from the external Laravel backend to the local `LibSys-v3` filesystem.

This change allows the application to handle file uploads independently without relying on the Laravel API, while ensuring existing files still load correctly.

## 1. Directory Structure
We will create a local storage structure within the `public` directory.

**Target Locations:**
- `public/storage/uploads/profile/` (for profile pictures)
- `public/storage/uploads/reg_form/` (for registration PDFs)

## 2. Implementation Strategy (Hybrid Approach)
Since `STORAGE_URL` is used globally across the app (pointing to the Laravel backend), changing it immediately might break other features (like Book Covers or QR Codes) that still reside on the remote server.

**We will implement a "Hybrid" logic:**
1.  **New Uploads:** Save directly to the local `public/storage/uploads` folder.
2.  **Existing Files:** Continue serving them from the Laravel backend (`STORAGE_URL`) until they are updated.
3.  **Frontend Display:** Update the JavaScript to intelligently decide whether to use the local path or the remote `STORAGE_URL`.

## 3. Detailed Steps

### Step 1: Create Directories
Ensure the following directories exist and are writable:
- `C:\xampp\htdocs\LibSys-v3\public\storage\uploads\profile`
- `C:\xampp\htdocs\LibSys-v3\public\storage\uploads\reg_form`

### Step 2: Update Backend (`StudentProfileController.php`)
Modify the controller to handle file saving locally instead of sending CURL requests to the API.

- **Remove:** `uploadToBackendAPI()` method.
- **Add:** `saveFileLocally($file, $subFolder)` method.
    - Generate a unique filename: `profile_{uniqid}.{ext}`.
    - Move file to: `public/storage/uploads/{subFolder}/`.
    - Return path: `storage/uploads/{subFolder}/{filename}`.
- **Update:** `updateProfile()` to use `saveFileLocally`.

### Step 3: Update Frontend (`public/js/student/myProfile.js`)
The current JS logic aggressively prepends `window.STORAGE_URL` to *all* paths. We need to modify this.

**Logic Change:**
- Check if the file path returned by the database starts with `storage/uploads/`.
    - **If YES:** Treat as local. Use `window.location.origin + '/LibSys-v3/public/' + path` (or a relative path).
    - **If NO:** Treat as remote. Keep using `window.STORAGE_URL + '/' + path`.

### Step 4: Verification
1.  **Test Upload:** Upload a new profile picture.
2.  **Verify File:** Check if the file exists in `public/storage/uploads/profile`.
3.  **Verify DB:** Check if the database stores the new path (e.g., `storage/uploads/profile/xyz.jpg`).
4.  **Verify Display:** Refresh the page to ensure the new local image loads.
5.  **Regression Test:** Check if an *old* profile (still on Laravel) continues to load correctly.

