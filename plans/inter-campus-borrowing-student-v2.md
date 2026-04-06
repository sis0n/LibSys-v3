# Plan: Inter-campus Borrowing Support for Student Book Catalog (v2)

Enable students to browse and filter books from all campuses, facilitating inter-campus borrowing, with persistent filtering across pagination and refreshes.

## Objective
Update the student book catalog to show books from all campuses and allow filtering by campus. The selected filter must persist when navigating between pages or refreshing the browser.

## Key Files & Context
- **`src/Repositories/BookCatalogRepository.php`**: Handles database queries for books.
- **`src/Controllers/BookCatalogController.php`**: API endpoint for fetching books.
- **`src/Views/Student/bookCatalog.php`**: Frontend UI for the book catalog.
- **`public/js/student/bookCatalog.js`**: Frontend logic for fetching and rendering books.

## Implementation Steps

### 1. Repository Updates (`src/Repositories/BookCatalogRepository.php`)
- Update `getPaginatedFiltered` to JOIN with the `campuses` table to include `campus_name` in the results.
- Update `getAllBooks` to JOIN with the `campuses` table as well.
- Ensure all relevant queries support an optional `campusId` filter.

### 2. Controller Updates (`src/Controllers/BookCatalogController.php`)
- **`index()`**:
    - Instantiate `CampusRepository`.
    - Fetch all campuses using `getAllCampuses()`.
    - Pass the `campuses` array to the `Student/bookCatalog` view.
- **`fetch()`**:
    - Accept `campus_id` from `$_GET`.
    - Logic:
        - If `campus_id` is "all", set `$campusId = null`.
        - Else if `campus_id` is provided, cast to `(int)`.
        - Else (default), use `$_SESSION['user_data']['campus_id']`.
- **`getAvailableCount()`**:
    - Accept `campus_id` from `$_GET` and pass it to the repository to get campus-specific counts.

### 3. View Updates (`src/Views/Student/bookCatalog.php`)
- **Filters**: Add a new Campus dropdown filter UI next to the "Status" filter.
    - Button ID: `campusDropdownBtn`
    - Menu ID: `campusDropdownMenu`
    - Value Display ID: `campusDropdownValue`
- **Options**: Populate the menu with an "All Campuses" option followed by the list of `$campuses` passed from the controller.
- **Modal**: Inside `bookModalContent`, add a field to display the "Campus" location of the book.

### 4. JavaScript Updates (`public/js/student/bookCatalog.js`)
- **Persistent State**:
    - Initialize `campusValueFilter` and `campusTextFilter` by checking `sessionStorage.getItem('bookCatalogCampus')` and `sessionStorage.getItem('bookCatalogCampusText')`.
    - Default to the user's home campus or "all" if no session exists.
- **Persistence Logic**:
    - In `window.selectCampus(el, id, text)`, save the `id` and `text` to `sessionStorage`.
    - Ensure `loadBooks()` always reads from the current `campusValueFilter`.
- **Dropdown Integration**:
    - Add event listeners for the new campus dropdown button.
    - Implement the `selectCampus` function to update the UI, state, and trigger `loadBooks(1)`.
- **API Integration**:
    - Update `loadBooks()`, `loadBooksInitial()`, and `loadAvailableCount()` to include `campus_id` in the `URLSearchParams`.
- **UI Rendering**:
    - Update `renderBooks()` to display the `campus_name` on each book card (e.g., as a small label or part of the info section).
    - Update `openModal()` to populate the new campus field in the modal.

## Verification & Testing
- **Persistence Check**: Select a specific campus (e.g., "North"), click to "Next Page", and verify the results are still filtered to "North".
- **Refresh Check**: Select a campus, refresh the browser, and verify the filter and dropdown label remain correct.
- **Data Accuracy**: Cross-reference the "Available" count with the books displayed for a specific campus.
- **Cross-Campus Visibility**: Verify that selecting "All Campuses" correctly aggregates books from multiple locations.
