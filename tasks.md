## 🏗️ Epic 1: Identity & Access Management (IAM)
This is the foundation. It handles who can enter the system and what they are allowed to do.
* **Feature: Multi-Role Authentication:** Secure login/logout for all 5 roles (Student, Admin, Reviewer, etc.) using `users` table.
* **Feature: Student Onboarding:** Registration specifically for students including profile data and ID photo uploads (`id_front_url`, `id_back_url`).
* **Feature: Admin Verification:** A dedicated dashboard for Admin to review student IDs and "Activate" accounts, which triggers the generation of the `serial_number`.

## 📄 Epic 2: Research Submission Engine
This is where the student spends most of their time. It’s the "data entry" heart of the app.
* **Feature: Application Wizard:** A multi-step form to collect research titles, PIs, and Co-investigators (stored as JSON).
* **Feature: Document Vault:** A robust upload system for the 7+ mandatory PDF files (Protocol, PI Consent, etc.) linked to the `documents` table.
* **Feature: Application Tracking:** A real-time status bar for the student to see exactly which stage their research is in.

## 💳 Epic 3: Financial Transaction System
The system acts as a gatekeeper; it doesn't move forward without a "Completed" status in the `payments` table.
* **Feature: Phase 1 (Initial Payment):** Logic to lock the application until the base submission fee is paid via an electronic gateway.
* **Feature: Phase 2 (Sample Fee):** Dynamic payment calculation based on the result provided by the Sample Size Officer.
* **Feature: Digital Invoicing:** Auto-generation of an electronic receipt containing the `serial_number` and transaction reference.

## 🧪 Epic 4: Technical Evaluation (Sample Size)
A specialized workflow for the `sample_officer` role.
* **Feature: Technical Queue:** A view for the officer to see only researches that have cleared the Initial Payment.
* **Feature: Sample Data Entry:** A simple interface to input the calculated sample size back into the `applications` table.

## 👁️ Epic 5: The "Blind Review" Workflow
This is the most critical academic part of the system.
* **Feature: Reviewer Assignment:** Admin tools to assign one or more `reviewer` roles to a specific application.
* **Feature: Blind Interface:** A restricted view that pulls data from `applications` but **strictly hides** any identifying info (PI name, student name) to ensure 100% anonymity.
* **Feature: Decision Portal:** A form for reviewers to submit their `decision` (Approve/Reject/Modify) and detailed `comments`.

## 🎓 Epic 6: Final Approval & Certification
The "Finish Line" managed by the Committee Manager.
* **Feature: Final Review Queue:** A view for the Manager to see researches that have been "Approved" by the assigned reviewers.
* **Feature: PDF Certificate Engine:** Logic to grab the data and generate a professional, stamped PDF certificate (`certificates` table).
* **Feature: Result Release:** A trigger that changes the status to `approved` and notifies the student they can now download their certificate.

## 📢 Epic 7: System Integrity & Alerts
The background processes that keep everyone informed and the system honest.
* **Feature: Multi-Channel Notifications:** Automated System, Email, or SMS alerts triggered by status changes.
* **Feature: Audit Logging:** A background "black box" that records every single action (who moved a file, who changed a status) into the `logs` table for security.

---

## 🏗️ Epic 1: Identity & Access Management (IAM)

### Feature 1: Student Onboarding & Identity Submission
**User Story 1.1:**
> **As a Student,** I want to create an account by providing my full name, email, national ID, phone, faculty, and department, **so that** I can gain access to the research submission portal.

* **Acceptance Criteria:**
    * The registration form must include all fields defined in the `users` table.
    * The system must enforce unique constraints on `email` and `national_id`.
    * The system must support Arabic text (UTF-8) for names and departments.
    * Passwords must be securely hashed before being stored in `password_hash`.

**User Story 1.2:**
> **As a Student,** I want to upload high-quality photos of my National ID (Front and Back), **so that** the IRB staff can verify my identity.

* **Acceptance Criteria:**
    * The form must have two separate file upload inputs.
    * Files must be saved to a secure directory, and the paths must be recorded in `id_front_url` and `id_back_url`.
    * The system should restrict file types to images (JPG, PNG) or PDF.

---

### Feature 2: Admin Verification & Account Lifecycle
**User Story 1.3:**
> **As an Admin,** I want to view a list of "Pending" students and inspect their uploaded ID documents, **so that** I can ensure only legitimate researchers use the system.

* **Acceptance Criteria:**
    * A dashboard view for users where `role = 'student'` and `is_active = FALSE`.
    * A "Quick View" modal to see the `id_front_url` and `id_back_url` images without leaving the list.

**User Story 1.4:**
> **As an Admin,** I want to activate a student’s account and trigger the generation of a Serial Number, **so that** they can proceed to the payment stage.

* **Acceptance Criteria:**
    * Clicking "Activate" must update `is_active` to `TRUE`.
    * The system must create an initial entry in the `applications` table for that student.
    * The `serial_number` must be generated (e.g., `IRB-YYYY-XXX`) and stored in the `applications` table.

---

### Feature 3: Role-Based Authentication (RBAC)
**User Story 1.5:**
> **As a User (any role),** I want to log in using my email and password, **so that** I am redirected to the dashboard specific to my role.

* **Acceptance Criteria:**
    * The system must check the `role` ENUM upon successful login.
    * **Student** → Redirect to My Researches.
    * **Admin** → Redirect to Pending Activations/Assignments.
    * **Reviewer** → Redirect to Blind Review Queue.
    * **Manager** → Redirect to Final Approval/Analytics.
    * **Sample Officer** → Redirect to Sample Calculation Queue.

---

### Feature 4: Profile & Security Management
**User Story 1.6:**
> **As a User,** I want to manage my personal profile information, **so that** I can keep my phone number and contact details up to date.

* **Acceptance Criteria:**
    * Users can update `phone_number`, `faculty`, and `department`.
    * Users **cannot** change their `role` or `national_id` once verified (this requires Admin intervention).

**User Story 1.7:**
> **As the System,** I want to log every login attempt and profile change, **so that** there is a trail for security auditing.

* **Acceptance Criteria:**
    * Every login/logout must be recorded in the `logs` table.
    * The `logs.action` field should describe the event (e.g., "User [ID] logged in from [IP]").

---

### 📊 Validation Checklist for the Team
| # | Requirement Check | SQL Column |
| :--- | :--- | :--- |
| 1 | Does the signup catch the "National ID"? | `national_id` |
| 2 | Are the front/back ID images stored? | `id_front_url`, `id_back_url` |
| 3 | Is the default state "Inactive"? | `is_active` (DEFAULT FALSE) |
| 4 | Does the Admin have a button to flip `is_active`? | N/A (Logic) |
| 5 | Can the system differentiate between a Reviewer and a Manager? | `role` (ENUM) |

Epic 2 is the core functional engine where the student transitions from a "User" to an "Applicant." It needs to handle complex data like JSON for co-investigators and multiple file uploads with specific ENUM types.

Here are the detailed User Stories for **Epic 2: Research Submission Engine**.

---

## 📄 Epic 2: Research Submission Engine

### Feature 1: Research Metadata Entry (Basic Info)
**User Story 2.1:**
> **As a Student,** I want to enter the research title and the Principal Investigator’s (PI) name **so that** the core identity of the study is recorded in the system.

* **Acceptance Criteria:**
    * The `title` field must support long Arabic text (TEXT type).
    * The `principal_investigator` field must be mandatory.
    * Data must map directly to the `applications` table.

**User Story 2.2:**
> **As a Student,** I want to add multiple co-investigators to my application **so that** all contributors are officially recognized.

* **Acceptance Criteria:**
    * UI should allow "Add More" functionality to list multiple names.
    * The backend must encode these names into a **JSON array** before saving to the `co_investigators` column.
    * The UI must be able to parse and display this JSON during the review stage.

---

### Feature 2: The Mandatory Document Vault
**User Story 2.3:**
> **As a Student,** I want to upload the 7 specific required documents (Protocol, PI Consent, Patient Consent, etc.) **so that** my application meets the IRB's technical requirements.

* **Acceptance Criteria:**
    * The upload interface must have 7 distinct slots corresponding to the `document_type` ENUM:
        1. `protocol` (نموذج البروتوكول)
        2. `conflict_of_interest` (إقرار تضارب المصالح)
        3. `irb_checklist` (قائمة مراجعة IRB)
        4. `pi_consent` (موافقة الباحث الرئيسي)
        5. `patient_consent` (موافقة المريض)
        6. `photos_biopsies_consent` (موافقة الصور والخزعات)
        7. `protocol_review_app` (طلب مراجعة البروتوكول)
    * The system must validate that the files are present before allowing "Final Submission."

**User Story 2.4:**
> **As the System,** I want to organize uploaded files in a structured folder path (`uploads/applications/{id}/`) **so that** files are easy to retrieve and backup.

* **Acceptance Criteria:**
    * The `file_path` in the `documents` table must be updated upon successful upload.
    * Original filenames should be sanitized to avoid Arabic character issues in the file system.

---

### Feature 3: Submission State Management
**User Story 2.5:**
> **As a Student,** I want to see a real-time progress bar of my application status **so that** I know exactly which stage (e.g., Awaiting Initial Payment, Under Review) I am currently in.

* **Acceptance Criteria:**
    * The UI must pull the `current_stage` ENUM from the `applications` table.
    * The status must be displayed in clear Arabic labels (e.g., "بانتظار الدفع المبدئي").

**User Story 2.6:**
> **As a Student,** I want to be blocked from editing my research details once it is "Under Review" **so that** the data remains consistent for the reviewers.

* **Acceptance Criteria:**
    * If `current_stage` is NOT `pending_admin` or `needs_modification`, the "Edit" buttons must be disabled or hidden.

---

### Feature 4: Traceability & Logging
**User Story 2.7:**
> **As an Admin,** I want to see a history of all document uploads and metadata changes for a specific research **so that** I can track the application's timeline.

* **Acceptance Criteria:**
    * Every submission action must trigger an entry in the `logs` table.
    * The `logs.action` should include details like "Document [Type] uploaded by [User]."

---

### 📊 Implementation Checklist (The "Definition of Done")



| # | Technical Requirement | DB Mapping |
| :--- | :--- | :--- |
| 1 | Does the form handle 1 to N co-investigators? | `applications.co_investigators` (JSON) |
| 2 | Are all 7 document types selectable in the upload? | `documents.document_type` (ENUM) |
| 3 | Is the Serial Number visible to the student after activation? | `applications.serial_number` |
| 4 | Does the UI use a "Glassmorphism" card style for the status? | Frontend Task (CSS/Tailwind) |
| 5 | Is the student blocked from proceeding without a Protocol file? | Logic Check (Required Field) |

Epic 3 is the "Gatekeeper" of the system. In your workflow, the research cannot move from one technical stage to the next unless a corresponding record in the `payments` table is marked as `completed`.

Here are the detailed User Stories for **Epic 3: Financial Transaction System**.

---

## 💳 Epic 3: Financial Transaction System

### Feature 1: Initial Submission Payment (Fixed Fee)
**User Story 3.1:**
> **As a Student,** I want to pay the fixed initial submission fee using my **Serial Number** **so that** my application can be sent to the Sample Size Officer for calculation.

* **Acceptance Criteria:**
    * The system must check if the `current_stage` is `awaiting_initial_payment`.
    * The payment interface must require the `serial_number` for validation.
    * Upon successful payment, the `payments` table must insert a record with `phase = 'initial'` and `status = 'completed'`.
    * The `applications.current_stage` must automatically update to `awaiting_sample_calc`.

---

### Feature 2: Sample Size Payment (Variable Fee)
**User Story 3.2:**
> **As a Student,** I want to pay the second fee which is calculated based on my research sample size **so that** my research can finally be assigned to reviewers.

* **Acceptance Criteria:**
    * The system must only enable this payment if the `sample_size` column in the `applications` table is not NULL.
    * The `amount` should be dynamically calculated (e.g., a base rate multiplied by the sample size or a bracket-based fee).
    * Upon successful payment, the `payments` table must insert a record with `phase = 'sample'`.
    * The `applications.current_stage` must automatically update to `under_review`.

---

### Feature 3: Payment Gateway Integration
**User Story 3.3:**
> **As a User,** I want to choose from multiple payment methods (Fawry, Vodafone Cash/Wallets, or Credit Card) **so that** I can complete the transaction conveniently.

* **Acceptance Criteria:**
    * Integration with a provider (e.g., Paymob or Fawry API).
    * The system must handle the asynchronous callback from the provider to update the `transaction_reference` and `paid_at` timestamp.
    * Failed transactions must set `status = 'failed'` and allow the student to try again without creating a duplicate application.

---

### Feature 4: Digital Receipts & Confirmations
**User Story 3.4:**
> **As a Student,** I want to view and download a digital receipt for each payment phase **so that** I have official proof of payment for my university records.

* **Acceptance Criteria:**
    * The receipt must display the `serial_number`, `transaction_reference`, `amount`, and `paid_at`.
    * UI should follow the Glassmorphism style, showing a "Success" state with a "Print/Download" button.

---

### Feature 5: Financial Logging & Alerts
**User Story 3.5:**
> **As the System,** I want to notify the student via Email/SMS once a payment is confirmed **so that** they are reassured the transaction was successful.

* **Acceptance Criteria:**
    * Trigger an entry in the `notifications` table with `channel = 'email'` or `'sms'`.
    * Update the `logs` table with the specific action: "Initial/Sample Payment completed for Serial [X]".

---

### 📊 Implementation Checklist (The "Definition of Done")



| # | Technical Requirement | DB Mapping |
| :--- | :--- | :--- |
| 1 | Does the system verify the `serial_number` before taking money? | `applications.serial_number` |
| 2 | Are there two distinct phases for the same application? | `payments.phase` (ENUM) |
| 3 | Is the `amount` stored as a decimal for accuracy? | `payments.amount` (DECIMAL 10,2) |
| 4 | Does the `current_stage` update *only* after `status = 'completed'`? | `applications.current_stage` |
| 5 | Is the transaction ID from the provider saved? | `payments.transaction_reference` |

**Technical Note for Member 3:**
When implementing the **Phase 2 Payment**, you need to write a simple pricing logic in PHP. For example:
```php
// Example Logic
if ($sample_size <= 100) { $fee = 200; }
else if ($sample_size <= 500) { $fee = 500; }
else { $fee = 1000; }
```
This ensures the `amount` passed to the payment gateway matches the complexity of the research.

Shall we move on to **Epic 4 & 5**, which cover the Sample Size Officer and the crucial Blind Review logic?

Epic 4 is the **Technical Evaluation** phase. It is the bridge between the student's initial payment and the actual review process. This epic is specifically designed for the **Sample Size Officer** role.

Here are the detailed User Stories for **Epic 4: Technical Evaluation (Sample Size)**.

---

## 🧪 Epic 4: Technical Evaluation (Sample Size)

### Feature 1: The Officer's Work Queue
**User Story 4.1:**
> **As a Sample Size Officer,** I want to see a dedicated dashboard of research applications that have successfully completed their initial payment **so that** I know which files are ready for technical processing.

* **Acceptance Criteria:**
    * The dashboard must query the `applications` table where `current_stage = 'awaiting_sample_calc'`.
    * The list must display the `serial_number`, `title`, and `created_at` date.
    * The list must be sorted by "Oldest First" to ensure a fair processing order.

---

### Feature 2: Research Data Access
**User Story 4.2:**
> **As a Sample Size Officer,** I want to view the research protocol and methodology documents **so that** I can extract the necessary data to perform calculations in the college's external system.

* **Acceptance Criteria:**
    * The Officer must have a "View Details" button for each application.
    * This view must pull files from the `documents` table where `document_type = 'protocol'`.
    * The UI must provide a direct download or preview link for the PDF.

---

### Feature 3: Sample Size Recording
**User Story 4.3:**
> **As a Sample Size Officer,** I want to input the final calculated sample size directly into the system **so that** the application can proceed to the final payment phase.

* **Acceptance Criteria:**
    * A simple numeric input field for the `sample_size` column in the `applications` table.
    * The system must validate that the input is a positive integer.
    * Upon saving, the `current_stage` must automatically update to `awaiting_sample_payment`.

---

### Feature 4: External System Integration (Manual)
**User Story 4.4:**
> **As a Sample Size Officer,** I want a clear confirmation that my data entry has successfully triggered the student's payment request **so that** I don't have to follow up manually.

* **Acceptance Criteria:**
    * After submission, a "Success" message should appear confirming that the student has been notified to pay for the sample size phase.
    * The application should immediately move from the Officer's "Active Queue" to the "Completed/History" list.

---

### Feature 5: Technical Audit Trail
**User Story 4.5:**
> **As the System,** I want to log exactly when and by whom the sample size was calculated **so that** there is accountability for the technical parameters of the research.

* **Acceptance Criteria:**
    * A record must be inserted into the `logs` table.
    * `logs.action` should read: "Sample size of [X] calculated and updated by [User Name]".
    * The `user_id` of the Sample Size Officer must be linked to the log entry.

---

### 📊 Implementation Checklist (The "Definition of Done")

| # | Technical Requirement | DB Mapping |
| :--- | :--- | :--- |
| 1 | Is the queue filtered by the correct stage? | `applications.current_stage` = 'awaiting_sample_calc' |
| 2 | Can the officer see the Protocol file path? | `documents.file_path` |
| 3 | Does saving the number update the stage? | `applications.sample_size` & `current_stage` update |
| 4 | Is the user role check enforced? | Middleware: `if(role !== 'sample_officer')` |
| 5 | Does the logic prevent editing after submission? | Logic: Lock `sample_size` input once stage changes |


---

## 👁️ Epic 5: The "Blind Review" Workflow

### Feature 1: Reviewer Assignment (Admin Action)
**User Story 5.1:**
> **As an Admin,** I want to see a list of applications that have completed all payment phases **so that** I can assign them to the appropriate technical reviewers.

* **Acceptance Criteria:**
    * The view filters `applications` where `current_stage = 'under_review'`.
    * The Admin can select one or more users with `role = 'reviewer'`.
    * On assignment, a new row is created in the `reviews` table with `decision = 'pending'`.
    * The `assigned_by` column must record the Admin's user ID.

---

### Feature 2: The Anonymized (Blind) Dashboard
**User Story 5.2:**
> **As a Reviewer,** I want to see my assigned research tasks in a dashboard that hides the identity of the student and the Principal Investigator (PI) **so that** I can provide an unbiased evaluation.

* **Acceptance Criteria:**
    * The SQL query must join `reviews` and `applications` but **exclude** `student_id` and `principal_investigator` from the result set.
    * If `applications.is_blinded = TRUE`, the UI must replace the PI name with "معلومات محجوبة" (Information Redacted).
    * The Reviewer should only see the `serial_number`, `title`, and research `department`.



---

### Feature 3: Technical Document Evaluation
**User Story 5.3:**
> **As a Reviewer,** I want to access and download the research protocol and other supporting documents **so that** I can conduct a thorough technical and ethical review.

* **Acceptance Criteria:**
    * Reviewers must have access to all records in the `documents` table associated with the `application_id`.
    * Files should open in a new tab or download directly.
    * The system must log the first time a reviewer opens a document for audit purposes.

---

### Feature 4: Decision & Feedback Submission
**User Story 5.4:**
> **As a Reviewer,** I want to submit my final decision (Approve, Reject, or Needs Modification) along with technical comments **so that** the student and committee manager can take the next steps.

* **Acceptance Criteria:**
    * The form must map to the `reviews` table.
    * `decision` must be one of the ENUM values: `approved`, `needs_modification`, `rejected`.
    * The `comments` field must be mandatory if the decision is `rejected` or `needs_modification`.
    * Once submitted, `reviewed_at` is updated to the current timestamp.

---

### Feature 5: The Modification Loop
**User Story 5.5:**
> **As a Student,** I want to receive the reviewer’s comments if my research "Needs Modification" **so that** I can update my documents and resubmit without starting a new application.

* **Acceptance Criteria:**
    * If a reviewer selects `needs_modification`, the `applications.current_stage` remains `under_review`, but the Student is granted "Edit" access again.
    * The student receives a notification via the `notifications` table (System/Email).
    * New document uploads must update existing entries or add to the `documents` table with a new timestamp.

---

### 📊 Implementation Checklist (The "Definition of Done")

| # | Technical Requirement | DB Table/Field |
| :--- | :--- | :--- |
| 1 | Does the Admin see a dropdown of Reviewers? | `users` (where role='reviewer') |
| 2 | Is the PI name hidden from the Reviewer UI? | `applications.principal_investigator` |
| 3 | Can a reviewer change their mind after submission? | Logic: Lock `reviews` after `reviewed_at` is NOT NULL |
| 4 | Does the `current_stage` change if all reviewers approve? | `applications.current_stage` |
| 5 | Are reviewer comments stored in UTF-8? | `reviews.comments` (utf8mb4_unicode_ci) |


Epic 7 is the **"Black Box"** and **"Pulse"** of the application. It ensures that every action is accountable and that users stay informed without manually refreshing their browsers. It primarily uses the `logs` and `notifications` tables.

---

## 📢 Epic 7: System Integrity & Alerts

### Feature 1: The Multi-Channel Notification Engine
**User Story 7.1:**
> **As a User (Any Role),** I want to receive real-time notifications within the system **so that** I am immediately aware of updates to my tasks or applications.

* **Acceptance Criteria:**
    * Notifications must be stored in the `notifications` table with `channel = 'system'`.
    * The UI must feature a notification "bell" icon with a red badge for unread counts.
    * Clicking a notification should mark it as `is_read = TRUE` and ideally redirect the user to the relevant page (e.g., the specific application).

**User Story 7.2:**
> **As a User,** I want to receive critical alerts via Email or SMS **so that** I stay informed even when I am not logged into the web application.

* **Acceptance Criteria:**
    * The system must trigger an external API call (e.g., PHPMailer for email or an SMS gateway) for specific "Critical" events:
        * Registration activation.
        * Payment success.
        * Decision (Approval/Rejection).
    * Records must be kept in the `notifications` table with `channel = 'email'` or `'sms'`.

---

### Feature 2: The Audit Trail (Logging)
**User Story 7.3:**
> **As an Admin,** I want a chronological log of all actions taken on a specific research application **so that** I can troubleshoot issues or verify the timeline in case of disputes.

* **Acceptance Criteria:**
    * Any change to the `current_stage` or any file upload MUST trigger a record in the `logs` table.
    * The `logs.action` field must be descriptive (e.g., "Reviewer [ID] changed decision to 'Needs Modification'").
    * Admin dashboard must have a "View History" page for each application that renders these logs in a vertical timeline.

---

### Feature 3: Global Dashboard & Analytics
**User Story 7.4:**
> **As the Committee Manager,** I want to see a statistical overview of the system (Total Researches, Average Approval Time, Department Distribution) **so that** I can monitor the committee's performance.

* **Acceptance Criteria:**
    * The Manager dashboard must display "Key Performance Indicators" (KPIs) using SQL aggregate functions (e.g., `COUNT`, `GROUP BY department`).
    * Data should be filtered by "Year" or "Faculty" as per the requirement file.
    * Include a "Reviewer Workload" chart showing how many researches are assigned to each reviewer.

---

### Feature 4: Search & Advanced Filtering
**User Story 7.5:**
> **As an Admin/Manager,** I want to search and filter researches by specialty, department, or submission year **so that** I can quickly locate specific files among hundreds of applications.

* **Acceptance Criteria:**
    * The search bar should query the `title` and `serial_number`.
    * Filters should dynamically update the view based on the `faculty` and `department` columns from the `users` table (via the `student_id` join).

---

### 📊 Implementation Checklist (The "Definition of Done")

| # | Technical Requirement | DB Table/Field |
| :--- | :--- | :--- |
| 1 | Does every status update trigger a `logAction()`? | `logs` |
| 2 | Is the "Unread" notification badge functional? | `notifications.is_read` |
| 3 | Are logs protected from deletion? | Logic: No "Delete" UI for `logs` |
| 4 | Does the dashboard pull data in real-time? | `applications` (Aggregated) |
| 5 | Are SMS/Email triggers handled asynchronously? | Logic (Queue or Background process) |

---

### 💡 Final Division Strategy for the Team

Now that we have all 7 Epics, here is how you can finalize the work with your team of 5:

1.  **Member 1 (The Core):** **Epic 1 & 7** (Auth, RBAC, Logging, and Dashboards). They build the skeleton.
2.  **Member 2 (The Input):** **Epic 2** (The Submission Form, JSON handling, and File Uploads).
3.  **Member 3 (The FinTech):** **Epic 3** (Payment Gateway integration and Receipts).
4.  **Member 4 (The Reviewer):** **Epic 4 & 5** (Sample Size logic and Blind Review interface).
5.  **Member 5 (The Closer):** **Epic 6** (Manager Approval logic and PDF Certificate generation).

- Donia, Epic 1: Identity & Access Management (IAM)
- Azzazy,Epic 2: Research Submission Engine
- Amir,Epic 3: Financial Transaction System & Epic 4: Technical Evaluation (Sample Size)
- Maula,"Epic 5: The ""Blind Review"" Workflow"
- Hager,Epic 6: Final Approval & Certification & Epic 7: System Integrity & Alerts