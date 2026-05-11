# PICTO-COOP-MGMT-SYS | Finance Module Architecture
**READ-ONLY ANALYSIS** | Last Updated: 2026-05-11

---

## SECTION 1 — All Finance Routes

### GROUP A — Global Finance Routes (`/finance/*`)
Provincial Admin and Super Admin use these routes to view **all cooperatives' finance data** from a single dashboard.

| HTTP Method | Full URL | Route Name | Controller@Method | Permission Middleware |
|---|---|---|---|---|
| **GET** | `/finance` | `finance.index` | `FinanceOverviewController@index` | `read financial-&-support \| read finance-funding-sources \| read finance-ledger-entries \| read finance-member-loans \| read finance-savings-accounts \| read finance-reports` |
| **GET** | `/finance/funding-sources` | `finance.funding-sources.index` | `FundingSourcesController@index` | `read finance-funding-sources` |
| **GET** | `/finance/funding-sources/create` | `finance.funding-sources.create` | `ActivityFundingSourceController@create` | `create finance-funding-sources` |
| **POST** | `/finance/funding-sources` | `finance.funding-sources.store` | `ActivityFundingSourceController@store` | `create finance-funding-sources` |
| **GET** | `/finance/funding-sources/{fundingSource}` | `finance.funding-sources.show` | `FundingSourcesController@show` | `read finance-funding-sources` |
| **GET** | `/finance/funding-sources/{fundingSource}/edit` | `finance.funding-sources.edit` | `ActivityFundingSourceController@edit` | `update finance-funding-sources` |
| **PUT** | `/finance/funding-sources/{fundingSource}` | `finance.funding-sources.update` | `ActivityFundingSourceController@update` | `update finance-funding-sources` |
| **DELETE** | `/finance/funding-sources/{fundingSource}` | `finance.funding-sources.destroy` | `ActivityFundingSourceController@destroy` | `delete finance-funding-sources` |
| **GET** | `/finance/financial-records` | `finance.financial-records.index` | `FinancialRecordsController@index` | `read finance-ledger-entries` |
| **GET** | `/finance/financial-records/create` | `finance.financial-records.create` | `FinancialRecordController@create` | `create finance-ledger-entries` |
| **POST** | `/finance/financial-records` | `finance.financial-records.store` | `FinancialRecordController@store` | `create finance-ledger-entries` |
| **GET** | `/finance/financial-records/{financialRecord}` | `finance.financial-records.show` | `FinancialRecordsController@show` | `read finance-ledger-entries` |
| **GET** | `/finance/financial-records/{financialRecord}/edit` | `finance.financial-records.edit` | `FinancialRecordController@edit` | `update finance-ledger-entries` |
| **PUT** | `/finance/financial-records/{financialRecord}` | `finance.financial-records.update` | `FinancialRecordController@update` | `update finance-ledger-entries` |
| **DELETE** | `/finance/financial-records/{financialRecord}` | `finance.financial-records.destroy` | `FinancialRecordController@destroy` | `delete finance-ledger-entries` |
| **GET** | `/finance/loans` | `finance.loans.index` | `LoansController@index` | `read finance-member-loans` |
| **GET** | `/finance/loans/create` | `finance.loans.create` | `LoansController@create` | `create finance-member-loans \| apply-own finance-member-loans` |
| **POST** | `/finance/loans` | `finance.loans.store` | `LoansController@store` | `create finance-member-loans \| apply-own finance-member-loans` |
| **GET** | `/finance/loans/{loan}` | `finance.loans.show` | `LoansController@show` | `read finance-member-loans` |
| **GET** | `/finance/loans/{loan}/edit` | `finance.loans.edit` | `LoansController@edit` | `update finance-member-loans` |
| **PUT** | `/finance/loans/{loan}` | `finance.loans.update` | `LoansController@update` | `update finance-member-loans` |
| **DELETE** | `/finance/loans/{loan}` | `finance.loans.destroy` | `LoansController@destroy` | `delete finance-member-loans` |
| **POST** | `/finance/loans/{loan}/approve` | `finance.loans.approve` | `LoansController@approve` | `approve finance-member-loans \| approve-major finance-member-loans` |
| **POST** | `/finance/loans/{loan}/disburse` | `finance.loans.disburse` | `LoansController@disburse` | `disburse finance-member-loans` |
| **POST** | `/finance/loans/{loan}/payments` | `finance.loans.payments.store` | `LoanPaymentsController@store` | `record-payment finance-member-loans` |
| **GET** | `/finance/loan-types` | `finance.loan-types.index` | `LoanTypeController@index` | `read finance-member-loans` |
| **POST** | `/finance/loan-types` | `finance.loan-types.store` | `LoanTypeController@store` | `create finance-member-loans` |
| **PUT** | `/finance/loan-types/{loanType}` | `finance.loan-types.update` | `LoanTypeController@update` | `update finance-member-loans` |
| **DELETE** | `/finance/loan-types/{loanType}` | `finance.loan-types.destroy` | `LoanTypeController@destroy` | `delete finance-member-loans` |
| **GET** | `/finance/savings` | `finance.savings.index` | `SavingsController@index` | `read finance-savings-accounts` |
| **GET** | `/finance/savings/create` | `finance.savings.create` | `SavingsController@create` | `open finance-savings-accounts` |
| **POST** | `/finance/savings` | `finance.savings.store` | `SavingsController@store` | `open finance-savings-accounts` |
| **GET** | `/finance/savings/{savings}` | `finance.savings.show` | `SavingsController@show` | `read finance-savings-accounts` |
| **GET** | `/finance/savings/{savings}/edit` | `finance.savings.edit` | `SavingsController@edit` | `update finance-savings-accounts` |
| **PUT** | `/finance/savings/{savings}` | `finance.savings.update` | `SavingsController@update` | `update finance-savings-accounts` |
| **DELETE** | `/finance/savings/{savings}` | `finance.savings.destroy` | `SavingsController@destroy` | `close finance-savings-accounts` |
| **POST** | `/finance/savings/{savings}/transactions` | `finance.savings.transactions.store` | `SavingsTransactionsController@store` | `record-deposit finance-savings-accounts \| record-withdrawal finance-savings-accounts` |
| **POST** | `/finance/savings/{savings}/calculate-interest` | `finance.savings.calculate-interest` | `SavingsController@calculateInterest` | `calculate-interest finance-savings-accounts \| override finance-auto-jobs` |
| **GET** | `/finance/external-supports` | `finance.external-supports.index` | `ExternalSupportController@index` | `read financial-&-support` |
| **GET** | `/finance/reports/statements` | `finance.reports.statements` | `FinanceReportsController@statements` | `read finance-reports` |
| **GET** | `/finance/reports/statements/export` | `finance.reports.statements.export` | `FinanceReportsController@exportStatements` | `export finance-reports` |
| **GET** | `/finance/reports/loan-portfolio` | `finance.reports.loan-portfolio` | `FinanceReportsController@loanPortfolio` | `read finance-reports` |
| **GET** | `/finance/reports/loan-portfolio/export` | `finance.reports.loan-portfolio.export` | `FinanceReportsController@exportLoanPortfolio` | `export finance-reports` |
| **GET** | `/finance/reports/savings-summary` | `finance.reports.savings-summary` | `FinanceReportsController@savingsSummary` | `read finance-reports` |
| **GET** | `/finance/reports/savings-summary/export` | `finance.reports.savings-summary.export` | `FinanceReportsController@exportSavingsSummary` | `export finance-reports` |
| **GET** | `/finance/reports/funder-accountability` | `finance.reports.funder-accountability` | `FinanceReportsController@funderAccountability` | `read finance-reports` |
| **GET** | `/finance/reports/funder-accountability/export` | `finance.reports.funder-accountability.export` | `FinanceReportsController@exportFunderAccountability` | `export finance-reports` |
| **GET** | `/finance/reports/trends` | `finance.reports.trends` | `FinanceReportsController@trends` | `read finance-reports` |
| **GET** | `/finance/reports/trends/export` | `finance.reports.trends.export` | `FinanceReportsController@exportTrends` | `export finance-reports` |

---

### GROUP B — Per-Cooperative Finance Routes (`/cooperatives/{id}/finance/*`)
Coop Admin uses these routes to manage **their own cooperative's finance data only**.

| HTTP Method | Full URL | Route Name | Controller@Method | Permission Middleware |
|---|---|---|---|---|
| **GET** | `/cooperatives/{cooperative}/finance/funding-sources` | `cooperatives.finance.funding-sources.index` | `FundingSourcesController@index` | `read finance-funding-sources` |
| **GET** | `/cooperatives/{cooperative}/finance/funding-sources/create` | `cooperatives.finance.funding-sources.create` | `ActivityFundingSourceController@create` | `create finance-funding-sources` |
| **POST** | `/cooperatives/{cooperative}/finance/funding-sources` | `cooperatives.finance.funding-sources.store` | `ActivityFundingSourceController@store` | `create finance-funding-sources` |
| **GET** | `/cooperatives/{cooperative}/finance/funding-sources/{fundingSource}` | `cooperatives.finance.funding-sources.show` | `FundingSourcesController@show` | `read finance-funding-sources` |
| **GET** | `/cooperatives/{cooperative}/finance/funding-sources/{fundingSource}/edit` | `cooperatives.finance.funding-sources.edit` | `ActivityFundingSourceController@edit` | `update finance-funding-sources` |
| **PUT** | `/cooperatives/{cooperative}/finance/funding-sources/{fundingSource}` | `cooperatives.finance.funding-sources.update` | `ActivityFundingSourceController@update` | `update finance-funding-sources` |
| **DELETE** | `/cooperatives/{cooperative}/finance/funding-sources/{fundingSource}` | `cooperatives.finance.funding-sources.destroy` | `ActivityFundingSourceController@destroy` | `delete finance-funding-sources` |
| **GET** | `/cooperatives/{cooperative}/finance/financial-records` | `cooperatives.finance.financial-records.index` | `FinancialRecordsController@index` | `read finance-ledger-entries` |
| **GET** | `/cooperatives/{cooperative}/finance/financial-records/create` | `cooperatives.finance.financial-records.create` | `FinancialRecordController@create` | `create finance-ledger-entries` |
| **POST** | `/cooperatives/{cooperative}/finance/financial-records` | `cooperatives.finance.financial-records.store` | `FinancialRecordController@store` | `create finance-ledger-entries` |
| **GET** | `/cooperatives/{cooperative}/finance/financial-records/{financialRecord}` | `cooperatives.finance.financial-records.show` | `FinancialRecordsController@show` | `read finance-ledger-entries` |
| **GET** | `/cooperatives/{cooperative}/finance/financial-records/{financialRecord}/edit` | `cooperatives.finance.financial-records.edit` | `FinancialRecordController@edit` | `update finance-ledger-entries` |
| **PUT** | `/cooperatives/{cooperative}/finance/financial-records/{financialRecord}` | `cooperatives.finance.financial-records.update` | `FinancialRecordController@update` | `update finance-ledger-entries` |
| **DELETE** | `/cooperatives/{cooperative}/finance/financial-records/{financialRecord}` | `cooperatives.finance.financial-records.destroy` | `FinancialRecordController@destroy` | `delete finance-ledger-entries` |
| **GET** | `/cooperatives/{cooperative}/finance/loans` | `cooperatives.finance.loans.index` | `LoansController@index` | `read finance-member-loans` |
| **GET** | `/cooperatives/{cooperative}/finance/loans/create` | `cooperatives.finance.loans.create` | `LoansController@create` | `create finance-member-loans \| apply-own finance-member-loans` |
| **POST** | `/cooperatives/{cooperative}/finance/loans` | `cooperatives.finance.loans.store` | `LoansController@store` | `create finance-member-loans \| apply-own finance-member-loans` |
| **GET** | `/cooperatives/{cooperative}/finance/loans/{loan}` | `cooperatives.finance.loans.show` | `LoansController@show` | `read finance-member-loans` |
| **GET** | `/cooperatives/{cooperative}/finance/loans/{loan}/edit` | `cooperatives.finance.loans.edit` | `LoansController@edit` | `update finance-member-loans` |
| **PUT** | `/cooperatives/{cooperative}/finance/loans/{loan}` | `cooperatives.finance.loans.update` | `LoansController@update` | `update finance-member-loans` |
| **DELETE** | `/cooperatives/{cooperative}/finance/loans/{loan}` | `cooperatives.finance.loans.destroy` | `LoansController@destroy` | `delete finance-member-loans` |
| **POST** | `/cooperatives/{cooperative}/finance/loans/{loan}/approve` | `cooperatives.finance.loans.approve` | `LoansController@approve` | `approve finance-member-loans \| approve-major finance-member-loans` |
| **POST** | `/cooperatives/{cooperative}/finance/loans/{loan}/disburse` | `cooperatives.finance.loans.disburse` | `LoansController@disburse` | `disburse finance-member-loans` |
| **POST** | `/cooperatives/{cooperative}/finance/loans/{loan}/payments` | `cooperatives.finance.loans.payments.store` | `LoanPaymentsController@store` | `record-payment finance-member-loans` |
| **GET** | `/cooperatives/{cooperative}/finance/savings` | `cooperatives.finance.savings.index` | `SavingsController@index` | `read finance-savings-accounts` |
| **GET** | `/cooperatives/{cooperative}/finance/savings/create` | `cooperatives.finance.savings.create` | `SavingsController@create` | `open finance-savings-accounts` |
| **POST** | `/cooperatives/{cooperative}/finance/savings` | `cooperatives.finance.savings.store` | `SavingsController@store` | `open finance-savings-accounts` |
| **GET** | `/cooperatives/{cooperative}/finance/savings/{savings}` | `cooperatives.finance.savings.show` | `SavingsController@show` | `read finance-savings-accounts` |
| **GET** | `/cooperatives/{cooperative}/finance/savings/{savings}/edit` | `cooperatives.finance.savings.edit` | `SavingsController@edit` | `update finance-savings-accounts` |
| **PUT** | `/cooperatives/{cooperative}/finance/savings/{savings}` | `cooperatives.finance.savings.update` | `SavingsController@update` | `update finance-savings-accounts` |
| **DELETE** | `/cooperatives/{cooperative}/finance/savings/{savings}` | `cooperatives.finance.savings.destroy` | `SavingsController@destroy` | `close finance-savings-accounts` |
| **POST** | `/cooperatives/{cooperative}/finance/savings/{savings}/transactions` | `cooperatives.finance.savings.transactions.store` | `SavingsTransactionsController@store` | `record-deposit finance-savings-accounts \| record-withdrawal finance-savings-accounts` |
| **POST** | `/cooperatives/{cooperative}/finance/savings/{savings}/calculate-interest` | `cooperatives.finance.savings.calculate-interest` | `SavingsController@calculateInterest` | `calculate-interest finance-savings-accounts \| override finance-auto-jobs` |
| **GET** | `/cooperatives/{cooperative}/finance/external-supports` | `cooperatives.finance.external-supports.index` | `ExternalSupportController@index` | `read financial-&-support` |
| **GET** | `/cooperatives/{cooperative}/finance/external-supports/create` | `cooperatives.finance.external-supports.create` | `ExternalSupportController@create` | `create financial-&-support` |
| **POST** | `/cooperatives/{cooperative}/finance/external-supports` | `cooperatives.finance.external-supports.store` | `ExternalSupportController@store` | `create financial-&-support` |
| **GET** | `/cooperatives/{cooperative}/finance/external-supports/{externalSupport}/edit` | `cooperatives.finance.external-supports.edit` | `ExternalSupportController@edit` | `update financial-&-support` |
| **PUT** | `/cooperatives/{cooperative}/finance/external-supports/{externalSupport}` | `cooperatives.finance.external-supports.update` | `ExternalSupportController@update` | `update financial-&-support` |
| **DELETE** | `/cooperatives/{cooperative}/finance/external-supports/{externalSupport}` | `cooperatives.finance.external-supports.destroy` | `ExternalSupportController@destroy` | `delete financial-&-support` |

---

### GROUP C — Member Portal Finance Routes (`/member-portal/*`)
Members use these routes to view **their own loans only**.

| HTTP Method | Full URL | Route Name | Controller@Method | Permission Middleware |
|---|---|---|---|---|
| **GET** | `/member-portal/loans` | `member-portal.loans` | `MemberPortalController@loans` | `read finance-member-loans \| apply-own finance-member-loans` |
| **GET** | `/member-portal/loans/{loan}` | `member-portal.loans.show` | `MemberPortalController@showLoan` | `read finance-member-loans \| apply-own finance-member-loans` |

---

## SECTION 2 — All Finance Controllers

### 1. [LoansController](app/Http/Controllers/LoansController.php)
**Location:** `app/Http/Controllers/LoansController.php`  
**Class:** `LoansController`

**Public Methods:**
- `create(Request)` — Show create loan form with member/loan-type picker
- `index(Request, ?Cooperative)` — List loans (global or per-coop)
- `store(Request)` — Create loan record + auto-generate repayment schedule
- `show(Request, Cooperative, MemberLoan)` — Display loan details + payment schedule
- `edit(Request, Cooperative, MemberLoan)` — Show edit form
- `update(Request, Cooperative, MemberLoan)` — Update loan details
- `destroy(Request, Cooperative, MemberLoan)` — Delete (soft delete) loan
- `approve(Request, MemberLoan)` — Approve pending/rejected loan
- `disburse(Request, MemberLoan)` — Disburse approved loan → creates FinancialRecord with origin=`loan_disbursement`

**Routes Called:**
- **Global:** `finance.loans.*`
- **Per-Coop:** `cooperatives.finance.loans.*`

**Internal Methods (private):**
- `generateRepaymentSchedule(MemberLoan)` — Creates LoanPayment records
- `calculateMonthlyPayment(float, float, int)` — Calculates EMI
- `enforceLoanAccess(MemberLoan, User)` — Checks cooperative scope
- `isMemberOnly(User)` — Checks if user is member-only (apply-own)
- Attachment handling methods

---

### 2. [LoanPaymentsController](app/Http/Controllers/LoanPaymentsController.php)
**Location:** `app/Http/Controllers/LoanPaymentsController.php`  
**Class:** `LoanPaymentsController`

**Public Methods:**
- `store(Request, MemberLoan)` — Record loan payment + updates remaining balance → creates FinancialRecord with origin=`loan_payment`

**Routes Called:**
- **Global:** `finance.loans.payments.store` (POST `/finance/loans/{loan}/payments`)
- **Per-Coop:** `cooperatives.finance.loans.payments.store` (POST `/cooperatives/{cooperative}/finance/loans/{loan}/payments`)

---

### 3. [LoanTypeController](app/Http/Controllers/LoanTypeController.php)
**Location:** `app/Http/Controllers/LoanTypeController.php`  
**Class:** `LoanTypeController`

**Public Methods:**
- `index(Request)` — Returns JSON list of loan types for a cooperative
- `store(Request)` — Create loan type (cooperative-scoped)
- `update(Request, LoanType)` — Update loan type
- `destroy(Request, LoanType)` — Delete loan type

**Routes Called:**
- **Global:** `finance.loan-types.*` (GET/POST/PUT/DELETE `/finance/loan-types`)
- Shared by both global and per-coop contexts (resolved via `resolveCooperativeId()`)

**Internal Methods (private):**
- `resolveCooperativeId(Request)` — Determines which coop's loan types to manage
- `authorizeLoanTypeAccess(Request, LoanType)` — Enforces coop scope

---

### 4. [SavingsController](app/Http/Controllers/SavingsController.php)
**Location:** `app/Http/Controllers/SavingsController.php`  
**Class:** `SavingsController`

**Public Methods:**
- `create(Request)` — Show create savings account form
- `index(Request)` — List savings accounts (global or per-coop)
- `store(Request)` — Create savings account + optional opening deposit
- `show(MemberSavings, Request)` — Display account + transaction history
- `edit(MemberSavings, Request)` — Show edit form
- `update(Request, MemberSavings)` — Update account details (interest rate, status)
- `destroy(MemberSavings, Request)` — Close account (soft delete)
- `calculateInterest(Request, MemberSavings)` — Calculate monthly interest → creates SavingsTransaction + FinancialRecord with origin=`savings`

**Routes Called:**
- **Global:** `finance.savings.*`
- **Per-Coop:** `cooperatives.finance.savings.*`

**Internal Methods (private):**
- `enforceSavingsAccess(MemberSavings, User)` — Checks cooperative scope
- `generateAccountNumber(int)` — Creates unique account number

---

### 5. [SavingsTransactionsController](app/Http/Controllers/SavingsTransactionsController.php)
**Location:** `app/Http/Controllers/SavingsTransactionsController.php`  
**Class:** `SavingsTransactionsController`

**Public Methods:**
- `store(Request, MemberSavings)` — Record deposit/withdrawal → creates FinancialRecord with origin=`savings`

**Routes Called:**
- **Global:** `finance.savings.transactions.store` (POST `/finance/savings/{savings}/transactions`)
- **Per-Coop:** `cooperatives.finance.savings.transactions.store` (POST `/cooperatives/{cooperative}/finance/savings/{savings}/transactions`)

---

### 6. [FinancialRecordsController](app/Http/Controllers/FinancialRecordsController.php)
**Location:** `app/Http/Controllers/FinancialRecordsController.php`  
**Class:** `FinancialRecordsController`

**Public Methods:**
- `index(Request)` — List financial records (ledger entries)
- `show(Request, Cooperative, FinancialRecord)` — Display single record

**Routes Called:**
- **Global:** `finance.financial-records.index` (GET `/finance/financial-records`)
- **Per-Coop:** `cooperatives.finance.financial-records.index` (GET `/cooperatives/{cooperative}/finance/financial-records`)

---

### 7. [FinancialRecordController](app/Http/Controllers/FinancialRecordController.php)
**Location:** `app/Http/Controllers/FinancialRecordController.php`  
**Class:** `FinancialRecordController`

**Public Methods:**
- `create(Request)` — Show create financial record form
- `edit(FinancialRecord, Request)` — Show edit form
- `store(Request)` — Create manual financial record entry
- `update(Request, FinancialRecord)` — Update record
- `destroy(Request, FinancialRecord)` — Delete record
- `index(Request)` — List records (alternative view)

**Routes Called:**
- **Global:** `finance.financial-records.create` (GET), `finance.financial-records.store` (POST), etc.
- **Per-Coop:** `cooperatives.finance.financial-records.create`, `cooperatives.finance.financial-records.store`, etc.

---

### 8. [FundingSourcesController](app/Http/Controllers/FundingSourcesController.php)
**Location:** `app/Http/Controllers/FundingSourcesController.php`  
**Class:** `FundingSourcesController`

**Public Methods:**
- `index(Request)` — List funding sources with filters
- `show(Request, Cooperative, ActivityFundingSource)` — Display funding source details

**Routes Called:**
- **Global:** `finance.funding-sources.index` (GET `/finance/funding-sources`)
- **Per-Coop:** `cooperatives.finance.funding-sources.index` (GET `/cooperatives/{cooperative}/finance/funding-sources`)

---

### 9. [ActivityFundingSourceController](app/Http/Controllers/ActivityFundingSourceController.php)
**Location:** `app/Http/Controllers/ActivityFundingSourceController.php`  
**Class:** `ActivityFundingSourceController`

**Public Methods:**
- `create(Request)` — Show create funding source form
- `edit(ActivityFundingSource, Request)` — Show edit form
- `store(Request)` — Create funding source record
- `update(Request, ActivityFundingSource)` — Update record
- `destroy(Request, ActivityFundingSource)` — Delete record

**Routes Called:**
- **Global:** `finance.funding-sources.create`, `finance.funding-sources.store`, etc.
- **Per-Coop:** `cooperatives.finance.funding-sources.create`, `cooperatives.finance.funding-sources.store`, etc.

---

### 10. [ExternalSupportController](app/Http/Controllers/ExternalSupportController.php)
**Location:** `app/Http/Controllers/ExternalSupportController.php`  
**Class:** `ExternalSupportController`

**Public Methods:**
- `create(Request)` — Show create external support form
- `index(Request)` — List external support records
- `edit(Request, Cooperative, ExternalSupport)` — Show edit form
- `store(Request)` — Create external support → creates FinancialRecord with origin=`external_support`
- `update(Request, Cooperative, ExternalSupport)` — Update record
- `destroy(Request, Cooperative, ExternalSupport)` — Delete record
- `financialRecords(Request)` — List linked financial records

**Routes Called:**
- **Global:** `finance.external-supports.index` (GET `/finance/external-supports`)
- **Per-Coop:** `cooperatives.finance.external-supports.index` (GET `/cooperatives/{cooperative}/finance/external-supports`)

---

### 11. [FinanceReportsController](app/Http/Controllers/FinanceReportsController.php)
**Location:** `app/Http/Controllers/FinanceReportsController.php`  
**Class:** `FinanceReportsController`

**Public Methods:**
- `statements(Request)` — Show financial statement summary (Income/Expense/Grants/Loans/Assets/Liabilities)
- `exportStatements(Request)` — Export statements as file
- `loanPortfolio(Request)` — Show loan portfolio summary
- `exportLoanPortfolio(Request)` — Export as file
- `savingsSummary(Request)` — Show savings account summary
- `exportSavingsSummary(Request)` — Export as file
- `funderAccountability(Request)` — Show funding accountability report
- `exportFunderAccountability(Request)` — Export as file
- `trends(Request)` — Show financial trends
- `exportTrends(Request)` — Export as file

**Routes Called:**
- **Global:** `finance.reports.*` (GET/export `/finance/reports/*`)
- Note: Reports routes are **global only** (no per-coop counterpart)

**Internal Methods (private):**
- `scopedFinancialRecords(Request)` — Filters records by user scope
- `applyScope(&$query, Request)` — Applies cooperative scope to queries

---

### 12. [FinanceOverviewController](app/Http/Controllers/FinanceOverviewController.php)
**Location:** `app/Http/Controllers/FinanceOverviewController.php`  
**Class:** `FinanceOverviewController`

**Public Methods:**
- `index(Request)` — Show finance dashboard with summary cards

**Routes Called:**
- **Global:** `finance.index` (GET `/finance`)

---

### 13. [MemberPortalController](app/Http/Controllers/MemberPortalController.php)
**Location:** `app/Http/Controllers/MemberPortalController.php`  
**Class:** `MemberPortalController`

**Public Methods (Finance-related):**
- `loans(Request)` — List member's own loans
- `showLoan(Request, MemberLoan)` — Show loan details + payment status

**Routes Called:**
- `member-portal.loans` (GET `/member-portal/loans`)
- `member-portal.loans.show` (GET `/member-portal/loans/{loan}`)

---

## SECTION 3 — All Finance Models

### 1. [MemberLoan](app/Models/MemberLoan.php)
- **Table:** `member_loans`
- **Uses CoopScoped Trait:** ✅ YES
- **Relationships:**
  - `belongsTo(Cooperative)` on `coop_id`
  - `belongsTo(Member)` on `member_id`
  - `belongsTo(LoanType)` on `loan_type_id`
  - `hasMany(LoanPayment)` — repayment schedule
- **Fillable:**
  - `coop_id`, `member_id`, `loan_type_id`, `principal`, `interest_rate`, `term_months`, `status`, `purpose`, `approved_by`, `approved_at`, `remarks`, `disbursement_date`, `amount_disbursed`, `disbursement_method`, `disburse_remarks`, `created_by`
- **Casts:** All currency fields to `decimal:2`; dates to `date`; `approved_at` to `datetime`
- **Activity Logging:** ✅ Yes (via `LogsActivity` trait)

---

### 2. [LoanPayment](app/Models/LoanPayment.php)
- **Table:** `loan_payments`
- **Uses CoopScoped Trait:** ✅ YES
- **Relationships:**
  - `belongsTo(MemberLoan)` on `loan_id`
  - `belongsTo(Cooperative)` on `coop_id`
  - `belongsTo(User)` on `recorded_by` (recorder)
- **Fillable:**
  - `loan_id`, `coop_id`, `payment_number`, `principal_due`, `interest_due`, `total_due`, `amount_paid`, `due_date`, `paid_at`, `balance_after`, `status`, `remarks`, `recorded_by`
- **Casts:** All currency fields to `decimal:2`; `due_date` to `date`; `paid_at` to `datetime`
- **Activity Logging:** ✅ Yes

---

### 3. [LoanType](app/Models/LoanType.php)
- **Table:** `loan_types`
- **Uses CoopScoped Trait:** ❌ NO (cooperative-scoped but not via trait)
- **Relationships:**
  - `belongsTo(Cooperative)` on `cooperative_id`
  - `hasMany(MemberLoan)` on `loan_type_id`
- **Fillable:**
  - `name`, `classification`, `description`, `cooperative_id`, `is_active`
- **Casts:** `is_active` to `boolean`

---

### 4. [MemberSavings](app/Models/MemberSavings.php)
- **Table:** `member_savings`
- **Uses CoopScoped Trait:** ✅ YES
- **Relationships:**
  - `belongsTo(Cooperative)` on `coop_id`
  - `belongsTo(Member)` on `member_id`
  - `belongsTo(User)` on `created_by` (creator)
  - `hasMany(SavingsTransaction)` — transaction history
- **Fillable:**
  - `coop_id`, `member_id`, `account_number`, `account_status`, `current_balance`, `interest_rate`, `opened_at`, `closed_at`, `last_interest_calculated`, `created_by`
- **Casts:** All currency fields to `decimal:2`; dates to `date`; `last_interest_calculated` to `datetime`
- **Activity Logging:** ✅ Yes

---

### 5. [SavingsTransaction](app/Models/SavingsTransaction.php)
- **Table:** `savings_transactions`
- **Uses CoopScoped Trait:** ✅ YES
- **Relationships:**
  - `belongsTo(MemberSavings)` on `member_savings_id` (savings)
  - `belongsTo(Cooperative)` on `coop_id`
  - `belongsTo(User)` on `recorded_by` (recorder)
- **Fillable:**
  - `member_savings_id`, `coop_id`, `type`, `amount`, `balance_after`, `remarks`, `recorded_by`, `recorded_at`
- **Casts:** All currency fields to `decimal:2`; `recorded_at` to `datetime`
- **Activity Logging:** ✅ Yes

---

### 6. [FinancialRecord](app/Models/FinancialRecord.php)
- **Table:** `financial_records`
- **Uses CoopScoped Trait:** ✅ YES
- **Relationships:**
  - `belongsTo(Cooperative)` on `coop_id`
- **Fillable:**
  - `coop_id`, `period`, `type`, `amount`, `source`, `purpose`, `date_recorded`, `total_assets`, `total_liabilities`, `net_surplus`, `capital_build_up`, `external_assistance_received`, `type_of_assistance`, `reference_doc`, `recorded_by`, **`origin`** (marks source: `loan_disbursement`, `loan_payment`, `savings`, `external_support`)
- **Casts:** All currency fields to `decimal:2`; `date_recorded` to `date`
- **Activity Logging:** ✅ Yes

---

### 7. [ExternalSupport](app/Models/ExternalSupport.php)
- **Table:** `external_supports`
- **Uses CoopScoped Trait:** ✅ YES
- **Relationships:**
  - `belongsTo(Cooperative)` on `coop_id`
  - `belongsTo(FinancialRecord)` on `financial_record_id`
- **Fillable:**
  - `coop_id`, `financial_record_id`, `support_type`, `provider_name`, `amount`, `date_granted`, `date_completed`, `status`, `remarks`
- **Casts:** `amount` to `decimal:2`; dates to `date`

---

### 8. [ActivityFundingSource](app/Models/ActivityFundingSource.php)
- **Table:** `activity_funding_sources`
- **Uses CoopScoped Trait:** ✅ YES
- **Relationships:**
  - `belongsTo(Activity)` on `activity_id`
  - `belongsTo(Cooperative)` on `coop_id`
- **Fillable:**
  - `activity_id`, `category`, `coop_id`, `funder_name`, `funder_type`, `amount_allocated`, `amount_released`, `date_released`, `status`, `remarks`, `attachment_paths`, `attachment_names`
- **Casts:** Currency fields to `decimal:2`; `date_released` to `date`; `attachment_paths`, `attachment_names` to `array`
- **Activity Logging:** ✅ Yes

---

## SECTION 4 — All Finance Vue Pages

### Finance Module Pages
Located in `resources/js/pages/Finance/`

#### Overview
- **[Finance/Index.vue](resources/js/pages/Finance/Index.vue)**
  - **Layout:** `FinanceShellLayout`
  - **Context:** Global (all cooperatives)
  - **Key Props:** `scopeLabel`, `summary` (loans, savings, financial_records, funding_sources, external_supports)

#### Loans Section
- **[Finance/Loans/Index.vue](resources/js/pages/Finance/Loans/Index.vue)**
  - **Layout:** `FinanceShellLayout`
  - **Context:** Global or per-coop
  - **Key Props:** `loans`, `cooperative`, `cooperatives`, `statuses`, `permissions`, `isCoopContext`
  
- **[Finance/Loans/Create.vue](resources/js/pages/Finance/Loans/Create.vue)**
  - **Layout:** `FinanceShellLayout`
  - **Context:** Global or per-coop
  - **Key Props:** `members`, `loanTypes`, `cooperatives`, `showCooperativePicker`, `preselectedCoopId`, `preselectedMemberId`

- **[Finance/Loans/Show.vue](resources/js/pages/Finance/Loans/Show.vue)**
  - **Layout:** `FinanceShellLayout`
  - **Context:** Global or per-coop
  - **Key Props:** `loan`, `memberLoanCount`, `repaymentSchedule`, `remainingBalance`, `nextPaymentDue`, `permissions`

- **[Finance/Loans/Edit.vue](resources/js/pages/Finance/Loans/Edit.vue)**
  - **Layout:** `FinanceShellLayout`
  - **Context:** Global or per-coop
  - **Key Props:** `loan`, `from`, `cooperative_id`, `isCoopContext`

#### Savings Section
- **[Finance/Savings/Index.vue](resources/js/pages/Finance/Savings/Index.vue)**
  - **Layout:** `FinanceShellLayout`
  - **Context:** Global or per-coop
  - **Key Props:** `savings`, `cooperative`, `cooperatives`, `accountStatuses`, `permissions`

- **[Finance/Savings/Create.vue](resources/js/pages/Finance/Savings/Create.vue)**
  - **Layout:** `FinanceShellLayout`
  - **Context:** Global or per-coop
  - **Key Props:** `members`, `interestRate`, `coop`, `isCoopContext`

- **[Finance/Savings/Show.vue](resources/js/pages/Finance/Savings/Show.vue)**
  - **Layout:** `FinanceShellLayout`
  - **Context:** Global or per-coop
  - **Key Props:** `savings`, `transactions`, `totalInterestEarned`, `permissions`

- **[Finance/Savings/Edit.vue](resources/js/pages/Finance/Savings/Edit.vue)**
  - **Layout:** `FinanceShellLayout`
  - **Context:** Global or per-coop
  - **Key Props:** `savings`, `isCoopContext`

#### Financial Records Section
- **[Finance/FinancialRecords/Index.vue](resources/js/pages/Finance/FinancialRecords/Index.vue)**
  - **Layout:** `FinanceShellLayout`
  - **Context:** Global or per-coop
  - **Key Props:** `records`, `cooperative`, `cooperatives`, `filters`, `permissions`

- **[Finance/FinancialRecords/Create.vue](resources/js/pages/Finance/FinancialRecords/Create.vue)**
  - **Layout:** `FinanceShellLayout`
  - **Context:** Global or per-coop

- **[Finance/FinancialRecords/Show.vue](resources/js/pages/Finance/FinancialRecords/Show.vue)**
  - **Layout:** `FinanceShellLayout`
  - **Context:** Global or per-coop
  - **Key Props:** `record`, `permissions`

- **[Finance/FinancialRecords/Edit.vue](resources/js/pages/Finance/FinancialRecords/Edit.vue)**
  - **Layout:** `FinanceShellLayout`
  - **Context:** Global or per-coop

#### Funding Sources Section
- **[Finance/FundingSources/Index.vue](resources/js/pages/Finance/FundingSources/Index.vue)**
  - **Layout:** `FinanceShellLayout`
  - **Context:** Global or per-coop
  - **Key Props:** `fundingSources`, `activities`, `cooperative`, `cooperatives`, `permissions`

- **[Finance/FundingSources/Create.vue](resources/js/pages/Finance/FundingSources/Create.vue)**
  - **Layout:** `FinanceShellLayout`
  - **Context:** Global or per-coop

- **[Finance/FundingSources/Show.vue](resources/js/pages/Finance/FundingSources/Show.vue)**
  - **Layout:** `FinanceShellLayout`
  - **Context:** Global or per-coop

- **[Finance/FundingSources/Edit.vue](resources/js/pages/Finance/FundingSources/Edit.vue)**
  - **Layout:** `FinanceShellLayout`
  - **Context:** Global or per-coop

#### External Supports Section
- **[Finance/ExternalSupports/Index.vue](resources/js/pages/Finance/ExternalSupports/Index.vue)**
  - **Layout:** `FinanceShellLayout`
  - **Context:** Global or per-coop

#### Reports Section
- **[Finance/Reports/Statements.vue](resources/js/pages/Finance/Reports/Statements.vue)**
  - **Layout:** `FinanceShellLayout`
  - **Context:** Global only

- **[Finance/Reports/LoanPortfolio.vue](resources/js/pages/Finance/Reports/LoanPortfolio.vue)**
  - **Layout:** `FinanceShellLayout`
  - **Context:** Global only

- **[Finance/Reports/SavingsSummary.vue](resources/js/pages/Finance/Reports/SavingsSummary.vue)**
  - **Layout:** `FinanceShellLayout`
  - **Context:** Global only

### Member Portal Finance Pages
Located in `resources/js/pages/Members/`

- **[Members/PortalLoans.vue](resources/js/pages/Members/PortalLoans.vue)**
  - **Layout:** `AppLayout`
  - **Context:** Member portal (single member's loans only)
  - **Key Props:** `member`, `loans`, `permissions`

- **[Members/PortalLoanShow.vue](resources/js/pages/Members/PortalLoanShow.vue)**
  - **Layout:** `AppLayout`
  - **Context:** Member portal (single member's single loan)
  - **Key Props:** `member`, `loan`, `permissions`

---

## SECTION 5 — How Provincial Admin uses Finance

### User Role & Permissions
- **Role:** `Provincial Admin`
- **Key Permission:** `view-all-cooperatives` (grants access to all cooperatives' data)
- **Sidebar:** Finance link appears if user has any of: `read financial-&-support`, `read finance-funding-sources`, `read finance-ledger-entries`, `read finance-member-loans`, `read finance-savings-accounts`, `read finance-reports`

### Step-by-Step Flow

**1. Login**
```
User logs in with Provincial Admin credentials
→ Authentication verified
→ Redirected to /homepage or /dashboard
```

**2. Click Finance in Sidebar**
```
Sidebar renders Finance link: href="/finance"
→ GET /finance
→ FinanceOverviewController@index
```

**3. Finance Dashboard (Finance Shell)**
```
Rendered Page: Finance/Index.vue
Layout: FinanceShellLayout
Shows:
  - 7 tabs: Overview | Funding Sources | Financial Records | Loans | Savings | External Support | Reports
  - Summary cards for all cooperatives (aggregated):
    • Total loans, pending, active, completed
    • Total savings accounts, active accounts, total balance
    • Financial records: income, expense, net surplus
    • Funding sources allocated/released
    • External support count/amount
```

**4. Select a Cooperative (Optional)**
```
On Global Loans Index page (/finance/loans):
  - Provincial Admin sees list of ALL cooperatives' loans
  - Can filter by Cooperative dropdown
  - Selecting a cooperative adds query param: ?coop_id={id}
  - Example: /finance/loans?coop_id=5
  
When coop_id is selected:
  - Query is scoped: WHERE coop_id = 5
  - Show only that cooperative's data
  - Breadcrumb or context label shows cooperative name
```

**5. Create/Edit/Delete Finance Records**
```
Provincial Admin CAN:
  ✅ Create loans for any member in any cooperative
  ✅ Approve/disburse loans
  ✅ Record loan payments → auto-posts to Financial Records
  ✅ Open savings accounts
  ✅ Record deposits/withdrawals → auto-posts to Financial Records
  ✅ Record interest calculations → auto-posts to Financial Records
  ✅ Manually create financial record entries
  ✅ Create/edit funding sources
  ✅ Create/edit external support records
  ✅ View all reports (Statements, Loan Portfolio, Savings Summary, Funder Accountability, Trends)

Example: Create Loan for Member in Coop ABC
  1. Click /finance/loans → Finance/Loans/Index
  2. Click "Create Loan"
  3. GET /finance/loans/create
  4. LoansController@create loads:
     - showCooperativePicker=true (Provincial Admin always sees picker)
     - cooperatives = [all cooperatives]
  5. Finance/Loans/Create renders cooperative dropdown + member dropdown
  6. User selects cooperative "Coop ABC" (id=5)
  7. Member dropdown auto-populates with Coop ABC's active members
  8. User selects member, loan type, principal amount
  9. POST /finance/loans
  10. LoansController@store:
      - Validates member belongs to selected coop
      - Creates MemberLoan record (status=Pending, coop_id=5)
      - Auto-generates repayment schedule via generateRepaymentSchedule()
      - Returns redirect to finance.loans.show
```

**6. Key Controller Methods Handling Provincial Admin Actions**

| Action | Route | Method | Notes |
|--------|-------|--------|-------|
| View all loans | GET `/finance/loans` | `LoansController@index` | `isCoopContext=false`; query scoped by coop_id if provided |
| Create loan | GET `/finance/loans/create` | `LoansController@create` | `showCooperativePicker=true` |
| Submit loan | POST `/finance/loans` | `LoansController@store` | Validates cooperative scope |
| Approve loan | POST `/finance/loans/{loan}/approve` | `LoansController@approve` | Status changes: Pending→Approved |
| Disburse loan | POST `/finance/loans/{loan}/disburse` | `LoansController@disburse` | Creates FinancialRecord(origin=loan_disbursement) |
| Record payment | POST `/finance/loans/{loan}/payments` | `LoanPaymentsController@store` | Creates FinancialRecord(origin=loan_payment) |
| View all savings | GET `/finance/savings` | `SavingsController@index` | Lists all coops' savings; can filter by coop_id |
| Open savings | POST `/finance/savings` | `SavingsController@store` | Creates MemberSavings + optional opening deposit |
| Record transaction | POST `/finance/savings/{savings}/transactions` | `SavingsTransactionsController@store` | Creates FinancialRecord(origin=savings) |
| Calculate interest | POST `/finance/savings/{savings}/calculate-interest` | `SavingsController@calculateInterest` | Creates SavingsTransaction + FinancialRecord |
| View ledger | GET `/finance/financial-records` | `FinancialRecordsController@index` | Lists all records across cooperatives |
| Create record | POST `/finance/financial-records` | `FinancialRecordController@store` | Manual entry |
| View reports | GET `/finance/reports/*` | `FinanceReportsController@*` | Global aggregated data |

---

## SECTION 6 — How Coop Admin uses Finance

### User Role & Permissions
- **Role:** `Coop Admin`
- **Key Characteristic:** Has `coop_id` + lacks `view-all-cooperatives` permission
- **Sidebar Behavior:** Finance link href="**`/cooperatives/my`**" (NOT `/finance`)

### Step-by-Step Flow

**1. Login**
```
Coop Admin logs in with credentials
→ Authentication verified
→ Has coop_id=5 and NO view-all-cooperatives permission
→ Redirected to /homepage or /dashboard
```

**2. Click Finance in Sidebar (AppSidebar.vue)**
```
Computed property in AppSidebar:
  isCoopAdmin = computed(() => auth.isCoopAdmin) → TRUE
  cooperativeLabel = "Cooperative"

If isCoopAdmin:
  financeItem.href = "/cooperatives/my"  (NOT /finance)
```

**3. Navigate to Cooperative Show Page**
```
GET /cooperatives/my
OR
GET /cooperatives/{cooperative}
→ Inertia renders Cooperatives/Show.vue
→ Shows cooperative profile + tabs
→ Finance tab = sub-component
```

**4. Finance Sub-Component in Coop Show**
```
Tabs visible in FinanceShellLayout (when isFromCoopContext=true):
  - Overview (no global version)
  - Funding Sources
  - Financial Records
  - Loans
  - Savings
  - External Support
  - Reports → still routes to global /finance/reports/*

Example: Click "Loans" tab
→ Detects URL path contains /cooperatives/
→ Constructs href: /cooperatives/{id}/finance/loans
→ GET /cooperatives/{id}/finance/loans
```

**5. Loans Management in Per-Coop Context**
```
Route: /cooperatives/5/finance/loans
LoansController@index (called with $cooperative=Cooperative(5)):
  - isCoopContext = TRUE
  - coopContext = Cooperative(5)
  - Query scoped: MemberLoan::where('coop_id', 5)
  - NO cooperatives dropdown (isCoopContext=true)
  
Finance/Loans/Index renders:
  - Loans for Coop #5 ONLY
  - Create button → /cooperatives/5/finance/loans/create
  - All operations stay within per-coop context

Create Loan in Per-Coop Context:
  GET /cooperatives/5/finance/loans/create
  LoansController@create:
    - isCoopContext = TRUE
    - cooperative = Cooperative(5)
    - preselectedCoop = {id:5, name:Coop XYZ, members:[...], loanTypes:[...]}
    - showCooperativePicker = FALSE
    - Coop Admin sees members of Coop 5 ONLY
  
  Finance/Loans/Create renders:
    - NO cooperative dropdown
    - Member picker pre-filled with Coop 5's members
    - Loan type picker pre-filled with Coop 5's loan types
  
  POST /cooperatives/5/finance/loans
  LoansController@store:
    - Enforces coop scope: member.coop_id == 5
    - Creates MemberLoan(coop_id=5)
    - Redirect → /cooperatives/5?tab=finance&subtab=loans (stays in component)
```

**6. Data Scope via CoopScoped Trait**
```
MemberLoan uses CoopScoped trait:
  - In queries: always filtered by coop_id
  - Coop Admin's user.coop_id = 5
  - Controllers check: if (!can('view-all-cooperatives') && coop_id) { WHERE coop_id = coop_id }
  
Example in SavingsController@index:
  if ($user && ! $user->can('view-all-cooperatives') && $user->coop_id) {
    $query->where('coop_id', $user->coop_id);
  }
  
Result: Coop Admin always sees only their cooperative's data
```

**7. Key Routes & Controllers for Coop Admin**

| Action | Route (Per-Coop) | Controller@Method | Coop Scope |
|--------|------------------|-------------------|-----------|
| View loans | `GET /cooperatives/{id}/finance/loans` | `LoansController@index` | coop_id = {id} |
| Create loan | `GET /cooperatives/{id}/finance/loans/create` | `LoansController@create` | Members/LoanTypes filtered by {id} |
| Submit loan | `POST /cooperatives/{id}/finance/loans` | `LoansController@store` | Validates member.coop_id == {id} |
| Approve | `POST /cooperatives/{id}/finance/loans/{loan}/approve` | `LoansController@approve` | Enforces loan.coop_id == {id} |
| Disburse | `POST /cooperatives/{id}/finance/loans/{loan}/disburse` | `LoansController@disburse` | Creates FR(coop_id={id}) |
| Record payment | `POST /cooperatives/{id}/finance/loans/{loan}/payments` | `LoanPaymentsController@store` | Creates FR(coop_id={id}) |
| View savings | `GET /cooperatives/{id}/finance/savings` | `SavingsController@index` | Scoped to {id} only |
| Open savings | `POST /cooperatives/{id}/finance/savings` | `SavingsController@store` | member.coop_id == {id} |
| Record transaction | `POST /cooperatives/{id}/finance/savings/{savings}/transactions` | `SavingsTransactionsController@store` | Creates FR(coop_id={id}) |
| View ledger | `GET /cooperatives/{id}/finance/financial-records` | `FinancialRecordsController@index` | Scoped to {id} only |
| View reports | `GET /finance/reports/statements` | `FinanceReportsController@statements` | Global (scoped by applyScope method) |

**8. Cannot See Global Routes**
```
Coop Admin cannot directly access:
  ❌ /finance
  ❌ /finance/loans
  ❌ /finance/savings
  (Attempting to visit → enforceCoopScope() aborts 403)

Sidebar intentionally omits these for Coop Admin
(href="/cooperatives/my" instead)
```

---

## SECTION 7 — Data Flow for Each Finance Sub-Module

### A) Viewing Loans List (Both Global and Per-Coop)

**Global Context:** `GET /finance/loans`
```
1. Route: finance.loans.index
   ↓
2. LoansController@index(Request, ?Cooperative=NULL)
   - isCoopContext = FALSE
   - coopId = request query('coop_id') or NULL
   - Query: MemberLoan::with(['member', 'loanType'])
   - If user is Coop Admin: WHERE coop_id = user.coop_id
   - If coopId provided: WHERE coop_id = coopId
   - Result: paginated loans
   ↓
3. Finance/Loans/Index.vue
   - Renders loan table
   - If global mode: shows cooperatives dropdown
   - Pagination + filters (status, member_id)
```

**Per-Coop Context:** `GET /cooperatives/5/finance/loans`
```
1. Route: cooperatives.finance.loans.index
   ↓
2. LoansController@index(Request, Cooperative $cooperative)
   - isCoopContext = TRUE
   - coopContext = Cooperative(5)
   - Query: MemberLoan::where('coop_id', 5)
   - Result: paginated loans for Coop 5 ONLY
   ↓
3. Finance/Loans/Index.vue (same component)
   - isCoopContext prop = TRUE
   - Hides cooperatives dropdown
   - Shows cooperative name in breadcrumb
```

---

### B) Creating a Loan (Both Contexts)

**Global:** `GET /finance/loans/create` → `POST /finance/loans`
```
1. LoansController@create
   - showCooperativePicker = TRUE
   - cooperatives = ALL cooperatives with members + loan types loaded
   - Vue renders cooperative dropdown → member dropdown → loan type
   ↓
2. User selects: Coop "ABC" (id=5) → Member "John" (id=10) → LoanType "Personal"
   ↓
3. POST /finance/loans
   LoansController@store:
     - Validates member_id=10 exists & belongs to coop 5
     - Validates loan_type_id matches coop 5
     - DB::transaction:
       a. Create MemberLoan(coop_id=5, member_id=10, status=Pending)
       b. generateRepaymentSchedule: Creates LoanPayment records for each month
       c. Attach files if provided
     - Log activity
   ↓
4. Redirect to finance.loans.show
```

**Per-Coop:** `GET /cooperatives/5/finance/loans/create` → `POST /cooperatives/5/finance/loans`
```
1. LoansController@create(Request, cooperative=Cooperative(5))
   - showCooperativePicker = FALSE
   - preselectedCoop = {id:5, name:"Coop ABC", members:[...], loanTypes:[...]}
   - Members/LoanTypes pre-filtered to Coop 5
   ↓
2. User selects: Member "Jane" (id=11) → LoanType "Business"
   ↓
3. POST /cooperatives/5/finance/loans
   LoansController@store:
     - Validates member_id=11 is in Coop 5
     - Validates loan_type_id is in Coop 5
     - Creates MemberLoan(coop_id=5, member_id=11)
     - generateRepaymentSchedule(loan)
   ↓
4. Redirect to /cooperatives/5?tab=finance&subtab=loans
```

---

### C) Approving a Loan (Both Contexts)

**Flow:**
```
1. Loan detail page shows "Approve" button
   (visible if user.can('approve finance-member-loans') OR 'approve-major finance-member-loans')
   ↓
2. User clicks → shows modal/form with optional "remarks" field
   ↓
3. POST /finance/loans/{loan}/approve (or per-coop variant)
   LoansController@approve(Request, MemberLoan $loan):
     - Checks: loan.status in [Pending, Rejected]
     - Updates: status=Approved, approved_by=user.id, approved_at=NOW
     - Logs activity
   ↓
4. Redirect back with success message
   - NO FinancialRecord created (approval is not a transaction)
```

---

### D) Viewing Savings (Both Contexts)

**Global:** `GET /finance/savings`
```
1. Route: finance.savings.index
   ↓
2. SavingsController@index:
   - Query: MemberSavings::with('member')
   - If user is Coop Admin: WHERE coop_id = user.coop_id
   - Result: All savings accounts (or filtered by coop)
   ↓
3. Finance/Savings/Index.vue
   - Renders table: account_number | member | balance | interest_rate | status
   - Filters: account_status dropdown
   - If global: cooperatives dropdown for drill-down
```

**Per-Coop:** `GET /cooperatives/5/finance/savings`
```
1. SavingsController@index:
   - isCoopContext = TRUE
   - Query: MemberSavings::where('coop_id', 5)
   - Result: Savings for Coop 5 only
   ↓
2. Finance/Savings/Index.vue
   - isCoopContext = TRUE
   - No cooperatives dropdown
   - Shows Coop 5's accounts only
```

---

### E) Viewing Financial Records / Ledger (Both Contexts)

**Global:** `GET /finance/financial-records`
```
1. FinancialRecordsController@index:
   - Query: FinancialRecord::with('cooperative')
   - If user is Coop Admin: WHERE coop_id = user.coop_id
   - Filters: search (period|source|purpose), type (Income|Expense|Loan|Grant)
   ↓
2. Finance/FinancialRecords/Index.vue
   - Renders table: period | type | amount | source | purpose | date_recorded
   - Shows ALL cooperatives' records (global admin)
   - If global: cooperatives dropdown
```

**Per-Coop:** `GET /cooperatives/5/finance/financial-records`
```
1. FinancialRecordsController@index:
   - isCoopContext = TRUE
   - Query: FinancialRecord::where('coop_id', 5)
   ↓
2. Finance/FinancialRecords/Index.vue
   - Shows Coop 5's ledger entries only
   - All auto-posted records (origin field tracks source)
```

---

### F) Viewing Funding Sources (Both Contexts)

**Global:** `GET /finance/funding-sources`
```
1. FundingSourcesController@index:
   - Query: ActivityFundingSource::with('activity', 'cooperative')
   - Filters: search, status, funder_type
   ↓
2. Finance/FundingSources/Index.vue
   - Renders: funder_name | activity | funder_type | amount_allocated | amount_released
   - If global: cooperatives dropdown
```

**Per-Coop:** `GET /cooperatives/5/finance/funding-sources`
```
1. FundingSourcesController@index:
   - Query: ActivityFundingSource::where('coop_id', 5)
   ↓
2. Vue shows Coop 5's funding sources only
```

---

### G) Viewing External Support (Both Contexts)

**Global:** `GET /finance/external-supports`
```
1. ExternalSupportController@index:
   - Query: ExternalSupport::with('cooperative', 'financialRecord')
   ↓
2. Finance/ExternalSupports/Index.vue
   - Renders: support_type | provider_name | amount | date_granted | status
```

**Per-Coop:** `GET /cooperatives/5/finance/external-supports`
```
1. ExternalSupportController@index:
   - Query: ExternalSupport::where('coop_id', 5)
   ↓
2. Vue shows Coop 5's external support records only
```

---

## SECTION 8 — Auto-posting to Financial Records Ledger

When certain finance actions occur, the system **automatically creates a FinancialRecord row** to maintain an audit trail and balance sheet. The `origin` field tracks the source.

### A) Loan Disbursement
**Trigger:** User clicks "Disburse" on an Approved loan

**Flow:**
```
POST /finance/loans/{loan}/disburse
LoansController@disburse:
  DB::transaction:
    1. Update MemberLoan: status=Active, disbursement_date=NOW, amount_disbursed=$amount
    2. CREATE FinancialRecord:
       - coop_id = loan.coop_id
       - period = NOW.format('Y-m')
       - type = 'Loan'  ← Loan disbursement is a liability
       - amount = loan.principal
       - source = 'loan_release'
       - purpose = 'Loan released to [Member Name] - [Loan Type Name]'
       - date_recorded = NOW
       - reference_doc = (string) loan.id
       - recorded_by = auth().user().name
       - origin = 'loan_disbursement'  ← KEY FIELD
```

**FinancialRecord Row Created:**
```
| coop_id | period | type | amount | source | origin |
|---------|--------|------|--------|--------|--------|
| 5       | 2026-05| Loan | 50000  | loan_release | loan_disbursement |
```

---

### B) Loan Payment Recording
**Trigger:** User records a payment on an active loan

**Flow:**
```
POST /finance/loans/{loan}/payments
LoanPaymentsController@store:
  DB::transaction:
    1. Update LoanPayment: amount_paid, paid_at, balance_after, status
    2. CREATE FinancialRecord:
       - type = 'Income'  ← Payment received is income
       - amount = $validated['amount']
       - source = 'loan_payment'
       - purpose = 'Loan payment from [Member] - [Loan Type]'
       - origin = 'loan_payment'
       - reference_doc = (string) loan.id
    3. If balance <= 0: Update MemberLoan status = 'Completed'
```

**FinancialRecord Row Created:**
```
| coop_id | period | type   | amount | source | origin |
|---------|--------|--------|--------|--------|--------|
| 5       | 2026-05| Income | 2500   | loan_payment | loan_payment |
```

---

### C) Savings Deposit/Withdrawal
**Trigger:** User records deposit or withdrawal

**Flow:**
```
POST /finance/savings/{savings}/transactions
SavingsTransactionsController@store:
  Validated['type'] = 'Deposit' or 'Withdrawal'
  
  DB::transaction:
    1. Create SavingsTransaction: type, amount, balance_after, recorded_at
    2. Update MemberSavings: current_balance = new balance
    3. CREATE FinancialRecord:
       - type = 'Income' (if Deposit) OR 'Expense' (if Withdrawal)
       - amount = $amount
       - source = 'Savings Deposit' or 'Savings Withdrawal'
       - purpose = 'Savings account transaction'
       - origin = 'savings'
       - recorded_by = auth().user().name
```

**FinancialRecord Row Created:**
```
| Deposit:                                            |
| coop_id | type   | amount | source | origin |
| 5       | Income | 1000   | Savings Deposit | savings |

| Withdrawal:                                         |
| coop_id | type    | amount | source | origin |
| 5       | Expense | 500    | Savings Withdrawal | savings |
```

---

### D) Savings Interest Calculation
**Trigger:** User or system calculates monthly interest

**Flow:**
```
POST /finance/savings/{savings}/calculate-interest
SavingsController@calculateInterest:
  interestAmount = (current_balance * interest_rate / 100) / 12
  
  DB::transaction:
    1. Create SavingsTransaction: type='Interest', amount=interestAmount
    2. Update MemberSavings: current_balance += interestAmount, last_interest_calculated=NOW
    3. CREATE FinancialRecord:
       - type = 'Income'
       - amount = interestAmount
       - source = 'Savings Interest'
       - purpose = 'Interest credited to savings account'
       - origin = 'savings'
```

**FinancialRecord Row Created:**
```
| coop_id | type   | amount | source | origin |
| 5       | Income | 50.00  | Savings Interest | savings |
```

---

### E) External Support Recording
**Trigger:** User creates an external support record

**Flow:**
```
POST /cooperatives/{coop}/finance/external-supports
ExternalSupportController@store:
  Validate support data
  
  DB::transaction:
    1. Create ExternalSupport record
    2. typeMap = map support_type to FinancialRecord type:
       - 'Grant' → 'Grant'
       - 'Loan' → 'Loan'
       - 'Equipment', 'Training', 'Technical Assistance', 'Other' → 'Support'
    3. CREATE FinancialRecord:
       - type = typeMap[support_type]
       - amount = support.amount
       - source = support.provider_name
       - purpose = 'External support received: [support_type]'
       - external_assistance_received = support.amount
       - type_of_assistance = support.support_type
       - reference_doc = 'external_support_' . support.id
       - origin = 'external_support'
       - recorded_by = auth().user().name
    4. Update ExternalSupport: financial_record_id = newly created FR id
```

**FinancialRecord Row Created:**
```
| coop_id | type   | amount | source | type_of_assistance | origin |
| 5       | Grant  | 25000  | JMMC   | Grant              | external_support |
```

---

## SECTION 9 — File Structure Summary

```
c:\Users\PC\Desktop\PICTO-COOP-MGMT-SYS

ROUTES
├── routes/web.php
│   ├── Group: /finance/* (global finance routes)
│   ├── Group: /cooperatives/{id}/finance/* (per-coop finance routes)
│   └── Group: /member-portal/loans* (member portal)

CONTROLLERS (app/Http/Controllers/)
├── FinanceOverviewController.php
│   └── index() → GET /finance
├── LoansController.php
│   ├── index, create, store, show, edit, update, destroy
│   └── approve(), disburse()
├── LoanPaymentsController.php
│   └── store() → record payment + auto-post FR
├── LoanTypeController.php
│   ├── index, store, update, destroy
├── SavingsController.php
│   ├── index, create, store, show, edit, update, destroy
│   └── calculateInterest() → auto-post interest FR
├── SavingsTransactionsController.php
│   └── store() → record deposit/withdrawal + auto-post FR
├── FinancialRecordsController.php
│   └── index, show
├── FinancialRecordController.php
│   ├── create, store, edit, update, destroy
├── FundingSourcesController.php
│   ├── index, show
├── ActivityFundingSourceController.php
│   ├── create, edit, store, update, destroy
├── ExternalSupportController.php
│   ├── create, edit, store, update, destroy
│   └── index, financialRecords()
├── FinanceReportsController.php
│   ├── statements, loanPortfolio, savingsSummary, funderAccountability, trends
│   ├── exportStatements, exportLoanPortfolio, exportSavingsSummary, etc.
└── MemberPortalController.php
    ├── loans() → GET /member-portal/loans
    └── showLoan() → GET /member-portal/loans/{loan}

MODELS (app/Models/)
├── MemberLoan.php ✅ CoopScoped
│   ├── relationships: cooperative, member, loanType, payments
│   └── fillable: principal, interest_rate, term_months, status, approved_by, etc.
├── LoanPayment.php ✅ CoopScoped
│   ├── relationships: loan, cooperative, recorder
│   └── fillable: amount_paid, due_date, paid_at, balance_after, status
├── LoanType.php (NOT CoopScoped, but has cooperative relation)
│   ├── relationships: cooperative, loans
│   └── fillable: name, classification, is_active
├── MemberSavings.php ✅ CoopScoped
│   ├── relationships: cooperative, member, creator, transactions
│   └── fillable: account_number, current_balance, interest_rate, account_status
├── SavingsTransaction.php ✅ CoopScoped
│   ├── relationships: savings, cooperative, recorder
│   └── fillable: type, amount, balance_after, recorded_at
├── FinancialRecord.php ✅ CoopScoped
│   ├── relationships: cooperative
│   └── fillable: period, type, amount, source, purpose, origin, reference_doc, etc.
├── ExternalSupport.php ✅ CoopScoped
│   ├── relationships: cooperative, financialRecord
│   └── fillable: support_type, provider_name, amount, date_granted, status
└── ActivityFundingSource.php ✅ CoopScoped
    ├── relationships: activity, cooperative
    └── fillable: funder_name, amount_allocated, amount_released, status

VUE PAGES (resources/js/pages/)
├── Finance/
│   ├── Index.vue (overview dashboard)
│   ├── Loans/
│   │   ├── Index.vue, Create.vue, Show.vue, Edit.vue
│   ├── Savings/
│   │   ├── Index.vue, Create.vue, Show.vue, Edit.vue
│   ├── FinancialRecords/
│   │   ├── Index.vue, Create.vue, Show.vue, Edit.vue
│   ├── FundingSources/
│   │   ├── Index.vue, Create.vue, Show.vue, Edit.vue
│   ├── ExternalSupports/
│   │   └── Index.vue
│   └── Reports/
│       ├── Statements.vue, LoanPortfolio.vue, SavingsSummary.vue, Trends.vue
└── Members/
    ├── PortalLoans.vue (list of member's loans)
    └── PortalLoanShow.vue (single loan detail)

LAYOUTS (resources/js/layouts/)
├── FinanceShellLayout.vue
│   └── Manages 7 tabs + cooperative context detection
├── AppLayout.vue (used for member portal)
└── AppSidebarLayout.vue

COMPONENTS (resources/js/components/)
├── AppSidebar.vue
│   ├── Finance link: href="/finance" (global) or "/cooperatives/my" (coop admin)
│   └── Computed properties for permission checks
└── (Finance-specific panels/components in resources/js/components/panels/)

DATABASE MIGRATIONS (database/migrations/)
├── *_create_member_loans_table
├── *_create_loan_payments_table
├── *_create_loan_types_table
├── *_create_member_savings_table
├── *_create_savings_transactions_table
├── *_create_financial_records_table
├── *_create_external_supports_table
└── *_create_activity_funding_sources_table

TRAITS (app/Models/Concerns/)
└── CoopScoped.php
    └── Automatically filters queries by coop_id + enforces cooperative isolation
```

---

## Summary Table: Routes by Context

| Feature | Global Route | Per-Coop Route | Member Portal |
|---------|--------------|----------------|---------------|
| **Loans** | `/finance/loans/*` | `/cooperatives/{id}/finance/loans/*` | `/member-portal/loans` |
| **Savings** | `/finance/savings/*` | `/cooperatives/{id}/finance/savings/*` | ❌ N/A |
| **Financial Records** | `/finance/financial-records/*` | `/cooperatives/{id}/finance/financial-records/*` | ❌ N/A |
| **Funding Sources** | `/finance/funding-sources/*` | `/cooperatives/{id}/finance/funding-sources/*` | ❌ N/A |
| **External Support** | `/finance/external-supports` | `/cooperatives/{id}/finance/external-supports/*` | ❌ N/A |
| **Reports** | `/finance/reports/*` | (Global only) | ❌ N/A |

---

## Key Architectural Patterns

1. **Dual-Route Pattern:** Every finance sub-module (except reports) exists in both global (`/finance/`) and per-coop (`/cooperatives/{id}/finance/`) contexts
2. **CoopScoped Trait:** Automatic query filtering enforces data isolation by cooperative
3. **Inertia Context Flags:** Controllers pass `isCoopContext`, `coopContext`, `showCooperativePicker` to Vue for UI adaptation
4. **Auto-posting:** Finance transactions automatically create `FinancialRecord` entries with `origin` field tracking the source
5. **Layout-Based Navigation:** `FinanceShellLayout` detects URL context and routes to appropriate sub-routes
6. **Permission Layering:** Permissions control access to actions; cooperative scope controls data visibility
7. **Activity Logging:** All finance models use `LogsActivity` trait for audit trail

---

**END OF ARCHITECTURE DOCUMENT**
