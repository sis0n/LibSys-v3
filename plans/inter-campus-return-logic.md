# Plan: Inter-Campus Book Return Logic (Handling Duplicate Accession Numbers)

## Objective
Implement a robust return system that correctly identifies books even when `accession_number` is duplicated across different campuses (South, Congress, Camarin, Engineering), and automatically handles the physical transfer of books between campuses.

## The Problem
- The barcode on the back of books contains the `accession_number`.
- `accession_number` is NOT unique across the entire system; multiple campuses may have different books with the same number (e.g., South has `0001` and Congress also has `0001`).
- Scanning a barcode currently makes the system "blind" because it doesn't know which specific copy is being returned if it only looks at the `books` table.

## The Solution: "Borrowing-First" Lookup
Instead of searching the `books` table first, the system will prioritize **Active Transactions**.

### 1. Smart Identification Logic
When a barcode is scanned:
1.  **Search Active Borrowings:** Query `borrow_transaction_items` joined with `books` and `users` where:
    - `accession_number` = [Scanned Value]
    - `status` is 'borrowed' or 'overdue'
2.  **Result Handling:**
    - **Single Match:** If only one person in the entire system has borrowed a book with that number, we have found the correct item.
    - **Multiple Matches:** If two people (e.g., one from South and one from Congress) currently have different books with the same `accession_number`, the system will show a **Selection List** to the librarian. The librarian can then verify the borrower's name/ID from the physical ID card.
    - **No Active Borrowing:** If no one is currently holding that `accession_number`, search the `books` table to show its current status (Available at [Campus Name]).

### 2. Auto-Campus Transfer
When the correct borrowed item is identified:
1.  **Campus Check:** Compare the book's "Home Campus" (`books.campus_id`) with the Librarian's "Current Campus" (`$_SESSION['user_data']['campus_id']`).
2.  **Logic on Return:**
    - If the campuses match, perform a standard return.
    - If they **do not match** (e.g., Congress book returned to South):
        - Mark the transaction as `returned`.
        - Update the book's `availability` to `available`.
        - **IMPORTANT:** Update the book's `campus_id` to the **Current Campus** (South).
        - *Reasoning:* The physical book is now at the South library. Updating the `campus_id` ensures it appears in the South catalog and is available for the next local borrower.

## Implementation Steps

### 1. Backend Changes (PHP)
- **`ReturningRepository::findItemByIdentifier`**: Update the SQL to prioritize active borrowings and include borrower details and campus names.
- **`ReturningController::checkBookStatus`**: Add logic to handle multiple results. If more than one active borrowing is found, return a list instead of a single object.
- **`ReturningRepository::markAsReturned`**: Modify to accept `new_campus_id`. Ensure `UPDATE books SET campus_id = ?, availability = 'available' ...` is executed.

### 2. Frontend Changes (JS/UI)
- **`returning.js`**: 
    - Handle the "Multiple Matches" response by showing a selection modal if needed.
    - In the standard `return-modal`, display a **Transfer Alert** if `book_campus_id != librarian_campus_id`.
- **`returning.php`**: Add a UI component for the "Multiple Borrowers Found" selection list.

## Verification & Testing
1.  **Scenario: Unique Return.** Scan `0001` (Borrowed by Student A). System identifies Student A immediately. Return successful.
2.  **Scenario: Cross-Campus Return.** Scan `0001` (Borrowed from Congress, returned to South). System shows "Cross-Campus Transfer" warning. Upon return, verify book now belongs to South in the database.
3.  **Scenario: Collision.** Scan `0001`. Two students (A and B) have borrowed this number from different campuses. System prompts: "Who is returning this? [Student A] or [Student B]". Librarian selects correct one. Return successful.
