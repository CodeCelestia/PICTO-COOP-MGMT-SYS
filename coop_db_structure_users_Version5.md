# User Accounts & Access Control — PCMIS

> **Module:** User Account Management & Role-Based Access Control (RBAC)
> **System:** Provincial Cooperative Management Information System (PCMIS)
> **Last Updated:** 2026-03-09
> **Purpose:** Defines who can access the system, what they can do, and
> tracks all login/session activity for accountability and security.

---

## Table of Contents

1. [System Users / Accounts](#1-system-users--accounts)
2. [Roles / User Types](#2-roles--user-types)
3. [Role Permissions Matrix](#3--role-permissions-matrix)
4. [User-Role Assignment](#4--user-role-assignment-junction-table)
5. [User-Coop Assignment](#5--user-coop-assignment-relational-table)
6. [Login / Session History](#6--login--session-history-dynamic)
7. [Password Reset Log](#7--password-reset-log-dynamic)
8. [Account Status History](#8--account-status-history-dynamic)
9. [Access Control Summary](#access-control-summary)
10. [Who Can Access What — Quick Reference](#who-can-access-what--quick-reference)

---

## 1. System Users / Accounts

> **📋 Description:** The master table of all system user accounts —
> whether they are provincial administrators, cooperative officers,
> committee members, or regular members with limited view access.
>
> **🎯 Purpose:** To manage authentication credentials and link every
> system user to their real identity (Member or Officer) and their
> assigned cooperative. Serves as the root table for the entire
> access control system.

| Field             | Type      | Description                                                              |
|-------------------|-----------|--------------------------------------------------------------------------|
| UserID            | INT (PK)  | Unique System User Identifier                                            |
| CoopID            | INT (FK)  | References CoopMasterProfile (nullable for provincial admin)             |
| MemberID          | INT (FK)  | References Members Profile (nullable if not a member)                    |
| OfficerID         | INT (FK)  | References Officers & Committees (nullable if not an officer)            |
| Username          | VARCHAR   | Unique login username                                                    |
| Email             | VARCHAR   | Account email address (used for notifications and password reset)        |
| PasswordHash      | VARCHAR   | Hashed password (never store plain text)                                 |
| FullName          | VARCHAR   | Display name of the user                                                 |
| AccountType       | ENUM      | Provincial Admin, Coop Admin, Officer, Committee Member, Member, Viewer  |
| AccountStatus     | ENUM      | Active, Inactive, Suspended, Locked, Pending Approval                    |
| ProfilePhoto      | VARCHAR   | File path or URL of profile photo (nullable)                             |
| LastLoginAt       | TIMESTAMP | Timestamp of most recent successful login                                |
| PasswordChangedAt | TIMESTAMP | Timestamp of last password change                                        |
| CreatedBy         | VARCHAR   | Admin who created the account                                            |
| CreatedAt         | TIMESTAMP | Account creation timestamp                                               |
| UpdatedAt         | TIMESTAMP | Last update timestamp                                                    |

---

## 2. Roles / User Types

> **📋 Description:** Defines the named roles available in the system —
> each role represents a level of access and a set of permitted actions.
>
> **🎯 Purpose:** To implement Role-Based Access Control (RBAC) — ensuring
> that each user only sees and does what their role permits. Roles can be
> assigned to users via the User-Role Assignment table.

| Field       | Type      | Description                                                      |
|-------------|-----------|------------------------------------------------------------------|
| RoleID      | INT (PK)  | Unique Role Identifier                                           |
| RoleName    | VARCHAR   | Name of the role (e.g., Provincial Admin, Coop Admin, Member)    |
| Description | TEXT      | What this role is allowed to do                                  |
| Level       | INT       | Hierarchy level (1 = highest/admin, 5 = lowest/view-only)        |
| IsActive    | BOOLEAN   | Whether this role is currently enabled                           |
| CreatedAt   | TIMESTAMP | Record creation timestamp                                        |

### Predefined Roles

| RoleID | RoleName               | Level | Description                                                                 |
|--------|------------------------|-------|-----------------------------------------------------------------------------|
| 1      | Provincial Admin       | 1     | Full system access — manage all coops, users, reports, settings             |
| 2      | Coop Admin             | 2     | Full access within their assigned cooperative only                          |
| 3      | Officer                | 3     | Can view/edit activities, members, and finances within their coop            |
| 4      | Committee Member       | 4     | Can view and submit reports related to their committee only                  |
| 5      | Member                 | 5     | Can view their own profile, services availed, and training records           |
| 6      | Viewer / Read-Only     | 6     | Read-only access to assigned modules (e.g., external auditors, guests)       |

---

## 3. 🔄 Role Permissions Matrix

> **📋 Description:** Defines exactly what each role can do per module/table
> in the system — using Create (C), Read (R), Update (U), Delete (D) permissions.
>
> **🎯 Purpose:** To enforce granular access control per module. This table
> is referenced at runtime to determine whether a logged-in user has
> permission to perform a specific action on a specific module.

| Field        | Type      | Description                                                          |
|--------------|-----------|----------------------------------------------------------------------|
| PermissionID | INT (PK)  | Unique Permission Record ID                                          |
| RoleID       | INT (FK)  | References Roles / User Types                                        |
| ModuleName   | VARCHAR   | Name of the module/table (e.g., Members, Finance, Training, Reports) |
| CanCreate    | BOOLEAN   | Permission to create/add new records                                 |
| CanRead      | BOOLEAN   | Permission to view/read records                                      |
| CanUpdate    | BOOLEAN   | Permission to edit/update existing records                           |
| CanDelete    | BOOLEAN   | Permission to delete records                                         |
| CanExport    | BOOLEAN   | Permission to export/download data (e.g., PDF, Excel)               |
| CanApprove   | BOOLEAN   | Permission to approve submitted records or requests                  |
| CreatedAt    | TIMESTAMP | Record creation timestamp                                            |

### Sample Permissions Matrix

| Module                  | Provincial Admin | Coop Admin | Officer | Committee Member | Member | Viewer |
|-------------------------|:----------------:|:----------:|:-------:|:----------------:|:------:|:------:|
| Coop Master Profile     | CRUD + Export    | R + U      | R       | R                | —      | R      |
| Members Profile         | CRUD + Export    | CRUD       | R + U   | R                | R (own)| R      |
| Officers & Committees   | CRUD             | CRUD       | R + U   | R                | R      | R      |
| Activities & Projects   | CRUD + Export    | CRUD       | CRUD    | R + C            | R      | R      |
| Financial & Support     | CRUD + Export    | CRUD       | R       | —                | —      | R      |
| Training & Capacity     | CRUD + Export    | CRUD       | CRUD    | R + C            | R (own)| R      |
| User Accounts           | CRUD             | C + R + U  | —       | —                | R (own)| —      |
| Audit Logs              | R                | —          | —       | —                | —      | —      |
| Reports & Dashboard     | Full             | Coop-only  | Coop-only| Committee-only  | Own    | R      |

> Legend: **C** = Create, **R** = Read, **U** = Update, **D** = Delete | **—** = No Access | **own** = Own record only

---

## 4. 🔄 User-Role Assignment *(Junction Table)*

> **📋 Description:** Links system users to their assigned roles.
> A user can have more than one role (e.g., a Coop Admin who is also
> a Committee Member in a different capacity).
>
> **🎯 Purpose:** To flexibly assign and manage multi-role access for
> users — supporting the scenario where one person holds multiple
> responsibilities within or across cooperatives.

| Field        | Type      | Description                                              |
|--------------|-----------|----------------------------------------------------------|
| AssignmentID | INT (PK)  | Unique Assignment Record ID                              |
| UserID       | INT (FK)  | References System Users / Accounts                       |
| RoleID       | INT (FK)  | References Roles / User Types                            |
| AssignedBy   | VARCHAR   | Admin who assigned the role                              |
| AssignedAt   | DATE      | Date the role was assigned                               |
| ExpiresAt    | DATE      | Role expiry date (nullable if permanent)                 |
| Status       | ENUM      | Active, Expired, Revoked                                 |
| Remarks      | TEXT      | Reason for role assignment or revocation                 |

---

## 5. 🔄 User-Coop Assignment *(Relational Table)*

> **📋 Description:** Defines which cooperative(s) a user has access to.
> Especially important for Provincial Admins who may oversee multiple
> cooperatives, or for users with cross-coop roles.
>
> **🎯 Purpose:** To enforce coop-level data isolation — ensuring that
> Coop Admins and Officers can only access data belonging to their
> assigned cooperative(s), while Provincial Admins retain system-wide access.

| Field        | Type      | Description                                              |
|--------------|-----------|----------------------------------------------------------|
| CoopAccessID | INT (PK)  | Unique Record ID                                         |
| UserID       | INT (FK)  | References System Users / Accounts                       |
| CoopID       | INT (FK)  | References CoopMasterProfile                             |
| AccessLevel  | ENUM      | Full Access, View Only, Report Only                      |
| AssignedBy   | VARCHAR   | Admin who granted the access                             |
| AssignedAt   | DATE      | Date access was granted                                  |
| ExpiresAt    | DATE      | Access expiry (nullable if permanent)                    |
| Status       | ENUM      | Active, Revoked, Expired                                 |
| Remarks      | TEXT      | Notes on access grant                                    |

---

## 6. 🔄 Login / Session History *(Dynamic)*

> **📋 Description:** A real-time log of all user login attempts —
> both successful and failed — including session duration, IP address,
> and device information.
>
> **🎯 Purpose:** To monitor system access patterns, detect unauthorized
> access attempts, enforce security policies, and provide a full
> audit trail of who accessed the system, when, and from where.

| Field        | Type      | Description                                                    |
|--------------|-----------|----------------------------------------------------------------|
| SessionID    | INT (PK)  | Unique Session Record ID                                       |
| UserID       | INT (FK)  | References System Users / Accounts                             |
| LoginAt      | TIMESTAMP | Timestamp of login attempt                                     |
| LogoutAt     | TIMESTAMP | Timestamp of logout (nullable if session is still active)      |
| IPAddress    | VARCHAR   | IP address of the login device                                 |
| DeviceInfo   | VARCHAR   | Browser, OS, or device name (User-Agent string)                |
| LoginStatus  | ENUM      | Success, Failed, Locked Out                                    |
| FailReason   | VARCHAR   | Reason for failure (e.g., Wrong Password, Account Locked)      |
| SessionToken | VARCHAR   | Unique session token (for active session management)           |

---

## 7. 🔄 Password Reset Log *(Dynamic)*

> **📋 Description:** Tracks all password reset requests and completions
> — whether initiated by the user (via forgot password) or by an
> administrator.
>
> **🎯 Purpose:** To maintain an auditable history of password reset
> events for security and support purposes — detecting suspicious
> reset patterns and ensuring compliance with account security policies.

| Field       | Type      | Description                                              |
|-------------|-----------|----------------------------------------------------------|
| ResetID     | INT (PK)  | Unique Reset Record ID                                   |
| UserID      | INT (FK)  | References System Users / Accounts                       |
| RequestedAt | TIMESTAMP | When the reset was requested                             |
| RequestedBy | VARCHAR   | User themselves or Admin who initiated the reset         |
| ResetMethod | ENUM      | Email Link, Admin Reset, OTP                             |
| Status      | ENUM      | Pending, Completed, Expired, Cancelled                   |
| CompletedAt | TIMESTAMP | When the reset was completed (nullable if still pending) |
| IPAddress   | VARCHAR   | IP address of the reset request                          |
| Remarks     | TEXT      | Additional notes                                         |

---

## 8. 🔄 Account Status History *(Dynamic)*

> **📋 Description:** Logs every status change to a user account —
> (e.g., Active → Suspended → Active, or Pending Approval → Active).
>
> **🎯 Purpose:** To maintain a full audit trail of account lifecycle
> events — who approved, suspended, or deactivated an account, and why.
> Supports HR and governance accountability within the system.

| Field           | Type      | Description                                         |
|-----------------|-----------|-----------------------------------------------------|
| AcctHistoryID   | INT (PK)  | Unique Record ID                                    |
| UserID          | INT (FK)  | References System Users / Accounts                  |
| PreviousStatus  | ENUM      | Status before the change                            |
| NewStatus       | ENUM      | Status after the change                             |
| ChangeReason    | TEXT      | Reason for the status change                        |
| ChangedBy       | VARCHAR   | Admin who made the change                           |
| ChangedAt       | TIMESTAMP | Timestamp of the change                             |
| Remarks         | TEXT      | Additional notes                                    |

---

## Access Control Summary

```
SystemUsers (Table 1)
    ├── Linked to MemberID ──────────────────── Members Profile (Table 2)
    ├── Linked to OfficerID ─────────────────── Officers & Committees (Table 3)
    ├── Linked to CoopID ────────────────────── CoopMasterProfile (Table 1-main)
    │
    ├── User-Role Assignment (Many:Many) ─────── Table 4
    │       └── References Roles ────────────── Table 2-roles
    │               └── Role Permissions ─────── Table 3-perms
    │
    ├── User-Coop Assignment (Many:Many) ─────── Table 5
    │
    ├── Login / Session History (1:Many) ─────── Table 6
    ├── Password Reset Log (1:Many) ──────────── Table 7
    └── Account Status History (1:Many) ─────── Table 8
```

---

## Who Can Access What — Quick Reference

| User Type          | Can Register? | Approves Account? | Scope of Access              |
|--------------------|:-------------:|:-----------------:|------------------------------|
|Super admin         | The creator   | self             | Everything                    |
| Provincial Admin   | Admin creates | Self or Super admin | All cooperatives, all modules|
| Coop Admin         | Admin creates | Provincial Admin  | Own coop only, all modules   |
| Officer            | Admin creates | Coop Admin        | Own coop, limited modules    |
| Committee Member   | Admin creates | Coop Admin        | Own committee module only    |
| Member             | Self-register | Coop Admin        | Own profile & records only   |
| Viewer / Read-Only | Admin creates | Provincial Admin  | Assigned modules, read only  |

---

## Account Creation Workflow

```
[Member/Officer] ──► Self-Register or Admin Creates Account
        │
        ▼
[Pending Approval] ──► Coop Admin or Provincial Admin Reviews
        │
        ├── APPROVED ──► Account Status = Active
        │                    └── Role Assigned ──► Permissions Applied
        │
        └── REJECTED ──► Account Status = Inactive + Reason Logged
```

---

*Last Updated: 2026-03-09 | Provincial Cooperative Management Information System (PCMIS)*