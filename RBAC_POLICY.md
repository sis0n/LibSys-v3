# UCC Library Management System — Role-Based Access Control (RBAC)

## Roles
- **Superadmin** — Head Librarian (Global Access)
- **Admin** — Assistant Head Librarian (Global, Limited)
- **Campus Admin** — Campus In-charge (Campus Only)
- **Librarian** — Regular Librarian (Campus Only, Limited)

---

## Module Access Matrix

| Module               | Superadmin | Admin | Campus Admin       | Librarian          |
|----------------------|------------|-------|--------------------|--------------------|
| User Management      | ✅ Full    | ✅ Full| ✅ Campus Only   | ❌                 |
| Campus Management    | ✅ Full    | ❌     |❌                | ❌                 |
| Student Promotion    | ✅ Full    | ✅ Full| ✅ Campus Only   | ❌                 |
| Book Management      | ✅ Full    | ✅ Full| ✅ Campus Only   | ✅ Campus Only     |
| Equipment Management | ✅ Full    | ✅ Full| ✅ Campus Only   | ✅ Campus Only     |
| QR Scanner           | ✅ Full    | ✅ Full| ✅ Full          | ✅ Full            |
| Returning            | ✅ Full    | ✅ Full| ✅ Full          | ✅ Full            |
| Borrowing Form       | ✅ Full    | ✅ Full| ✅ Full          | ✅ Full            |
| Attendance Logs      | ✅ Full    | ✅ Full| ✅ Campus Only   | ✅ Campus Only     |
| Overdue Tracking     | ✅ Full    | ✅ Full| ✅ Campus Only   | ✅ Campus Only     |
| Reports              | ✅ Full    | ✅ Full| ✅ Campus Only   | ✅ Campus Only     |
| Transaction History  | ✅ Full    | ✅ Full| ✅ Campus Only   | ✅ Campus Only     |
| Audit Trail          | ✅ Full    | ✅ Full| ❌               | ❌                 |
| Backup               | ✅ Full    | ❌     | ❌               | ❌                 |
| Restore User         | ✅ Full    | ✅ Full| ❌               | ❌                 |
| Bulk Delete Queue    | ✅ Full    | ✅ Full| ✅ campus only   | ❌                 |
| Library Policies     | ✅ Full    | ❌     | ❌               | ❌                 |

---

## Role Descriptions

### Superadmin (Head Librarian)
- Global access sa lahat ng campuses
- Tanging role na pwedeng mag-manage ng Campus Management
- Tanging role na pwedeng mag-backup ng system
- Nakakakita ng lahat ng reports across all campuses
- Pwedeng mag-assign ng modules sa ibang roles

### Admin (Assistant Head Librarian)
- Global access pero limited compared sa Superadmin
- Hindi pwedeng mag-manage ng campuses
- Hindi pwedeng mag-backup ng system
- Nakakakita ng lahat ng reports across all campuses
- Pwedeng mag-access ng Audit Trail

### Campus Admin (Campus In-charge)
- Access sa sariling campus lang
- Pwedeng mag-manage ng users, books, equipment sa sariling campus
- Hindi nakakakita ng ibang campus data
- Hindi pwedeng mag-access ng Audit Trail, Backup, at Restore User
- View only access sa Library Policies

### Librarian (Regular Librarian)
- Access sa sariling campus lang
- Pwedeng mag-manage ng books at equipment sa sariling campus
- Nag-ha-handle ng borrowing at returning transactions
- Hindi pwedeng mag-manage ng users
- View only access sa Library Policies

---

## Campus Data Restriction Rules
- **Superadmin at Admin** — Nakakakita ng lahat ng campus data
- **Campus Admin at Librarian** — Sariling campus lang ang nakikita
- **Gatekeeper Logic** — Pag inactive ang campus, hindi ma-a-access ang lahat ng books, equipment, at users nito

---
