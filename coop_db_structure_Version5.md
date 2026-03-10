# Provincial Cooperative Database Structure

> **System:** Provincial Cooperative Management Information System (PCMIS)
> **Last Updated:** 2026-03-09
> **Purpose:** Standardized data schema for managing cooperative profiles, members,
> officers, activities, financial performance, and training records at the provincial level.

---

## Table of Contents

1. [Cooperative Master Profile](#1-cooperative-master-profile)
2. [Members Profile Database](#2-members-profile-database)
   - [2a. Coop Services Availed](#2a--member-coop-services-availed-dynamic)
   - [2b. Member Sector History](#2b--member-sector-history-dynamic)
3. [Officers and Committees](#3-officers-and-committees)
   - [3a. Officer / Term History](#3a--officer-history--term-history-table-dynamic)
   - [3b. Committee Members Junction](#3b--committee-members-relational-table-junction-table)
   - [3c. Cooperative Status History](#3c--cooperative-status-history-table-dynamic)
4. [Activities & Projects Database](#4-activities--projects-database)
   - [4a. Activity Participants](#4a--activity-participants-relational-table-junction-table)
   - [4b. Funding Source Details](#4b--funding-source-details-table-relational)
5. [Financial & Support Data](#5-financial--support-data)
   - [5a. Government/External Support](#5a--governmentexternal-support-relational-table)
6. [Training & Capacity Building](#6-training--capacity-building--skills-development-monitoring)
   - [6a. Training Participants](#6a--training-participants-relational-table-junction-table)
   - [6b. Skills Inventory](#6b--skills-inventory--development-tracking-table-dynamic)
7. [Audit Log](#7--audit-log--change-history-table-system-wide-dynamic)
8. [Entity Relationship Summary](#entity-relationship-summary)
9. [Legend](#legend)

---

## 1. Cooperative Master Profile

> **📋 Description:** The central registry of all cooperatives under the province.
> This is the root/parent table of the entire system. All other tables reference
> this table via `CoopID`.
>
> **🎯 Purpose:** To maintain an official, up-to-date profile of every registered
> cooperative — including its type, location, legal status, and accreditation details.

| Field               | Type      | Description                              |
|---------------------|-----------|------------------------------------------|
| CoopID              | INT (PK)  | Unique Cooperative Identifier            |
| Name                | VARCHAR   | Official Name of the Cooperative         |
| RegistrationNumber  | VARCHAR   | Government Registration Number           |
| CoopType            | VARCHAR   | Credit, Marketing, Multi-purpose, etc.   |
| DateEstablished     | DATE      | Date of official registration            |
| Address             | TEXT      | Full address                             |
| Province            | VARCHAR   | Province/District                        |
| Email               | VARCHAR   | Contact email                            |
| Phone               | VARCHAR   | Contact phone number                     |
| Status              | ENUM      | Active, Inactive, Dissolved, Suspended   |
| AccreditationStatus | VARCHAR   | Accredited / Pending / Revoked           |
| AccreditationDate   | DATE      | Date of latest accreditation             |
| CreatedAt           | TIMESTAMP | Record creation timestamp                |
| UpdatedAt           | TIMESTAMP | Last update timestamp                    |

---

## 2. Members Profile Database

> **📋 Description:** Contains the personal, demographic, socio-economic, and
> membership details of every individual cooperative member.
>
> **🎯 Purpose:** To serve as the master record of cooperative members — capturing
> not just identity but also their educational background, primary livelihood,
> sector classification, and membership standing — essential for reporting and
> targeting interventions.

| Field                 | Type      | Description                                                      |
|-----------------------|-----------|------------------------------------------------------------------|
| MemberID              | INT (PK)  | Unique Member Identifier                                         |
| CoopID                | INT (FK)  | References CoopMasterProfile                                     |
| FirstName             | VARCHAR   | First name                                                       |
| LastName              | VARCHAR   | Last name                                                        |
| BirthDate             | DATE      | Date of birth                                                    |
| Gender                | ENUM      | Male, Female, Other                                              |
| Address               | TEXT      | Residential address                                              |
| Phone                 | VARCHAR   | Contact number                                                   |
| Email                 | VARCHAR   | Email address                                                    |
| DateJoined            | DATE      | Date became a member                                             |
| MembershipType        | ENUM      | Regular, Associate                                               |
| MembershipStatus      | ENUM      | Active, Suspended, Resigned, Deceased                            |
| ShareCapital          | DECIMAL   | Total share capital/investment                                   |
| EducationalAttainment | ENUM      | No Formal Education, Elementary, High School, Vocational, College, Post-Graduate |
| PrimaryLivelihood     | VARCHAR   | Main source of income (e.g., Farming, Fishing, Vending, Employment) |
| Sector                | ENUM      | Farmer, Fishfolk, Employee, Entrepreneur, Youth, Women, Senior Citizen, PWD, Other |
| CreatedAt             | TIMESTAMP | Record creation timestamp                                        |
| UpdatedAt             | TIMESTAMP | Last update timestamp                                            |

---

## 2a. 🔄 Member Coop Services Availed *(Dynamic)*

> **📋 Description:** A dynamic record of all cooperative services that each
> member has availed over their membership lifetime. One member can avail
> multiple services (e.g., loan in 2023, marketing in 2024, training in 2025).
>
> **🎯 Purpose:** To monitor service utilization per member, enabling the cooperative
> and provincial offices to assess accessibility, demand, and frequency of services.

| Field       | Type      | Description                                                                       |
|-------------|-----------|-----------------------------------------------------------------------------------|
| AvailmentID | INT (PK)  | Unique Availment Record ID                                                        |
| MemberID    | INT (FK)  | References Members Profile                                                        |
| CoopID      | INT (FK)  | References CoopMasterProfile                                                      |
| ServiceType | ENUM      | Loan, Marketing, Training, Savings, Insurance, Technical Assistance, Other        |
| ServiceDetail | VARCHAR | Specific description of the service availed                                       |
| DateAvailed | DATE      | Date the service was availed                                                      |
| Amount      | DECIMAL   | Amount involved (if applicable, e.g., loan amount)                                |
| Status      | ENUM      | Active, Completed, Pending, Cancelled                                             |
| ReferenceNo | VARCHAR   | Reference document or transaction number                                          |
| Remarks     | TEXT      | Additional notes                                                                  |
| RecordedBy  | VARCHAR   | Admin/User who recorded                                                           |
| CreatedAt   | TIMESTAMP | Record creation timestamp                                                         |

---

## 2b. 🔄 Member Sector History *(Dynamic)*

> **📋 Description:** Logs every change in a member's sector classification or
> primary livelihood over time.
>
> **🎯 Purpose:** To track socio-economic mobility and reclassification of
> members — useful for longitudinal reporting and impact assessment of cooperative
> programs on members' livelihoods.

| Field              | Type      | Description                               |
|--------------------|-----------|-------------------------------------------|
| SectorHistoryID    | INT (PK)  | Unique Record ID                          |
| MemberID           | INT (FK)  | References Members Profile                |
| PreviousSector     | VARCHAR   | Sector before the change                  |
| NewSector          | VARCHAR   | Updated sector classification             |
| PreviousLivelihood | VARCHAR   | Livelihood before the change              |
| NewLivelihood      | VARCHAR   | Updated primary livelihood                |
| ChangeReason       | TEXT      | Reason for reclassification               |
| ChangedBy          | VARCHAR   | Admin/User who updated the record         |
| ChangedAt          | TIMESTAMP | Timestamp of the change                   |

---

## 3. Officers and Committees

> **📋 Description:** Records the currently active officers and committee members
> of each cooperative, including their position, committee assignment, and term.
>
> **🎯 Purpose:** To maintain a clear, current record of cooperative governance —
> who holds what position, in which committee, and during what period. Serves as
> the basis for accountability and reporting on cooperative leadership.

| Field     | Type      | Description                                     |
|-----------|-----------|-------------------------------------------------|
| OfficerID | INT (PK)  | Unique Officer Identifier                       |
| MemberID  | INT (FK)  | References Members Profile                      |
| CoopID    | INT (FK)  | References CoopMasterProfile                    |
| Position  | VARCHAR   | Chairman, Treasurer, Secretary, etc.            |
| Committee | VARCHAR   | Audit, Education, Election, Credit, etc.        |
| TermStart | DATE      | Start of term                                   |
| TermEnd   | DATE      | End of term                                     |
| Status    | ENUM      | Active, Retired, Removed, Resigned              |
| CreatedAt | TIMESTAMP | Record creation timestamp                       |
| UpdatedAt | TIMESTAMP | Last update timestamp                           |

---

## 3a. 🔄 Officer History / Term History Table *(Dynamic)*

> **📋 Description:** A historical log of every officer's terms, positions held,
> committee assignments, and status changes across multiple election years.
>
> **🎯 Purpose:** To provide a complete and auditable leadership history of the
> cooperative — tracking re-elections, position changes, removals, and resignations
> over the cooperative's lifetime. Enables trend analysis of governance stability.

| Field           | Type      | Description                                          |
|-----------------|-----------|------------------------------------------------------|
| HistoryID       | INT (PK)  | Unique History Record ID                             |
| OfficerID       | INT (FK)  | References Officers and Committees                   |
| MemberID        | INT (FK)  | References Members Profile                           |
| CoopID          | INT (FK)  | References CoopMasterProfile                         |
| Position        | VARCHAR   | Position held during this term                       |
| Committee       | VARCHAR   | Committee assignment during this term                |
| TermStart       | DATE      | Term start date                                      |
| TermEnd         | DATE      | Term end date                                        |
| Status          | ENUM      | Active, Completed, Removed, Resigned                 |
| ReasonForChange | TEXT      | Reason for change (election, resignation, removal)   |
| ElectionYear    | YEAR      | Year of election if applicable                       |
| RecordedBy      | VARCHAR   | Admin/user who recorded the change                   |
| RecordedAt      | TIMESTAMP | Timestamp of the record entry                        |

---

## 3b. 🔄 Committee Members Relational Table *(Junction Table)*

> **📋 Description:** A many-to-many junction table that links individual members
> to their respective committee assignments within a cooperative.
>
> **🎯 Purpose:** To track committee memberships separately from elected officer
> positions — allowing multiple members to serve in multiple committees simultaneously,
> with date ranges and statuses for each assignment.

| Field             | Type      | Description                                |
|-------------------|-----------|--------------------------------------------|
| CommitteeMemberID | INT (PK)  | Unique ID                                  |
| CoopID            | INT (FK)  | References CoopMasterProfile               |
| MemberID          | INT (FK)  | References Members Profile                 |
| CommitteeName     | VARCHAR   | Name of Committee                          |
| Role              | VARCHAR   | Chairperson, Member, Secretary, etc.       |
| DateAssigned      | DATE      | Date assigned to committee                 |
| DateRemoved       | DATE      | Date removed (nullable if still active)    |
| Status            | ENUM      | Active, Inactive                           |

---

## 3c. 🔄 Cooperative Status History Table *(Dynamic)*

> **📋 Description:** A chronological log of every official status change the
> cooperative has undergone (e.g., Active → Suspended → Active, or
> Accredited → Revoked).
>
> **🎯 Purpose:** To maintain a transparent and auditable record of the cooperative's
> operational and legal standing over time — critical for regulatory compliance,
> monitoring by CDA, and provincial oversight reporting.

| Field           | Type      | Description                                      |
|-----------------|-----------|--------------------------------------------------|
| StatusHistoryID | INT (PK)  | Unique Status History ID                         |
| CoopID          | INT (FK)  | References CoopMasterProfile                     |
| PreviousStatus  | VARCHAR   | Status before the change                         |
| NewStatus       | VARCHAR   | Status after the change                          |
| ChangeReason    | TEXT      | Reason for status change                         |
| ChangedBy       | VARCHAR   | Admin/User who made the change                   |
| ChangedAt       | TIMESTAMP | When the change happened                         |
| Remarks         | TEXT      | Additional notes                                 |

---

## 4. Activities & Projects Database

> **📋 Description:** Tracks all programs, projects, events, and outreach activities
> conducted by or for each cooperative — including planning, implementation, funding,
> and results data.
>
> **🎯 Purpose:** To document what each cooperative actually does — monitoring
> progress, accountability of funds, beneficiary reach, and partnership arrangements.
> Supports M&E (Monitoring and Evaluation) reporting at the provincial level.

| Field                          | Type      | Description                                                        |
|--------------------------------|-----------|--------------------------------------------------------------------|
| ActivityID                     | INT (PK)  | Unique Activity Identifier                                         |
| CoopID                         | INT (FK)  | References CoopMasterProfile                                       |
| Title                          | VARCHAR   | Activity/Project name                                              |
| Description                    | TEXT      | Detailed description of the activity                               |
| Category                       | ENUM      | Project, Outreach, Event, Livelihood, Training, Infrastructure, Other |
| DateStarted                    | DATE      | Start date                                                         |
| DateEnded                      | DATE      | End date (nullable if ongoing)                                     |
| Status                         | ENUM      | Planned, In Progress, Completed, Cancelled                         |
| ResponsibleOfficer             | INT (FK)  | References Officers and Committees                                 |
| FundingSource                  | VARCHAR   | Primary funding source summary (e.g., LGU, DA, CDA, Coop Fund)    |
| Budget                         | DECIMAL   | Total allocated budget                                             |
| ActualExpense                  | DECIMAL   | Total actual amount spent                                          |
| TargetMemberBeneficiaries      | INT       | Target number of member-beneficiaries                              |
| TargetCommunityBeneficiaries   | INT       | Target number of community/external beneficiaries                  |
| ActualMemberBeneficiaries      | INT       | Actual number of member-beneficiaries reached                      |
| ActualCommunityBeneficiaries   | INT       | Actual number of community/external beneficiaries reached          |
| ImplementingPartner            | VARCHAR   | Partner agency or organization (e.g., DA, DOLE, LGU, NGO)         |
| Outcomes                       | TEXT      | Results, outputs, and impact narrative                             |
| Remarks                        | TEXT      | Additional notes or observations                                   |
| CreatedAt                      | TIMESTAMP | Record creation timestamp                                          |

---

## 4a. 🔄 Activity Participants Relational Table *(Junction Table)*

> **📋 Description:** A many-to-many junction table linking cooperative members
> to specific activities/projects they participated in.
>
> **🎯 Purpose:** To record individual-level participation in activities — enabling
> detailed beneficiary tracking, attendance monitoring, and per-member impact
> assessment for each project or event conducted.

| Field         | Type      | Description                               |
|---------------|-----------|-------------------------------------------|
| ParticipantID | INT (PK)  | Unique ID                                 |
| ActivityID    | INT (FK)  | References Activities & Projects          |
| MemberID      | INT (FK)  | References Members Profile                |
| Role          | VARCHAR   | Participant, Organizer, Volunteer, etc.   |
| DateJoined    | DATE      | Date of participation                     |
| IsBeneficiary | BOOLEAN   | Whether this participant is a beneficiary |
| Remarks       | TEXT      | Additional notes                          |

---

## 4b. 🔄 Funding Source Details Table *(Relational)*

> **📋 Description:** A detailed breakdown of funding sources when an activity
> or project has multiple funders or funding tranches.
>
> **🎯 Purpose:** To support transparent financial accountability per activity —
> tracking how much was pledged vs. released per funding source, and the current
> release status. Especially useful for multi-funded livelihood or infrastructure projects.

| Field           | Type      | Description                                         |
|-----------------|-----------|-----------------------------------------------------|
| FundingID       | INT (PK)  | Unique Funding Record ID                            |
| ActivityID      | INT (FK)  | References Activities & Projects                    |
| CoopID          | INT (FK)  | References CoopMasterProfile                        |
| FunderName      | VARCHAR   | Name of funding agency or source                    |
| FunderType      | ENUM      | Government, NGO, Private, Coop Fund, Donor          |
| AmountAllocated | DECIMAL   | Allocated amount from this funding source           |
| AmountReleased  | DECIMAL   | Actual amount released/received                     |
| DateReleased    | DATE      | Date funds were released                            |
| Status          | ENUM      | Released, Pending, Partially Released               |
| Remarks         | TEXT      | Additional notes                                    |

---

## 5. Financial & Support Data

> **📋 Description:** The main financial ledger of each cooperative — recording
> income, expenses, grants, loans, and key financial performance indicators
> including assets, liabilities, net surplus, and capital build-up on a
> periodic (annual/quarterly) basis.
>
> **🎯 Purpose:** To monitor the financial health and performance of cooperatives
> over time. Supports provincial reporting on cooperative viability, sustainability,
> and the impact of government/external assistance. Enables year-over-year
> comparison of financial indicators.
>
> ⚠️ **Note on Redundancy:** `ExternalAssistanceReceived` and `TypeOfAssistance`
> are summarized here as financial snapshot fields. The detailed per-assistance
> records (agency, amount, date, status) are stored in **Table 5a** to avoid
> duplication.

| Field                      | Type      | Description                                                            |
|----------------------------|-----------|------------------------------------------------------------------------|
| FinanceID                  | INT (PK)  | Unique Financial Record ID                                             |
| CoopID                     | INT (FK)  | References CoopMasterProfile                                           |
| Period                     | VARCHAR   | Reporting period (e.g., 2025, 2025-Q1)                                |
| Type                       | ENUM      | Income, Expense, Grant, Loan, Support, Capital                         |
| Amount                     | DECIMAL   | Financial amount for this transaction/entry                            |
| Source                     | VARCHAR   | Source or recipient of funds                                           |
| Purpose                    | TEXT      | Description/purpose of the transaction                                 |
| DateRecorded               | DATE      | Date of transaction                                                    |
| TotalAssets                | DECIMAL   | Total assets of the cooperative for this period                        |
| TotalLiabilities           | DECIMAL   | Total liabilities of the cooperative for this period                   |
| NetSurplus                 | DECIMAL   | Net surplus (profit/loss) for this period                              |
| CapitalBuildUp             | DECIMAL   | Total capital build-up (accumulated share capital + reserves)          |
| ExternalAssistanceReceived | DECIMAL   | Total monetary value of external assistance received this period       |
| TypeOfAssistance           | ENUM      | Grant, Loan, Training, Equipment, Technical Assistance, Other          |
| ReferenceDoc               | VARCHAR   | Supporting document reference number                                   |
| RecordedBy                 | VARCHAR   | Admin/User who recorded the entry                                      |
| CreatedAt                  | TIMESTAMP | Record creation timestamp                                              |

---

## 5a. 🔄 Government/External Support Relational Table

> **📋 Description:** A dedicated registry of all external support, grants, loans,
> and assistance received by the cooperative from government agencies, NGOs,
> or other external organizations.
>
> **🎯 Purpose:** To provide a detailed, traceable record of every assistance
> package received — separate from the main financial ledger — enabling
> monitoring of utilization, completion, and impact of external interventions.
> Links to `FinanceID` for financial reconciliation.

| Field         | Type      | Description                                          |
|---------------|-----------|------------------------------------------------------|
| SupportID     | INT (PK)  | Unique Support Record ID                             |
| CoopID        | INT (FK)  | References CoopMasterProfile                         |
| FinanceID     | INT (FK)  | References Financial & Support Data (optional link)  |
| SupportType   | ENUM      | Grant, Loan, Equipment, Training, Technical Assistance, Other |
| ProviderName  | VARCHAR   | Name of providing government agency or organization  |
| Amount        | DECIMAL   | Financial value of assistance (if applicable)        |
| DateGranted   | DATE      | Date assistance was granted                          |
| DateCompleted | DATE      | Date of full release or completion                   |
| Status        | ENUM      | Ongoing, Completed, Pending                          |
| Remarks       | TEXT      | Additional notes                                     |

---

## 6. Training & Capacity Building — Skills Development Monitoring

> **📋 Description:** Records all training programs, seminars, and capacity-building
> activities conducted for cooperative members and officers — including participant
> counts, target groups, facilitators, and follow-up requirements.
>
> **🎯 Purpose:** To monitor the cooperative's investment in human capital development
> — tracking what skills are being built, who the target beneficiaries are,
> participation rates, and whether follow-up sessions or assessments are needed.
> Feeds into provincial skills development reporting.

| Field            | Type      | Description                                                         |
|------------------|-----------|---------------------------------------------------------------------|
| TrainingID       | INT (PK)  | Unique Training Record ID                                           |
| CoopID           | INT (FK)  | References CoopMasterProfile                                        |
| Title            | VARCHAR   | Training/seminar title                                              |
| DateConducted    | DATE      | Date of training                                                    |
| Facilitator      | VARCHAR   | Name of provider/facilitator or organization                        |
| SkillsTargeted   | TEXT      | Specific skills the training aims to develop                        |
| Venue            | VARCHAR   | Location/venue of training                                          |
| TargetGroup      | ENUM      | All Members, Officers Only, Women, Youth, Farmers, Fishfolk, New Members, Other |
| NoOfParticipants | INT       | Actual total number of participants who attended                    |
| FollowUpNeeded   | BOOLEAN   | Indicates whether a follow-up session or assessment is required     |
| FollowUpDate     | DATE      | Scheduled date for follow-up (nullable if FollowUpNeeded = No)      |
| FollowUpRemarks  | TEXT      | Notes on follow-up actions needed                                   |
| Status           | ENUM      | Planned, Completed, Cancelled, Follow-Up Pending                    |
| CreatedAt        | TIMESTAMP | Record creation timestamp                                           |

---

## 6a. 🔄 Training Participants Relational Table *(Junction Table)*

> **📋 Description:** A many-to-many junction table linking individual cooperative
> members or officers to training events they attended.
>
> **🎯 Purpose:** To maintain individual-level training attendance records —
> tracking who attended each training, their outcomes, and certificates issued.
> Enables per-member training history and supports the Skills Inventory table.

| Field           | Type      | Description                                  |
|-----------------|-----------|----------------------------------------------|
| TraineeID       | INT (PK)  | Unique ID                                    |
| TrainingID      | INT (FK)  | References Training & Capacity Building      |
| MemberID        | INT (FK)  | References Members Profile                   |
| OfficerID       | INT (FK)  | References Officers (nullable)               |
| Outcome         | ENUM      | Passed, Failed, Incomplete, No Assessment    |
| CertificateNo   | VARCHAR   | Certificate number (if issued)               |
| CertificateDate | DATE      | Date certificate was issued                  |
| Remarks         | TEXT      | Additional notes                             |

---

## 6b. 🔄 Skills Inventory / Development Tracking Table *(Dynamic)*

> **📋 Description:** A growing inventory of skills acquired by each member or
> officer, tied to the specific training that was the source of the skill.
> Updated each time a new training is completed.
>
> **🎯 Purpose:** To build a cumulative skills profile for every cooperative member
> — tracking proficiency levels and growth over time. Supports targeted training
> planning, succession planning for officers, and provincial human resource
> development reporting.

| Field            | Type      | Description                                     |
|------------------|-----------|-------------------------------------------------|
| SkillID          | INT (PK)  | Unique Skill Record ID                          |
| MemberID         | INT (FK)  | References Members Profile                      |
| CoopID           | INT (FK)  | References CoopMasterProfile                    |
| SkillName        | VARCHAR   | Name of the skill acquired                      |
| ProficiencyLevel | ENUM      | Beginner, Intermediate, Advanced                |
| TrainingID       | INT (FK)  | References Training (source of the skill)       |
| DateAcquired     | DATE      | Date skill was acquired/certified               |
| LastUpdated      | TIMESTAMP | Most recent update to this skill record         |
| Remarks          | TEXT      | Notes on skill application or re-assessment     |

---

## 7. 🔄 Audit Log / Change History Table *(System-wide Dynamic)*

> **📋 Description:** A system-wide log that automatically captures all
> data changes (inserts, updates, deletes) across all major tables, along with
> who made the change and when.
>
> **🎯 Purpose:** To ensure full accountability, traceability, and data integrity
> across the system. Enables administrators to review any modification to any
> cooperative record — who changed what, when, and what the data looked like before
> and after. Essential for audits and data governance compliance.

| Field     | Type      | Description                                        |
|-----------|-----------|----------------------------------------------------|
| LogID     | INT (PK)  | Unique Log ID                                      |
| TableName | VARCHAR   | Name of the table that was changed                 |
| RecordID  | INT       | ID of the record that was changed                  |
| Action    | ENUM      | INSERT, UPDATE, DELETE                             |
| ChangedBy | VARCHAR   | Admin/User responsible for the change              |
| ChangedAt | TIMESTAMP | Timestamp of change                                |
| OldValue  | JSON/TEXT | Previous value snapshot (before change)            |
| NewValue  | JSON/TEXT | New value snapshot (after change)                  |
| Remarks   | TEXT      | Optional notes on the change                       |

---

## Entity Relationship Summary

```
CoopMasterProfile (Table 1)
    ├── Members Profile (1:Many) ─────────────────────────────── Table 2
    │       ├── Coop Services Availed (1:Many) ─────────────── Table 2a
    │       └── Member Sector History (1:Many) ──────────────── Table 2b
    │
    ├── Officers & Committees (1:Many) ───────────────────────── Table 3
    │       ├── Officer / Term History (1:Many) ──────────────── Table 3a
    │       └── Committee Members Junction (Many:Many) ─────── Table 3b
    │
    ├── Cooperative Status History (1:Many) ──────────────────── Table 3c
    │
    ├── Activities & Projects (1:Many) ──────────────────────── Table 4
    │       ├── Activity Participants (Many:Many) ─────────────── Table 4a
    │       └── Funding Source Details (1:Many) ──────────────── Table 4b
    │
    ├── Financial & Support Data (1:Many) ───────────────────── Table 5
    │       └── External Support Table (1:Many) ────────────── Table 5a
    │
    └── Training & Capacity Building (1:Many) ───────────────── Table 6
            ├── Training Participants (Many:Many) ────────────── Table 6a
            └── Skills Inventory / Dev Tracking (1:Many) ─────── Table 6b

[All Tables] ──────────────────────────────────────────────── Audit Log (Table 7)
```

---

## Legend

| Symbol / Term | Meaning                                                     |
|---------------|-------------------------------------------------------------|
| PK            | Primary Key — uniquely identifies each record               |
| FK            | Foreign Key — references a record in another table          |
| 🔄            | Dynamic / History / Junction Table                          |
| ENUM          | Field with a predefined list of accepted values             |
| JSON/TEXT     | Flexible structured or unstructured data field              |
| BOOLEAN       | True/False field (Yes = 1, No = 0)                          |
| DECIMAL       | Numeric field with decimal precision (for financial values) |
| TIMESTAMP     | Auto-recorded date and time                                 |
| nullable      | Field can be left empty/null                                |

---

*Last Updated: 2026-03-09 | Provincial Cooperative Management Information System (PCMIS)*