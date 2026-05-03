## 👤 Donia | Update: The "Super User" & Enhanced RBAC

**Objective:** Create the ultimate oversight role.

- **Task: Super User Implementation**
  - **Description:** Add a `super_admin` value to the `role` ENUM.
  - **Privileges:** This user bypasses all row-level restrictions. They can view the **Identity** of PIs (unlike regular reviewers), access all financial logs, and see the system through the eyes of any other role.
  - **Audit View:** Build a master "System Pulse" page where the Super User can see a live stream of the `logs` table (who is logging in, who uploaded what, who changed a status).

---

## 👤 Azzazy | Update: Research Uniqueness & Keywords

**Objective:** Prevent duplicate research and improve searchability.

- **Task: Metadata Expansion**
  - **Description:** Update the submission form to include a "Keywords" field (enforce a minimum of 5 tags/words). Store these in a new `keywords` column (or a separate related table).
- **Task: The "Similarity Guard" (Non-AI Logic)**
  - **Logic:** When a student submits a title, the system must run a similarity check against existing `approved` or `pending` titles.
  - **Technical Implementation:** Use the **Levenshtein Distance** algorithm or **Jaccard Similarity** index.
  - **Formula:** For a simple PHP/SQL implementation, you can use:
    $$\text{Similarity \%} = \left( 1 - \frac{\text{levenshtein}(s1, s2)}{\max(\text{length}(s1), \text{length}(s2))} \right) \times 100$$
  - **Action:** If the score is $> 80\%$, block the submission and show a "Duplicate Research Detected" warning.

---

## 👤 Amir | Update: Assignment Logic & 48-Hour Deadline

**Objective:** Ensure the workflow doesn't stall at the assignment stage.

- **Task: Reviewer Acknowledgement Workflow**
  - **Description:** When an Admin assigns a reviewer, the status in the `reviews` table becomes `awaiting_acceptance`. The reviewer must "Accept" or "Refuse."
  - **The 48-Hour Kill-Switch:** Implement a background check (Cron Job). If `assigned_at` is $> 48$ hours and status is still `awaiting_acceptance`:
    - Change status to `timed_out`.
    - Move the application back to the Admin's "Needs Assignment" queue.
    - **Priority Flag:** Add a `is_high_priority` boolean to the `applications` table. If an assignment times out, flip this to `TRUE` so it appears at the top of the Admin's list.

---

## 👤 Maula | Update: Reviewer Performance & Response

**Objective:** Track reviewer reliability and handle refusals.

- **Task: Refusal Handling**
  - **Description:** Build the UI for the reviewer to "Refuse" an assignment. This **must** require a text input for the `refusal_reason`.
- **Task: Performance Metrics (The "Report Card")**
  - **Description:** Create a data-aggregator for the Super Admin to see:
    - **Acceptance Rate:** (Accepted Assignments / Total Assignments).
    - **Avg. Decision Time:** Time between "Accepted" and "Final Decision."
    - **Decision Distribution:** How many Approvals vs. Rejections per reviewer.
    - **Late Count:** Number of times a reviewer accepted but exceeded the internal deadline.

---

## 👤 Hager | Update: Digital Verification & Super Dashboards

**Objective:** Finalize the "Finish Line" with security and massive analytics.

- **Task: Manager Digital Signature**
  - **Description:** Create an upload field for the Manager to save their signature (PNG with transparency).
  - **Automation:** Update the PDF Certificate Engine to automatically overlay this signature image onto the generated `certificates`.
- **Task: Public Verification Portal**
  - **Description:** Build a **Public Route** (no login required) where anyone can enter a `certificate_id` or scan a **QR Code**.
  - **Functionality:** This page simply returns: `Valid/Invalid`, `Student Name`, `Research Title`, and `Issue Date`.
- **Task: Super Admin "God View" Dashboard**
  - **Description:** A massive analytics suite. Filter all researches by:
    - **Specialty/Department.**
    - **Status** (Who is late? Who is stuck in payment?).
    - **Reviewer Workload.**
  - **Visuals:** Tables with sorting by "Waiting Time" so the Super Admin can jump in and manually nudge stuck processes.
