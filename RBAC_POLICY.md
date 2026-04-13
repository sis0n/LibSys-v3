# UCC Library Management System — Role-Based Access Control (RBAC)

## Roles
- **Superadmin** — Head Librarian (Global Full Access)
- **Admin** — Assistant Head Librarian / Campus In-charge.
    - *Global Admin*: Global Access (No assigned Campus).
    - *Local Admin*: Campus-specific Access (Assigned to a specific Campus).
- **Librarian** — Regular Librarian (Campus-specific Access).

---

## Module Access Matrix

| Module               | Superadmin | Admin (Global) | Admin (Local)    | Librarian          |
|----------------------|------------|----------------|------------------|--------------------|
| User Management      | ✅ Full    | ✅ Full       | ✅ Campus Only   | ❌                |
| Campus Management    | ✅ Full    | ❌            | ❌               | ❌                |
| Student Promotion    | ✅ Full    | ❌            | ❌               | ❌                |
| Book Management      | ✅ Full    | ✅ Full       | ✅ Campus Only   | ✅ Campus Only    |
| Equipment Management | ✅ Full    | ✅ Full       | ✅ Campus Only   | ✅ Campus Only    |
| QR Scanner           | ✅ Full    | ✅ Full       | ✅ Full          | ✅ Full           |
| Returning            | ✅ Full    | ✅ Full       | ✅ Full          | ✅ Full           |
| Borrowing Form       | ✅ Full    | ✅ Full       | ✅ Full          | ✅ Full           |
| Attendance Logs      | ✅ Full    | ✅ Full       | ✅ Campus Only   | ✅ Campus Only    |
| Overdue Tracking     | ✅ Full    | ✅ Full       | ✅ Campus Only   | ✅ Campus Only    |
| Reports              | ✅ Full    | ✅ Full       | ✅ Campus Only   | ✅ Campus Only    |
| Transaction History  | ✅ Full    | ✅ Full       | ✅ Campus Only   | ✅ Campus Only    |

| Audit Trail          | ✅ Full    | ❌            | ❌               | ❌                |
| Backup               | ✅ Full    | ❌            | ❌               | ❌                |
| Restore User         | ✅ Full    | ✅ Full       | ❌               | ❌                |
| Bulk Delete Queue    | ✅ Full    | ✅ Full       | ✅ Campus Only   | ❌                |
| Library Policies     | ✅ Full    | ❌            | ❌               | ❌                |

---

## Role Descriptions

### Superadmin (Head Librarian)
- Global access sa lahat ng campuses.
- Tanging role na pwedeng mag-manage ng Campus Management at Library Policies.
- Tanging role na pwedeng mag-backup ng system.
- Pwedeng mag-assign ng modules sa ibang roles.

### Admin (Assistant Head Librarian / Campus In-charge)
- **Global Admin**:
    - Nakakakita ng lahat ng data across all campuses.
    - Pwedeng mag-access ng Audit Trail at Restore User features.
    - Assistant ng Head Librarian sa overall operations.
- **Local Admin**:
    - Access sa sariling campus lang (based on assigned `campus_id`).
    - Pwedeng mag-manage ng users, books, at equipment sa sariling campus.
    - Hindi nakakakita ng ibang campus data.

### Librarian (Regular Librarian)
- Access sa sariling campus lang.
- Pwedeng mag-manage ng books at equipment sa sariling campus.
- Nag-ha-handle ng borrowing at returning transactions.
- Hindi pwedeng mag-manage ng users.
- View only access sa data ng sariling campus.

---

## Campus Data Restriction Rules
- **Superadmin at Global Admin** — Walang assigned `campus_id`. Nakakakita ng lahat ng data.
- **Local Admin at Librarian** — May assigned `campus_id`. Ang makikita lang ay data na tumutugma sa kanilang campus.
- **Gatekeeper Logic** — Ang access level ay automatic na nag-aadjust base sa `campus_id` ng logged-in user.

---
