# LibSys v3.0 - API Documentation

This document provides a comprehensive list of all API endpoints for the Library Information System. All endpoints now follow a standardized JSON structure.

## 🛠️ Global Configuration
- **Base URL:** `http://localhost/LibSys-v3/public`
- **Headers Required:**
    - `Accept: application/json`
    - `Cookie: PHPSESSID=YOUR_SESSION_ID`
    - `X-CSRF-TOKEN: YOUR_CSRF_TOKEN` (for POST requests)

## 📦 Response Structure
All responses follow this flat structure:

**Success Response (200 OK):**
```json
{
    "success": true,
    "message": "Optional success message",
    "data": { ... } // Or other dynamic keys
}
```

**Error Response (400/401/403/500):**
```json
{
    "success": false,
    "message": "Required error message",
    "errors": { ... } // Optional details
}
```

---

## 🔍 1. Public / Guest Endpoints
These endpoints do not require authentication.

| Method | Endpoint | Description |
| :--- | :--- | :--- |
| `GET` | `/api/guest/fetchBooks` | Fetch paginated books for the landing page |

---

## 🛡️ 2. Authentication & Account
| Method | Endpoint | Description |
| :--- | :--- | :--- |
| `POST` | `/api/login` | System login |
| `POST` | `/api/api/change-password` | Update account password |
| `POST` | `/forgot-password/send-otp` | Send OTP to registered email |
| `POST` | `/verify-otp/check` | Verify OTP code |
| `POST` | `/verify-otp/resend` | Resend verification code |
| `POST` | `/reset-password/submit` | Finalize password reset |

---

## 🎓 3. Student / Faculty / Staff (Borrower)
Replace `{role}` with `student`, `faculty`, or `staff`.

| Method | Endpoint | Description |
| :--- | :--- | :--- |
| `GET` | `/api/{role}/attendance/get` | Get attendance history for the logged-in user |
| `GET` | `/api/{role}/qrBorrowingTicket/checkStatus` | Check current ticket status |
| `GET` | `/api/{role}/cart/json` | Get current cart items |
| `POST` | `/api/{role}/cart/add/{id}` | Add book/equipment to cart |
| `POST` | `/api/{role}/cart/remove/{id}` | Remove item from cart |
| `POST` | `/api/{role}/cart/clear` | Clear entire cart |
| `POST` | `/api/{role}/cart/checkout` | Generate borrowing ticket |
| `GET` | `/api/{role}/myprofile/get` | Get profile details |
| `POST` | `/api/{role}/myprofile/update` | Update profile information |
| `GET` | `/api/{role}/borrowing-history/pagination` | Get paginated borrowing history |
| `GET` | `/api/{role}/borrowing-history/stats` | Get borrowing statistics |

---

## 📚 4. Librarian / Admin (Management)
Replace `{role}` with `librarian` or `admin`.

### Books Management
| Method | Endpoint | Description |
| :--- | :--- | :--- |
| `GET` | `/api/{role}/booksmanagement/fetch` | List all books with filters |
| `GET` | `/api/{role}/booksmanagement/details/{id}` | Get specific book info |
| `POST` | `/api/{role}/booksmanagement/add` | Add new book to inventory |
| `POST` | `/api/{role}/booksmanagement/update/{id}` | Update book details |
| `POST` | `/api/{role}/booksmanagement/delete/{id}` | Deactivate/Delete book |
| `POST` | `/api/{role}/booksmanagement/reactivate/{id}` | Reactivate book |
| `POST` | `/api/{role}/booksmanagement/deleteMultiple` | Bulk deactivation |
| `POST` | `/api/{role}/booksmanagement/bulkImport` | CSV Import |

### Equipment & Scanning
| Method | Endpoint | Description |
| :--- | :--- | :--- |
| `GET` | `/api/{role}/equipmentManagement/fetch` | List all equipment |
| `POST` | `/api/{role}/equipmentManagement/store` | Add new equipment |
| `POST` | `/api/{role}/qrScanner/scanTicket` | Scan student QR ticket |
| `POST` | `/api/{role}/qrScanner/borrowTransaction` | Confirm borrowing transaction |

### Returning & Overdue
| Method | Endpoint | Description |
| :--- | :--- | :--- |
| `GET` | `/api/{role}/returning/getTableData` | List overdue items |
| `POST` | `/api/{role}/returning/checkBook` | Verify book for return |
| `POST` | `/api/{role}/returning/markReturned` | Process return transaction |
| `POST` | `/api/{role}/returning/extend` | Extend borrowing duration |

---

## 📊 5. Reports & Analytics (High-Level Roles)
Available for `superadmin`, `admin`, and `campus_admin`.

| Method | Endpoint | Description |
| :--- | :--- | :--- |
| `GET` | `/api/{role}/reports/circulated-books` | Books circulation analytics |
| `GET` | `/api/{role}/reports/top-visitors` | Most frequent library visitors |
| `GET` | `/api/{role}/reports/most-borrowed-books` | Popular books report |
| `GET` | `/api/{role}/reports/getActivityReport` | General system activity logs |
| `GET` | `/api/{role}/dashboard/getData` | Aggregated dashboard stats |
| `POST` | `/api/{role}/reports/generate-report` | Generate PDF report (DomPDF) |

---

## 👑 6. Superadmin Exclusives
| Method | Endpoint | Description |
| :--- | :--- | :--- |
| `GET` | `/api/superadmin/userManagement/pagination` | Paginated user list |
| `POST` | `/api/superadmin/userManagement/add` | Create new user account |
| `POST` | `/api/superadmin/userManagement/toggleStatus/{id}` | Deactivate/Activate user |
| `GET` | `/api/superadmin/campuses/fetch` | List all campuses |
| `POST` | `/api/superadmin/campuses/store` | Create new campus branch |
| `POST` | `/api/superadmin/backup/database/full` | Trigger full database backup |
| `POST` | `/api/superadmin/studentPromotion/promote` | Execute bulk student promotion |
| `GET` | `/api/superadmin/auditLogs/fetch` | View detailed system audit logs |

---

## 📝 Postman Testing Guide
1. **Login in Browser:** Login to your LibSys web app.
2. **Get Session ID:** Press `F12` > Application > Cookies > Copy the value of `PHPSESSID`.
3. **Configure Postman:**
    - Go to Headers tab.
    - Key: `Cookie`, Value: `PHPSESSID=your_copied_value`.
4. **GET Request:** Paste any URL above and press **Send**.
5. **POST Request:** Ensure you include the `csrf_token` in the body (form-data) or as a header.
