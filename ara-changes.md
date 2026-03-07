# Ara Changes

**Cooperative Management System** — A multi-tenant platform for managing cooperative members, offices, and Personal Data Sheets (PDS) following the Philippine Civil Service Commission (CSC) format.

Built with **Laravel 12**, **Vue 3 + TypeScript**, **Inertia.js**, and **Spatie Permission**.

---

## Role Hierarchy

```
super_admin
    └── coop_sdn_admin        (one per cooperative)
            └── coop_office_admin   (one per branch/office, auto-created)
                    └── member      (cooperative members)
```

---

## Default Seeded Accounts

After running `php artisan migrate --seed`, the following accounts are available:

| Role | Email | Password |
|---|---|---|
| `super_admin` | `superadmin@picto.coop` | `password` |
| `coop_sdn_admin` | `sdnadmin@picto.coop` | `password` |

> **`coop_office_admin` accounts are NOT pre-seeded.** They are created automatically when the SDN admin creates an office (see workflow below).

---

## System Workflow

### 1. Super Admin (`super_admin`)

**URL:** `/super-admin/dashboard`

The super admin is the **system administrator only** — they do not manage cooperative data.

**Responsibilities:**
- **User Management** (`/super-admin/users`) — Create, edit, delete, and assign roles to any user. This is how the first `coop_sdn_admin` account is bootstrapped.
- **Role Permissions** — Fine-tune what each role can do via Spatie Permission.
- **Activity Logs** (`/super-admin/logs`) — Read-only audit trail of all significant system actions.

---

### 2. SDN Admin (`coop_sdn_admin`)

**URL:** `/sdn-admin/dashboard`

The SDN admin is the **primary operational role**. All data they see is scoped to their own cooperative (`sdn_id`).

#### Step 1 — Create Offices

Before any members can join, offices (branches) must be set up.

`/sdn-admin/offices/create`

The form has two sections:
- **Office Details:** name, code, address, self-registration toggle
- **Office Admin Account:** name, email, password (confirmed)

On submit, a **database transaction** runs that:
1. Creates the `Office` record
2. Creates a `User` with the `coop_office_admin` role linked to that office
3. Logs the action to the audit trail
4. Shows a flash message with the new admin's email

The new office admin can log in immediately after this.

#### Step 2 — Monitor PDS Records

`/sdn-admin/pds` — View all Personal Data Sheets across **all offices** in the cooperative.

- **Filter** by office or search by name/email
- **View** any record in full CSC Philippine PDS format (8 sections)
- **Edit** core PDS fields

#### Step 3 — Manage User Accounts

`/sdn-admin/users` — View all user accounts within the cooperative.

- Filter by office, status, or search
- **Suspend** or **Activate** any account

#### Step 4 — Merge Queue

`/sdn-admin/merge-queue` — Review potential duplicate PDS records flagged by the system.

When a member submits their PDS and it closely matches an existing record (same name + DOB, or matching government IDs), the system **does not auto-merge**. Instead it raises a merge queue entry for the SDN admin to review.

**Actions:**
- **Approve** — Marks the source PDS as a confirmed duplicate; transfers all member/user links to the target record
- **Reject** (requires a reason) — Keeps both records as separate, legitimate entries

---

### 3. Office Admin (`coop_office_admin`)

**URL:** `/office-admin/dashboard`

Created automatically when the SDN admin creates an office. Scoped to their single `office_id`.

**Responsibilities:**
- **Office Profile** (`/office-admin/profile`) — View and update the office's own details
- **PDS Management** (`/office-admin/pds`) — View and manage all PDS records for their office
  - **Create PDS manually** — For walk-in or manually enrolled members
  - **Generate Account from PDS** — Creates a login for a member who has a PDS but no account yet; assigns the `member` role

---

### 4. Member (`member`)

**URL:** `/member/dashboard` (only accessible after PDS is complete)

#### Registration & Onboarding Flow

1. **Self-register** (`/register`)
   - If the office has self-registration enabled, a dropdown appears to select the office
   - After email verification, the member is redirected to the member area

2. **Complete PDS** (`/member/complete-pds`) — **mandatory gate**
   - The `EnsurePdsComplete` middleware blocks all other member pages until this is done
   - The member fills in their full Personal Data Sheet
   - On submit, the system runs **duplicate detection**:
     - **Duplicate found** → Member is warned with a side-by-side comparison. They must either confirm they are the same person (queues a merge review for the SDN admin) or confirm they are a different person (saves as a new record)
     - **No duplicate** → PDS is saved, member status becomes `pending`, SDN admin is notified

3. **Member Dashboard** — Shows membership status (`pending` / `active` / `suspended`) and member number once approved

4. **My PDS** (`/member/my-pds`) — View and update their own PDS at any time

---

## Data Flow Summary

```
super_admin
  └─ creates ──────────────→ coop_sdn_admin account

coop_sdn_admin
  └─ creates ──────────────→ Office + coop_office_admin account (one transaction)
  └─ monitors ─────────────→ all PDS records across the cooperative
  └─ resolves ─────────────→ duplicate merge queue
  └─ manages ──────────────→ user accounts (suspend / activate)

coop_office_admin
  └─ manages ──────────────→ PDS records in their office
  └─ can manually enroll ──→ members (create PDS + generate account)

member
  └─ self-registers
  └─ completes PDS ────────→ duplicate check
       ├─ duplicate found ─→ merge queue (SDN admin reviews)
       └─ clean ───────────→ Member record created (pending)
                              SDN admin notified
  └─ after approval ───────→ dashboard + member number
  └─ can update own PDS ───→ anytime
```

---

## Route Guards

| Middleware | Behavior |
|---|---|
| `auth` | Must be logged in |
| `verified` | Email must be verified |
| `role:super_admin` | Spatie role check — 403 if wrong role |
| `role:coop_sdn_admin` | Spatie role check — 403 if wrong role |
| `role:coop_office_admin` | Spatie role check — 403 if wrong role |
| `role:member` | Spatie role check — 403 if wrong role |
| `pds.complete` | Redirects member to `/member/complete-pds` if PDS not yet submitted |
