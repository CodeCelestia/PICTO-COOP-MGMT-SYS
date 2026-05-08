# PICTO Cooperative Management System - System Architecture

> **Last Updated:** May 8, 2026  
> **Purpose:** Comprehensive system architecture overview with focus on role hierarchy and Finance module organization

---

## Table of Contents

1. [System Overview](#system-overview)
2. [Role Hierarchy](#role-hierarchy)
3. [Finance Module Architecture](#finance-module-architecture)
4. [Role-Based Access Control (RBAC)](#role-based-access-control)
5. [Finance Permissions Matrix](#finance-permissions-matrix)
6. [Data Scoping Patterns](#data-scoping-patterns)

---

## System Overview

The **PICTO Cooperative Management Information System** is a Laravel-based multi-tenant cooperative management platform with the following key characteristics:

- **Multi-Role Support**: 9 predefined roles with hierarchical access levels
- **Multi-Tenant Architecture**: System-wide view for Super Admin & Provincial Admin; Cooperative-scoped data for lower roles
- **Modular Design**: Organized into core business modules
- **Dual Data Routes**: Global routes (`/finance/*`) + Per-coop routes (`/cooperatives/{cooperative}/finance/*`)
- **Permission-Based Access**: Fine-grained Spatie Laravel Permissions framework

---

## Role Hierarchy

The system uses a 9-level role hierarchy (Level 0 = highest authority):

```
┌─────────────────────────────────────────────────────────────┐
│                    ROLE HIERARCHY DIAGRAM                    │
└─────────────────────────────────────────────────────────────┘

Level 0  ┌──────────────────────────────────────┐
         │       SUPER ADMIN                    │  ← Full system access
         │ Complete control over all provinces  │    across all cooperatives
         │ and cooperatives                     │
         └──────────────────────────────────────┘
                           ▼
Level 1  ┌──────────────────────────────────────┐
         │    PROVINCIAL ADMIN                  │  ← Manage all coops,
         │ Full system access — manage all      │    users, reports
         │ coops, users, reports, settings      │    (no system settings)
         └──────────────────────────────────────┘
                           ▼
         ┌─────────────────┬─────────────────┐
         │                 │                 │
Level 2  │    COOP ADMIN   │  CHAIRPERSON    │  ← Full access within
         │ (Admin level)   │  (Leadership)   │    assigned cooperative
         │                 │                 │
         └────────┬────────┴────────┬────────┘
                  │                 │
         ┌────────▼────────┐  ┌─────▼──────────┐
Level 3  │ GENERAL MANAGER │  │     OFFICER    │  ← Operational roles
         │(Operations)     │  │ (Field-level)  │    within cooperative
         └────────┬────────┘  └─────┬──────────┘
                  │                 │
         ┌────────▼─────────────────▼────────┐
Level 4  │     COMMITTEE MEMBER              │  ← Committee-specific
         │ (View/submit reports for         │    operations
         │  assigned committee)              │
         └────────┬─────────────────────────┘
                  │
         ┌────────▼─────────────────────────┐
Level 5  │           MEMBER                 │  ← Self-service
         │ (View own profile & services)    │    member access
         └────────┬─────────────────────────┘
                  │
         ┌────────▼─────────────────────────┐
Level 6  │          VIEWER                  │  ← Read-only access
         │ (Read-only for auditors, guests) │    (external auditors)
         └─────────────────────────────────┘
```

### Role Details

| Level | Role | Scope | Key Responsibilities |
|-------|------|-------|----------------------|
| 0 | **Super Admin** | System-wide | Complete system access, system settings, all permissions |
| 1 | **Provincial Admin** | System-wide | Manage all cooperatives, users, audit logs, reports (no system settings) |
| 2 | **Coop Admin** | Per-Coop | Full control within assigned cooperative (create/update/delete all resources) |
| 3 | **Chairperson** | Per-Coop | Leadership oversight, approve major decisions, view financial health |
| 4 | **General Manager** | Per-Coop | Day-to-day operations management, operations-focused finance |
| 5 | **Officer** | Per-Coop | Field operations, member interactions, transactions |
| 6 | **Committee Member** | Per-Coop | Committee-specific reports, read-only view of finances |
| 7 | **Member** | Self | View own profile, services, loans, savings |
| 8 | **Viewer** | Per-Coop | Read-only access (external auditors, guests) |

---

## Finance Module Architecture

### Global Structure

The Finance module is a critical subsystem managing all financial operations. It is organized into **two parallel route structures**:

#### 1. **Global Finance Routes** (`/finance/*`)
Used by **Super Admin** and **Provincial Admin** for system-wide financial oversight.

#### 2. **Per-Coop Finance Routes** (`/cooperatives/{cooperative}/finance/*`)
Used by **Coop Admin** and lower roles for cooperative-specific financial management.

### Finance Sub-Modules

```
┌────────────────────────────────────────────────────────────┐
│                   FINANCE MODULE STRUCTURE                  │
└────────────────────────────────────────────────────────────┘

FINANCE
│
├── 📋 FUNDING SOURCES
│   ├── Activity Funding Sources
│   └── External Support Sources
│
├── 💰 MEMBER LOANS
│   ├── Loan Applications (create, edit, delete)
│   ├── Loan Approvals (by Coop Admin/Chairperson)
│   ├── Loan Disbursement (Coop Admin/General Manager)
│   ├── Payment Recording (Officer/Member)
│   └── Loan History & Status
│
├── 💳 SAVINGS ACCOUNTS
│   ├── Account Opening (Coop Admin/General Manager)
│   ├── Deposits & Withdrawals (Member/Officer)
│   ├── Interest Calculation (automated)
│   ├── Account Closure
│   └── Transaction History
│
├── 🏦 COOPERATIVE LOANS (Future/Optional)
│   ├── Institution-to-coop loans
│   ├── Repayment management
│   └── Multi-year tracking
│
├── 📊 FINANCIAL RECORDS (LEDGER)
│   ├── General ledger entries
│   ├── Transaction recording
│   ├── Manual entry support
│   └── Audit trail
│
├── 📈 FINANCIAL HEALTH METRICS
│   ├── KPIs & ratios
│   ├── Health score calculation
│   └── Dashboard indicators
│
└── 📑 FINANCE REPORTS
    ├── Financial Statements
    ├── Loan Portfolio Reports
    ├── Savings Summary Reports
    ├── Funder Accountability Reports
    └── Export Functionality (CSV, PDF)
```

### Finance Route Organization

#### Global Finance Routes

```
GET     /finance                           → Redirect to funding-sources
GET     /finance/funding-sources            → List all funding sources
GET     /finance/funding-sources/create     → Create new funding source
POST    /finance/funding-sources            → Store funding source
GET     /finance/funding-sources/{id}       → View funding source
PUT     /finance/funding-sources/{id}       → Update funding source
DELETE  /finance/funding-sources/{id}       → Delete funding source

GET     /finance/financial-records         → List ledger entries
GET     /finance/financial-records/create  → Create ledger entry
POST    /finance/financial-records         → Store ledger entry
GET     /finance/financial-records/{id}    → View ledger entry
PUT     /finance/financial-records/{id}    → Update ledger entry
DELETE  /finance/financial-records/{id}    → Delete ledger entry

GET     /finance/loans                     → List all loans
GET     /finance/loans/create              → Create new loan
POST    /finance/loans                     → Store loan
GET     /finance/loans/{id}                → View loan
PUT     /finance/loans/{id}                → Update loan
DELETE  /finance/loans/{id}                → Delete loan
POST    /finance/loans/{id}/approve        → Approve loan
POST    /finance/loans/{id}/disburse       → Disburse loan
POST    /finance/loans/{id}/payments       → Record payment

GET     /finance/loan-types                → List loan types
POST    /finance/loan-types                → Create loan type
PUT     /finance/loan-types/{id}           → Update loan type
DELETE  /finance/loan-types/{id}           → Delete loan type

GET     /finance/savings                   → List all savings accounts
GET     /finance/savings/create            → Create new savings account
POST    /finance/savings                   → Store savings account
GET     /finance/savings/{id}              → View savings account
PUT     /finance/savings/{id}              → Update savings account
DELETE  /finance/savings/{id}              → Close savings account
POST    /finance/savings/{id}/transactions → Record transaction
POST    /finance/savings/{id}/calculate-interest → Calculate interest

GET     /finance/reports/statements        → Financial statements
GET     /finance/reports/loan-portfolio    → Loan portfolio
GET     /finance/reports/savings-summary   → Savings summary
GET     /finance/reports/funder-accountability → Funder accountability
```

#### Per-Coop Finance Routes

```
GET     /cooperatives/{cooperative}/finance/funding-sources
GET     /cooperatives/{cooperative}/finance/funding-sources/create
POST    /cooperatives/{cooperative}/finance/funding-sources
GET     /cooperatives/{cooperative}/finance/funding-sources/{id}
PUT     /cooperatives/{cooperative}/finance/funding-sources/{id}
DELETE  /cooperatives/{cooperative}/finance/funding-sources/{id}

GET     /cooperatives/{cooperative}/finance/financial-records
GET     /cooperatives/{cooperative}/finance/financial-records/create
POST    /cooperatives/{cooperative}/finance/financial-records
GET     /cooperatives/{cooperative}/finance/financial-records/{id}
PUT     /cooperatives/{cooperative}/finance/financial-records/{id}
DELETE  /cooperatives/{cooperative}/finance/financial-records/{id}

GET     /cooperatives/{cooperative}/finance/loans
GET     /cooperatives/{cooperative}/finance/loans/create
POST    /cooperatives/{cooperative}/finance/loans
GET     /cooperatives/{cooperative}/finance/loans/{id}
PUT     /cooperatives/{cooperative}/finance/loans/{id}
DELETE  /cooperatives/{cooperative}/finance/loans/{id}
POST    /cooperatives/{cooperative}/finance/loans/{id}/approve
POST    /cooperatives/{cooperative}/finance/loans/{id}/disburse
POST    /cooperatives/{cooperative}/finance/loans/{id}/payments

GET     /cooperatives/{cooperative}/finance/savings
GET     /cooperatives/{cooperative}/finance/savings/create
POST    /cooperatives/{cooperative}/finance/savings
GET     /cooperatives/{cooperative}/finance/savings/{id}
PUT     /cooperatives/{cooperative}/finance/savings/{id}
DELETE  /cooperatives/{cooperative}/finance/savings/{id}
POST    /cooperatives/{cooperative}/finance/savings/{id}/transactions
POST    /cooperatives/{cooperative}/finance/savings/{id}/calculate-interest

GET     /cooperatives/{cooperative}/finance/external-supports
GET     /cooperatives/{cooperative}/finance/external-supports/create
POST    /cooperatives/{cooperative}/finance/external-supports
GET     /cooperatives/{cooperative}/finance/external-supports/{id}
PUT     /cooperatives/{cooperative}/finance/external-supports/{id}
DELETE  /cooperatives/{cooperative}/finance/external-supports/{id}
```

---

## Role-Based Access Control (RBAC)

### Core Modules & Base Permissions

The system organizes permissions by module. Each module has base actions:

| Module | Create | Read | Update | Delete | Export | Approve |
|--------|--------|------|--------|--------|--------|---------|
| Coop Master Profile | ✓ | ✓ | ✓ | ✓ | ✓ | ✗ |
| Members Profile | ✓ | ✓ | ✓ | ✓ | ✓ | ✗ |
| Members Management | ✗ | ✓ | ✗ | ✗ | ✗ | ✗ |
| Officers & Committees | ✓ | ✓ | ✓ | ✓ | ✗ | ✗ |
| Activities & Projects | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| Financial & Support | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| Training & Capacity | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| User Accounts | ✓ | ✓ | ✓ | ✓ | ✗ | ✗ |
| Audit Logs | ✗ | ✓ | ✗ | ✗ | ✗ | ✗ |
| Reports & Dashboard | ✗ | ✓ | ✗ | ✗ | ✓ | ✗ |

### Base Module Access by Role

```
┌──────────────────────┬──────┬───────┬──────────┬──────────┬────────┬──────────┬──────────┬────────┬───────┐
│ Module               │ L0   │ L1    │   L2     │   L3     │  L4    │    L5    │   L6     │  L7    │  L8   │
│                      │ SA   │ PA    │  Coop A  │ Chair    │  GM    │ Officer  │ CommMem  │ Member │ View  │
├──────────────────────┼──────┼───────┼──────────┼──────────┼────────┼──────────┼──────────┼────────┼───────┤
│ Coop Master Profile  │ CRUD │ CRUD  │ RU       │ R        │ R      │ R        │ R        │ —      │ R     │
│ Members Profile      │ CRUD │ CRUD  │ CRUD     │ R        │ RU     │ RU       │ R        │ R*     │ R     │
│ Members Management   │ R    │ R     │ R        │ —        │ —      │ —        │ —        │ —      │ —     │
│ Officers & Committees│ CRUD │ CRUD  │ CRUD     │ RU       │ RU     │ RU       │ R        │ —      │ R     │
│ Activities & Projects│ CRUDE│ CRUDE │ CRUDE    │ R        │ R      │ CRUDE    │ RC       │ R      │ R     │
│ Financial & Support  │ CRUDE│ CRUDE │ CRUDE    │ R        │ R      │ R        │ —        │ —      │ R     │
│ Training & Capacity  │ CRUDE│ CRUDE │ CRUDE    │ —        │ —      │ CRUDE    │ RC       │ R      │ —     │
│ User Accounts        │ CRUD │ —     │ —        │ —        │ —      │ —        │ —        │ —      │ —     │
│ Audit Logs           │ R    │ R     │ —        │ —        │ —      │ —        │ —        │ —      │ —     │
│ Reports & Dashboard  │ RE   │ RE    │ RE       │ R        │ R      │ R        │ R        │ R**    │ —     │
└──────────────────────┴──────┴───────┴──────────┴──────────┴────────┴──────────┴──────────┴────────┴───────┘

Legend: C=Create, R=Read, U=Update, D=Delete, E=Export
* = Own profile only
** = Own records only
```

---

## Finance Permissions Matrix

### Finance-Specific Permissions (Detailed)

The Finance module has granular permissions beyond the base CRUD actions:

#### Member Loans
- `apply-own finance-member-loans` — Member can apply for their own loan
- `create finance-member-loans` — Create loan (officer/admin)
- `read finance-member-loans` — View loans
- `update finance-member-loans` — Edit loan details
- `delete finance-member-loans` — Delete loan
- `approve finance-member-loans` — Approve standard loans
- `approve-major finance-member-loans` — Approve high-value loans
- `disburse finance-member-loans` — Release loan funds
- `record-payment finance-member-loans` — Record loan payments
- `export finance-member-loans` — Export loan data

#### Savings Accounts
- `open finance-savings-accounts` — Create savings account
- `read finance-savings-accounts` — View savings accounts
- `update finance-savings-accounts` — Modify account
- `close finance-savings-accounts` — Close account
- `record-deposit finance-savings-accounts` — Record deposits
- `record-withdrawal finance-savings-accounts` — Record withdrawals
- `calculate-interest finance-savings-accounts` — Calculate & apply interest
- `export finance-savings-accounts` — Export savings data

#### Funding Sources
- `create finance-funding-sources` — Create funding source
- `read finance-funding-sources` — View funding sources
- `update finance-funding-sources` — Edit funding source
- `delete finance-funding-sources` — Delete funding source
- `approve finance-funding-sources` — Approve funding source
- `export finance-funding-sources` — Export funding data

#### Ledger & Records
- `create finance-ledger-entries` — Create ledger entry
- `read finance-ledger-entries` — View ledger entries
- `update finance-ledger-entries` — Edit ledger entry
- `delete finance-ledger-entries` — Delete ledger entry
- `approve finance-ledger-entries` — Approve entries
- `export finance-ledger-entries` — Export ledger data
- `read finance-health-metrics` — View financial health indicators

#### Reports & Governance
- `generate finance-reports` — Generate reports
- `read finance-reports` — View reports
- `export finance-reports` — Export reports
- `approve finance-reports` — Approve reports
- `manage finance-policies` — Configure finance policies
- `override finance-auto-jobs` — Override automated processes
- `view finance-audit-trail` — View finance audit trail
- `reconcile finance-transactions` — Reconcile transactions

#### Cooperative Loans (Future)
- `create finance-coop-loans`
- `read finance-coop-loans`
- `update finance-coop-loans`
- `delete finance-coop-loans`
- `approve finance-coop-loans`
- `disburse finance-coop-loans`
- `record-repayment finance-coop-loans`
- `export finance-coop-loans`

### Finance Permissions by Role

#### 🔴 SUPER ADMIN (Level 0)
**Global Scope** — Full system access
- ✅ ALL finance permissions across entire system
- ✅ Create, Read, Update, Delete, Approve, Disburse
- ✅ Override auto-jobs
- ✅ Manage finance policies
- ✅ Audit trail access

#### 🟠 PROVINCIAL ADMIN (Level 1)
**Global Scope** — System-wide management (no system settings)
- ✅ ALL finance permissions across all cooperatives
- ✅ Member Loans: Full CRUD + Approve + Disburse
- ✅ Savings: Full CRUD + Transaction recording
- ✅ Funding Sources: Full CRUD + Approve
- ✅ Ledger: Full CRUD + Approve + Export
- ✅ Reports: Full read + Export + Approve
- ✅ Audit trail access
- ✅ Reconciliation & health metrics

#### 🟡 COOP ADMIN (Level 2)
**Per-Coop Scope** — Full administrative control within coop
- ✅ Funding Sources: Create, Read, Update, Approve, Export
- ✅ Member Loans: Create, Read, Update, Delete, Approve, Disburse, Record Payment, Export
- ✅ Coop Loans: Create, Read, Update, Delete, Disburse, Record Repayment, Export
- ✅ Savings: Open, Read, Update, Close, Transaction Recording, Interest Calculation, Export
- ✅ Ledger: Create, Read, Update, Delete, Approve, Export
- ✅ Health Metrics: Read
- ✅ Reports: Generate, Read, Export
- ✅ Audit trail view
- ✅ Transaction reconciliation

#### 🟢 CHAIRPERSON (Level 3)
**Per-Coop Scope** — Leadership & oversight role
- ✅ Funding Sources: **Read + Approve**
- ✅ Member Loans: **Read + Approve Major** loans only
- ✅ Coop Loans: Read
- ✅ Savings: Read
- ✅ Ledger: Read, Health Metrics: Read
- ✅ Reports: Read + Approve
- ✅ Audit trail view

#### 🔵 GENERAL MANAGER (Level 4)
**Per-Coop Scope** — Operations-focused financial management
- ✅ Funding Sources: Create, Read, Update, Export
- ✅ Member Loans: Create, Read, Update, Approve, Disburse, Record Payment, Export
- ✅ Coop Loans: Create, Read, Update, Disburse, Record Repayment, Export
- ✅ Savings: Open, Read, Update, Close, Transaction Recording, Interest Calculation, Export
- ✅ Ledger: Create, Read, Update, Approve, Export
- ✅ Health Metrics: Read
- ✅ Reports: Generate, Read, Export
- ✅ Override auto-jobs
- ✅ Audit trail view
- ✅ Transaction reconciliation

#### 🟣 OFFICER (Level 5)
**Per-Coop Scope** — Field-level operational transactions
- ✅ Funding Sources: Create, Read
- ✅ Member Loans: **Apply-own + Create, Read, Update, Record Payment**
- ✅ Coop Loans: Read + Record Repayment
- ✅ Savings: Read + Record Deposit/Withdrawal
- ✅ Ledger: Create, Read, Health Metrics: Read
- ✅ Reports: Generate, Read
- ❌ No approval authority
- ❌ No disburse authority

#### 🔵 COMMITTEE MEMBER (Level 6)
**Per-Coop Scope** — Read-only with audit trail
- ✅ Funding Sources: Read
- ✅ Member Loans: Read
- ✅ Coop Loans: Read
- ✅ Savings: Read
- ✅ Ledger: Read, Health Metrics: Read
- ✅ Reports: Read
- ✅ Audit trail view
- ❌ No transactional authority

#### 🟢 MEMBER (Level 7)
**Self + Cooperative Scope** — Self-service member access
- ✅ Member Loans: **Apply-own + Read own**
- ✅ Savings: Read own + Record Deposit/Withdrawal on own accounts
- ❌ No approval or administrative authority

#### ⚪ VIEWER (Level 8)
**Per-Coop Scope** — Read-only external auditor/guest access
- ✅ Funding Sources: Read
- ✅ Member Loans: Read
- ✅ Coop Loans: Read
- ✅ Savings: Read
- ✅ Ledger: Read, Health Metrics: Read
- ✅ Reports: Read
- ❌ No transactional or administrative authority

### Permission Access Summary Table

```
┌────────────────────────────┬──────┬────┬────────┬────────┬──────┬────────┬──────────┬────────┬───────┐
│ Finance Permission         │ SA   │PA  │Coop A  │Chair   │ GM   │Officer │CommMem   │Member  │Viewer │
├────────────────────────────┼──────┼────┼────────┼────────┼──────┼────────┼──────────┼────────┼───────┤
│ Create Funding Source       │ ✅   │✅  │ ✅     │ ❌     │ ✅   │ ✅     │ ❌       │ ❌     │ ❌    │
│ Read Funding Source         │ ✅   │✅  │ ✅     │ ✅     │ ✅   │ ✅     │ ❌       │ ❌     │ ✅    │
│ Update Funding Source       │ ✅   │✅  │ ✅     │ ❌     │ ✅   │ ❌     │ ❌       │ ❌     │ ❌    │
│ Approve Funding Source      │ ✅   │✅  │ ✅     │ ✅     │ ❌   │ ❌     │ ❌       │ ❌     │ ❌    │
│ Export Funding Source       │ ✅   │✅  │ ✅     │ ❌     │ ✅   │ ❌     │ ❌       │ ❌     │ ❌    │
│                             │      │    │        │        │      │        │          │        │       │
│ Apply-own Member Loan       │ ✅   │✅  │ ❌     │ ❌     │ ❌   │ ✅     │ ❌       │ ✅     │ ❌    │
│ Create Member Loan          │ ✅   │✅  │ ✅     │ ❌     │ ✅   │ ✅     │ ❌       │ ❌     │ ❌    │
│ Read Member Loan            │ ✅   │✅  │ ✅     │ ✅     │ ✅   │ ✅     │ ✅       │ ✅*    │ ✅    │
│ Update Member Loan          │ ✅   │✅  │ ✅     │ ❌     │ ✅   │ ✅     │ ❌       │ ❌     │ ❌    │
│ Delete Member Loan          │ ✅   │✅  │ ✅     │ ❌     │ ❌   │ ❌     │ ❌       │ ❌     │ ❌    │
│ Approve Member Loan         │ ✅   │✅  │ ✅     │ ❌     │ ✅   │ ❌     │ ❌       │ ❌     │ ❌    │
│ Approve-major Member Loan   │ ✅   │✅  │ ❌     │ ✅     │ ❌   │ ❌     │ ❌       │ ❌     │ ❌    │
│ Disburse Member Loan        │ ✅   │✅  │ ✅     │ ❌     │ ✅   │ ❌     │ ❌       │ ❌     │ ❌    │
│ Record Payment Member Loan  │ ✅   │✅  │ ✅     │ ❌     │ ✅   │ ✅     │ ❌       │ ❌     │ ❌    │
│ Export Member Loan          │ ✅   │✅  │ ✅     │ ❌     │ ✅   │ ❌     │ ❌       │ ❌     │ ❌    │
│                             │      │    │        │        │      │        │          │        │       │
│ Open Savings Account        │ ✅   │✅  │ ✅     │ ❌     │ ✅   │ ❌     │ ❌       │ ❌     │ ❌    │
│ Read Savings Account        │ ✅   │✅  │ ✅     │ ✅     │ ✅   │ ✅     │ ✅       │ ✅*    │ ✅    │
│ Update Savings Account      │ ✅   │✅  │ ✅     │ ❌     │ ✅   │ ❌     │ ❌       │ ❌     │ ❌    │
│ Close Savings Account       │ ✅   │✅  │ ✅     │ ❌     │ ✅   │ ❌     │ ❌       │ ❌     │ ❌    │
│ Record Deposit              │ ✅   │✅  │ ✅     │ ❌     │ ✅   │ ✅     │ ❌       │ ✅*    │ ❌    │
│ Record Withdrawal           │ ✅   │✅  │ ✅     │ ❌     │ ✅   │ ✅     │ ❌       │ ✅*    │ ❌    │
│ Calculate Interest          │ ✅   │✅  │ ✅     │ ❌     │ ✅   │ ❌     │ ❌       │ ❌     │ ❌    │
│ Export Savings              │ ✅   │✅  │ ✅     │ ❌     │ ✅   │ ❌     │ ❌       │ ❌     │ ❌    │
│                             │      │    │        │        │      │        │          │        │       │
│ Create Ledger Entry         │ ✅   │✅  │ ✅     │ ❌     │ ✅   │ ✅     │ ❌       │ ❌     │ ❌    │
│ Read Ledger Entry           │ ✅   │✅  │ ✅     │ ✅     │ ✅   │ ✅     │ ✅       │ ❌     │ ✅    │
│ Update Ledger Entry         │ ✅   │✅  │ ✅     │ ❌     │ ✅   │ ❌     │ ❌       │ ❌     │ ❌    │
│ Delete Ledger Entry         │ ✅   │✅  │ ✅     │ ❌     │ ❌   │ ❌     │ ❌       │ ❌     │ ❌    │
│ Approve Ledger Entry        │ ✅   │✅  │ ✅     │ ❌     │ ✅   │ ❌     │ ❌       │ ❌     │ ❌    │
│ Export Ledger Entry         │ ✅   │✅  │ ✅     │ ❌     │ ✅   │ ❌     │ ❌       │ ❌     │ ❌    │
│                             │      │    │        │        │      │        │          │        │       │
│ Read Finance Health Metrics │ ✅   │✅  │ ✅     │ ✅     │ ✅   │ ✅     │ ✅       │ ❌     │ ✅    │
│ Generate Reports            │ ✅   │✅  │ ✅     │ ❌     │ ✅   │ ✅     │ ❌       │ ❌     │ ❌    │
│ Read Reports                │ ✅   │✅  │ ✅     │ ✅     │ ✅   │ ✅     │ ✅       │ ❌     │ ✅    │
│ Approve Reports             │ ✅   │✅  │ ❌     │ ✅     │ ❌   │ ❌     │ ❌       │ ❌     │ ❌    │
│ Export Reports              │ ✅   │✅  │ ✅     │ ❌     │ ✅   │ ❌     │ ❌       │ ❌     │ ❌    │
│                             │      │    │        │        │      │        │          │        │       │
│ View Audit Trail            │ ✅   │✅  │ ✅     │ ✅     │ ✅   │ ❌     │ ✅       │ ❌     │ ❌    │
│ Reconcile Transactions      │ ✅   │✅  │ ✅     │ ❌     │ ✅   │ ❌     │ ❌       │ ❌     │ ❌    │
│ Override Auto-Jobs          │ ✅   │✅  │ ❌     │ ❌     │ ✅   │ ❌     │ ❌       │ ❌     │ ❌    │
│ Manage Finance Policies     │ ✅   │❌  │ ❌     │ ❌     │ ❌   │ ❌     │ ❌       │ ❌     │ ❌    │
└────────────────────────────┴──────┴────┴────────┴────────┴──────┴────────┴──────────┴────────┴───────┘

* = Own records only
```

---

## Data Scoping Patterns

### Dual Route Architecture

The system implements a sophisticated dual-route architecture for financial data management:

```
┌─────────────────────────────────────────────────────────────┐
│          GLOBAL vs. PER-COOP ROUTE ARCHITECTURE             │
└─────────────────────────────────────────────────────────────┘

GLOBAL ROUTES: /finance/*
┌──────────────────────────────────────────────────────────────┐
│ Users: Super Admin, Provincial Admin                         │
│ Scope: System-wide, all cooperatives                         │
│ Data Access: All cooperative finances                        │
│ Middleware Check: permission:read financial-&-support|...   │
└──────────────────────────────────────────────────────────────┘
                         ↓
                 [PERMISSION CHECK]
                         ↓
          No special scoping, views ALL data
          (Super Admin & Provincial Admin have
           system-wide view by default)

PER-COOP ROUTES: /cooperatives/{cooperative}/finance/*
┌──────────────────────────────────────────────────────────────┐
│ Users: Coop Admin, Chairperson, GM, Officer, Committee      │
│ Scope: Single cooperative                                    │
│ Data Access: Only this cooperative's finances               │
│ Middleware Check: permission:read financial-&-support|...   │
└──────────────────────────────────────────────────────────────┘
                         ↓
                 [PERMISSION CHECK]
                         ↓
                 [COOP SCOPING CHECK]
                         ↓
          Query filtered by cooperative_id
          Only this coop's data returned
          Foreign cooperative access blocked
```

### Data Access Control Logic

#### How Data Scoping Works

1. **System-Wide Permissions**: `view-all-cooperatives`
   - Users with this permission see all cooperative data
   - Applied to: Super Admin, Provincial Admin
   - Routes: Both global and per-coop (can override)

2. **Coop-Scoped Data**: User's `coop_id`
   - Default for all other roles
   - Data automatically filtered by user's assigned cooperative
   - Cannot access other cooperatives' data

3. **Self-Scoped Data**: User's own records
   - Members can only view/modify their own records
   - Officers can view/transact on their assigned coop's data
   - Enforced through query scoping in controllers

#### Model Trait: CoopScoped

All finance-related models use the `CoopScoped` trait:

```php
// In models (MemberLoan, MemberSavings, FinancialRecord, etc.):
use App\Models\Concerns\CoopScoped;

class MemberLoan extends Model {
    use CoopScoped;
}
```

**CoopScoped Trait Behavior**:
- Automatically scopes queries to user's `coop_id`
- Prevents cross-cooperative data access
- Can be bypassed with `view-all-cooperatives` permission
- Applied at model query level (automatic in all queries)

#### Examples of Data Scoping in Action

**Example 1: Coop Admin viewing Member Loans**

```
User: Coop Admin (coop_id = 5)
Route: GET /finance/loans
or    GET /cooperatives/5/finance/loans

Query Applied:
SELECT * FROM member_loans 
WHERE cooperative_id = 5  // Automatically scoped

Result: Only loans from cooperative #5
```

**Example 2: Provincial Admin viewing all loans**

```
User: Provincial Admin (permission: view-all-cooperatives)
Route: GET /finance/loans

Query Applied:
SELECT * FROM member_loans
// No coop_id filter (bypassed by permission)

Result: Loans from ALL cooperatives
```

**Example 3: Member viewing own loans**

```
User: Member (coop_id = 5, user_id = 123)
Route: GET /member-portal/loans

Query Applied:
SELECT * FROM member_loans 
WHERE cooperative_id = 5 
AND member_id = 123  // Filtered to own records

Result: Only user's own loans
```

### Data Scoping Hierarchy

```
┌─────────────────────────────────────────────────────┐
│            DATA SCOPING HIERARCHY                    │
└─────────────────────────────────────────────────────┘

System-Wide (Unrestricted)
├─ Super Admin
└─ Provincial Admin + view-all-cooperatives permission
   │
   └─→ Access to: All cooperatives' finance data

Per-Cooperative (Scoped)
├─ Coop Admin
├─ Chairperson
├─ General Manager
├─ Officer
├─ Committee Member
└─ Viewer
   │
   └─→ Access to: Only assigned cooperative's data

Self-Service (Self-Scoped)
├─ Member (user_id scoped)
└─ Officer (member_id scoped when recording own transactions)
   │
   └─→ Access to: Own records only
```

---

## System Components

### Core Finance Models

```
app/Models/
├── MemberLoan.php           → Member loan applications & status
├── LoanPayment.php          → Loan payment records
├── LoanType.php             → Loan type definitions
├── MemberSavings.php        → Savings accounts
├── SavingsTransaction.php   → Savings deposits/withdrawals
├── FinancialRecord.php      → General ledger entries
├── ActivityFundingSource.php → Activity funding sources
└── ExternalSupport.php      → External support records
```

### Finance Controllers

```
app/Http/Controllers/
├── LoansController.php                  → CRUD + Approve + Disburse
├── LoanPaymentsController.php           → Payment recording
├── LoanTypeController.php               → Loan type management
├── SavingsController.php                → Account management
├── SavingsTransactionsController.php    → Transaction recording
├── FinancialRecordController.php        → Ledger entry management
├── FinancialRecordsController.php       → Ledger list view
├── FundingSourcesController.php         → Funding source view
├── ActivityFundingSourceController.php  → Funding source CRUD
├── FinanceReportsController.php         → Report generation
└── ExternalSupportController.php        → External support management
```

### Key Features

1. **Transaction Recording**: Member loans, savings deposits/withdrawals
2. **Approval Workflow**: Multi-level approval (standard vs. major loans)
3. **Interest Calculation**: Automated savings interest computation
4. **Audit Trail**: All finance transactions logged
5. **Report Generation**: Financial statements, loan portfolio, savings summary
6. **Health Metrics**: KPIs and financial health indicators
7. **Reconciliation**: Tools for finance reconciliation

---

## Summary

This PICTO Cooperative Management System implements a sophisticated role-based access control system with:

- **9 hierarchical roles** spanning system administrators to individual members
- **Comprehensive Finance module** with dual-route architecture for global and per-coop operations
- **Fine-grained permissions** allowing role-specific finance operations
- **Automatic data scoping** preventing unauthorized cross-cooperative access
- **Dual data model**: System-wide views for administrators + cooperative-specific views for operational roles
- **Modular design** enabling independent feature development and testing

The Finance module is a critical subsystem managing all cooperative financial operations with clear separation between administrative oversight and operational management.
