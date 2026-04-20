# Help File Update Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Update `docs/HELP.md` with documentation for all features added since January 2026 (notifications, My Profile, photo approvals, qualifications reports, data integrity additions) plus FAQ and glossary updates.

**Architecture:** Single-file markdown update. All changes go to `docs/HELP.md`. No code changes — the existing HelpController reads the markdown and renders it. Work is organized as sequential edit tasks, each targeting a specific section of the document.

**Tech Stack:** Markdown only. No PHP/Vue/JS changes.

**Spec:** `docs/superpowers/specs/2026-04-20-help-file-update-design.md`

---

### Task 1: Update Table of Contents

**Files:**
- Modify: `docs/HELP.md:6-23` (Table of Contents block)

- [ ] **Step 1: Replace the Table of Contents**

Replace lines 6-23 of `docs/HELP.md` (the `## Table of Contents` block through the last entry) with:

```markdown
## Table of Contents

1. [Getting Started](#getting-started)
2. [User Roles Overview](#user-roles-overview)
3. [Dashboard & Navigation](#dashboard--navigation)
4. [Notifications](#notifications)
5. [Staff Directory](#staff-directory)
6. [My Profile](#my-profile)
7. [Staff Management](#staff-management)
8. [Photo Approvals](#photo-approvals)
9. [Units & Departments](#units--departments)
10. [Ranks & Job Categories](#ranks--job-categories)
11. [Staff Transitions](#staff-transitions)
12. [Qualifications Reports](#qualifications-reports)
13. [Reports & Exports](#reports--exports)
14. [User Management](#user-management)
15. [Data Integrity](#data-integrity)
16. [Common Tasks](#common-tasks)
17. [Frequently Asked Questions](#frequently-asked-questions)
18. [Getting Help](#getting-help)
19. [Keyboard Shortcuts](#keyboard-shortcuts)
20. [Glossary](#glossary)
```

- [ ] **Step 2: Commit**

```bash
git add docs/HELP.md
git commit -m "docs(help): update table of contents with new sections"
```

---

### Task 2: Update User Roles Overview

**Files:**
- Modify: `docs/HELP.md:62-97` (User Roles section role lists)

- [ ] **Step 1: Update Staff User role**

Find the Staff User subsection (line 62-67). Replace the bullet list with:

```markdown
### Staff User
Regular employees with basic access to:
- View their own profile
- Update personal information, contacts, address, and qualifications via My Profile
- Upload a profile photo (subject to HR approval)
- View and manage their notifications
- View organizational structure
- Change their password
```

- [ ] **Step 2: Update HR User role**

Find the HR User subsection (line 75-80). Replace the bullet list with:

```markdown
### HR User
Human Resources specialists with access to:
- View all staff records
- Create and manage staff qualifications
- Add staff notes (commendations, warnings, etc.)
- Process staff transfers
- Review and approve or reject staff photo submissions
```

- [ ] **Step 3: Update Admin User role**

Find the Admin User subsection (line 82-88). Replace the bullet list with:

```markdown
### Admin User
Administrative users who can:
- All Personnel and HR capabilities
- Create new staff records
- Download staff data exports
- Process promotions and transfers
- Manage staff notes
- Access qualifications reports and exports
```

- [ ] **Step 4: Update Super Administrator role**

Find the Super Administrator subsection (line 90-97). Replace the bullet list with:

```markdown
### Super Administrator
Full system access including:
- All administrative capabilities
- Manage user accounts and roles
- Configure permissions
- Access data integrity tools
- View audit logs
- Manage system settings
- All notification, profile, photo approval, and reporting capabilities
```

- [ ] **Step 5: Commit**

```bash
git add docs/HELP.md
git commit -m "docs(help): update user roles with new feature capabilities"
```

---

### Task 3: Add Notifications Section

**Files:**
- Modify: `docs/HELP.md` — insert new section after the "Dashboard & Navigation" section (after line 138, before "Staff Directory")

- [ ] **Step 1: Insert Notifications section**

Insert the following block between the `---` separator at line 139 and `## Staff Directory` at line 141:

```markdown

## Notifications

The system keeps you informed about approvals, status changes, and important updates through in-app notifications.

### Notification Bell

A bell icon is always visible in the top-right area of the page header.

- A **red badge** appears on the bell showing your unread notification count (displays "9+" when there are more than 9)
- Click the bell to open a **dropdown** showing your 10 most recent notifications
- Each notification displays an icon, title, description, and relative time (e.g., "2 hours ago")
- Click the **X** button on any notification to dismiss it
- Click **Mark all as read** to clear all unread notifications at once
- Click on a notification to navigate directly to the related item (e.g., a staff profile or approval)

### Notifications Page

For a full view of all your notifications:

1. Click **View all** at the bottom of the notification bell dropdown, or navigate to the Notifications page from the menu
2. You'll see a paginated list of all your notifications (20 per page)
3. **Filter by status** using the tab buttons at the top:
   - **All** — every notification
   - **Unread** — only unread notifications
   - **Read** — only read notifications
4. **Filter by type** using the dropdown to narrow by notification category (e.g., photo approvals, qualifications)
5. For each notification you can:
   - Click the **check mark** to mark it as read
   - Click the **delete** button to remove it
   - Click on the notification title or body to mark it as read and navigate to the related content

> **Note:** You can only see your own notifications. Each user's notification list is private.

---
```

- [ ] **Step 2: Commit**

```bash
git add docs/HELP.md
git commit -m "docs(help): add Notifications section"
```

---

### Task 4: Replace "Your Profile" with "My Profile"

**Files:**
- Modify: `docs/HELP.md:193-219` — replace the entire "Your Profile" section

- [ ] **Step 1: Replace the Your Profile section**

Replace everything from `## Your Profile` (line 193) through the `---` at line 219 with the expanded My Profile section:

```markdown
## My Profile

My Profile is your personal dashboard for viewing and managing your staff information. It uses a card-based layout with sections you can edit and sections managed by HR.

### Accessing My Profile

1. Click on your **Profile Menu** in the top-right corner
2. Select **My Profile**
3. You'll see your profile organized into cards

### Profile Photo

You can upload or change your profile photo from the photo card:

1. Click on the photo card or the **Upload** button
2. Drag and drop an image or click to browse your files
3. Requirements: **JPG or PNG** format, maximum **2 MB**
4. After uploading, your photo enters a **"Pending review"** state
5. An HR administrator will review and approve or reject your submission
6. You'll receive a **notification** when your photo is approved or rejected
7. To remove your current photo, click the **Remove** button

> **Note:** Your photo won't change immediately after upload. It must be approved by HR first. You can see the pending status and timestamp on your photo card.

### Contact Information

You can manage your phone numbers and email addresses:

1. Scroll to the **Contact** card on your profile
2. To **add** a contact:
   - Click **Add Contact**
   - Select the type (Phone or Email)
   - Enter the details
   - Click **Save**
3. To **edit** a contact, click the edit icon next to it
4. To **delete** a contact, click the delete icon

> **Restrictions:** You cannot delete your last phone number or your organizational email address (e.g., @audit.gov.gh).

### Address

You can add or update your address:

1. Scroll to the **Address** card on your profile
2. If no address exists, click **Add Address**
3. To update an existing address, click **Edit** or **Change**
4. Fill in the fields:
   - **Address Line 1** (required)
   - Address Line 2
   - **City** (required)
   - Region
   - Country
   - Post Code
5. Click **Save**

### Qualifications

You can manage your own qualifications, which may require HR approval:

1. Scroll to the **Qualifications** card on your profile
2. To **add** a qualification:
   - Click **Add Qualification**
   - Enter: qualification name, institution, year obtained, level, and course
   - Click **Save**
3. Each qualification shows a status badge:
   - **Approved** (green) — verified by HR
   - **Pending** (amber) — awaiting HR review
4. To **attach documents** (certificates, transcripts), click the attach icon on a qualification
5. To **view details**, click on the qualification name
6. To **delete** a qualification (if permitted), use the delete button

### HR-Managed Information (Read-Only)

The following sections are visible on your profile but can only be updated by HR:

- **Personal Details** — date of birth, gender, nationality, religion, marital status, identity documents
- **Employment Information** — hire date, current rank, current unit/department
- **Dependents** — registered family members (spouse, children, parents)

To update any of these fields, contact your HR department.

### Changing Your Password

1. Click on your **Profile Menu** in the top-right corner
2. Select **Change Password**
3. Enter your **Current Password**
4. Enter your **New Password**
5. **Confirm** your new password
6. Click **Update Password**

---
```

- [ ] **Step 2: Commit**

```bash
git add docs/HELP.md
git commit -m "docs(help): replace Your Profile with expanded My Profile section"
```

---

### Task 5: Add Photo Approvals Section

**Files:**
- Modify: `docs/HELP.md` — insert new section after "Staff Management" (after the `---` that ends Staff Management, before "Units & Departments")

- [ ] **Step 1: Insert Photo Approvals section**

Insert the following block between the end of the Staff Management section and the start of "Units & Departments":

```markdown

## Photo Approvals

*This section is for users with the "approve staff photo" permission*

When staff members upload new profile photos, they must be reviewed and approved before becoming visible. The Photo Approvals page lets authorized users manage this queue.

### Accessing Photo Approvals

1. Navigate to **Photo Approvals** in the main menu
2. You'll see a table of all pending photo submissions
3. If there are no pending photos, the page shows "No pending photo submissions"

### Reviewing and Acting on Submissions

The table shows one row per pending submission with:
- **Staff Member** — name of the person who submitted the photo
- **Current Photo** — their existing approved photo (or "None" if they don't have one)
- **Pending Photo** — the new photo they uploaded (highlighted with an amber border)
- **Submitted** — when the photo was uploaded (e.g., "2 hours ago")

For each submission you have two options:

**To Approve:**
1. Click the **Approve** button (green)
2. The pending photo becomes the staff member's official profile photo
3. The staff member receives a notification that their photo was approved

**To Reject:**
1. Click the **Reject** button (red)
2. The pending photo is removed
3. The staff member receives a notification that their photo was rejected and may upload a new one

---
```

- [ ] **Step 2: Commit**

```bash
git add docs/HELP.md
git commit -m "docs(help): add Photo Approvals section"
```

---

### Task 6: Add Qualifications Reports Section

**Files:**
- Modify: `docs/HELP.md` — insert new section after "Staff Transitions" and before "Reports & Exports"

- [ ] **Step 1: Insert Qualifications Reports section**

Insert the following block between the end of the Staff Transitions section and the start of "Reports & Exports":

```markdown

## Qualifications Reports

*This section is for users with the "qualifications.reports.view" permission*

The Qualifications Reports module provides a comprehensive dashboard for analysing staff qualifications across the organization.

### Accessing Qualifications Reports

1. Navigate to **Qualifications** > **Reports** in the main menu
2. You must have the **qualifications.reports.view** permission
3. Exporting reports additionally requires the **qualifications.reports.export** permission

### KPI Dashboard

At the top of the page, four summary cards give you an at-a-glance overview:

1. **Total Qualifications** — count of qualifications matching your current filters, with trend over time
2. **Staff Covered** — number and percentage of active staff who have at least one qualification
3. **Pending** — qualifications awaiting approval, with the age of the oldest pending item
4. **Staff Without Qualifications** — count and percentage of active staff with no qualifications (useful for identifying training needs)

### Filtering Reports

Use any combination of filters to narrow your results (all are optional):

- **Department** — select an organizational department
- **Unit** — units update automatically based on selected department
- **Qualification Level** — filter by credential type (Degree, Diploma, Certificate, etc.)
- **Status** — filter by approval status (Approved, Pending, etc.)
- **Gender** — filter by Male or Female
- **Year Range** — set a start and/or end year
- **Institution** — search by school or university name
- **Course** — search by course or programme name

Filters apply automatically as you select them. Active filters appear as **badges** that you can click to remove individually.

### Report Types

Select a report type from the dropdown:

1. **Staff List** — detailed individual qualification records
2. **By Unit** — qualifications aggregated by organizational unit
3. **By Level** — qualifications aggregated by credential level
4. **Gaps** — staff who have no qualifications, useful for training needs analysis

### Charts & Visualizations

Six interactive charts are available below the KPI cards:

1. **Qualification Level Distribution** — breakdown by level
2. **Highest Level by Gender** — comparison of male vs. female staff
3. **Qualifications by Unit** — breakdown by organizational unit
4. **Acquired Over Time** — trend of qualifications gained year-over-year
5. **Top Institutions** — most common schools and universities
6. **Top Qualifications** — most common courses and degrees

Each chart can be **expanded to full screen** and supports toggling between count and percentage views.

### Exporting Reports

To export data as PDF or Excel:

1. Click the **PDF** or **Excel** export button
2. Select the report type from the dropdown (Staff List, By Unit, By Level, or Gaps)
3. The export will include only the data matching your current filters
4. The file downloads automatically

> **Note:** Exporting requires the **qualifications.reports.export** permission in addition to the view permission.

---
```

- [ ] **Step 2: Commit**

```bash
git add docs/HELP.md
git commit -m "docs(help): add Qualifications Reports section"
```

---

### Task 7: Update Data Integrity Section

**Files:**
- Modify: `docs/HELP.md` — the "Available Checks" list inside the Data Integrity section (currently lines 590-597)

- [ ] **Step 1: Add new checks to the list**

Find the Available Checks list. After the last existing entry (`- **Staff Without Gender** - Staff records with missing gender data`), add:

```markdown
- **Expired Active Status** - Staff with expired active status dates that need review
- **Pending Qualifications** - Qualifications submitted by staff that are awaiting HR review and approval
```

- [ ] **Step 2: Commit**

```bash
git add docs/HELP.md
git commit -m "docs(help): add new data integrity checks"
```

---

### Task 8: Update FAQ Section

**Files:**
- Modify: `docs/HELP.md` — the "Frequently Asked Questions" section

- [ ] **Step 1: Update the profile photo FAQ entry**

Find the existing FAQ entry (line 679):
```
**Q: How do I update my profile photo?**
A: Navigate to your profile, click on your current photo or the "Upload Photo" option, select a new image, and save.
```

Replace it with:
```markdown
**Q: How do I update my profile photo?**
A: Go to **My Profile** and use the photo card to upload a new image (JPG/PNG, max 2 MB). Your photo will enter a "Pending review" state and must be approved by an HR administrator before it becomes visible. You'll receive a notification when it's approved or rejected.
```

- [ ] **Step 2: Add new General Questions**

After the updated profile photo FAQ entry, add:

```markdown

**Q: How do I get notified about approvals and updates?**
A: The system sends you in-app notifications. Look for the **bell icon** in the top-right corner — a red badge shows your unread count. Click it to see recent notifications, or visit the **Notifications** page to view and filter all your notifications.

**Q: I uploaded a new profile photo but it's not showing yet. Why?**
A: Profile photos require HR approval before they appear. After you upload, your photo enters a "Pending review" state. You'll see a pending badge on your profile photo card. Once an HR administrator approves or rejects your photo, you'll receive a notification.
```

- [ ] **Step 3: Add new Staff Management FAQ entry**

After the existing "How do I record a staff death?" FAQ entry, add:

```markdown

**Q: How do I add qualifications to my profile?**
A: Go to **My Profile** and scroll to the Qualifications card. Click **Add Qualification**, fill in the details (name, institution, year, level, course), and save. You can also attach supporting documents. New qualifications may require HR approval before they are marked as verified.
```

- [ ] **Step 4: Add new Reports FAQ entry**

After the existing "Can I schedule automatic reports?" FAQ entry, add:

```markdown

**Q: Where can I see qualification statistics for my department?**
A: Navigate to **Qualifications** > **Reports** (requires reporting permission). Use the Department and Unit filters to narrow results. The KPI dashboard shows summary metrics, and you can export detailed reports as PDF or Excel.
```

- [ ] **Step 5: Commit**

```bash
git add docs/HELP.md
git commit -m "docs(help): update FAQ with new feature entries"
```

---

### Task 9: Update Glossary and Footer

**Files:**
- Modify: `docs/HELP.md` — Glossary table and footer lines

- [ ] **Step 1: Add new glossary terms**

Find the Glossary table. After the last existing row (`| **Qualification** | Educational or professional credentials |`), add:

```markdown
| **Notification** | An in-app alert informing you of approvals, status changes, or system events |
| **Photo Approval** | The HR review process for staff profile photo submissions |
| **Data Integrity Check** | An automated validation that identifies data quality issues for administrator review |
| **KPI (Key Performance Indicator)** | A summary metric displayed on report dashboards |
| **Qualification Level** | The classification of a credential (e.g., Degree, Diploma, Certificate, Training) |
```

- [ ] **Step 2: Update footer**

Find and replace the footer lines:

```markdown
*Last Updated: December 2024*

*HR Management System - Version 2024.12*
```

With:

```markdown
*Last Updated: April 2026*

*HR Management System - Version 2026.04*
```

- [ ] **Step 3: Commit**

```bash
git add docs/HELP.md
git commit -m "docs(help): update glossary and version footer"
```

---

### Task 10: Final Review

**Files:**
- Read: `docs/HELP.md` (full file)

- [ ] **Step 1: Read the full updated file**

Read the entire `docs/HELP.md` to verify:
- Table of Contents links match section headings
- No orphaned or broken anchor links
- Consistent formatting (heading levels, bullet styles, blockquote usage)
- No duplicate sections or missing separators
- All new sections are in the correct order per the TOC

- [ ] **Step 2: Verify markdown renders correctly**

```bash
php artisan tinker --execute="echo strlen(\Illuminate\Support\Str::markdown(file_get_contents(base_path('docs/HELP.md'))));"
```

Expected: A number (the HTML string length), confirming the markdown parses without errors.

- [ ] **Step 3: Fix any issues found and commit**

If any issues are found in steps 1-2, fix them and commit:

```bash
git add docs/HELP.md
git commit -m "docs(help): fix review issues"
```

- [ ] **Step 4: Final commit if no issues**

If everything looks good and no fixes were needed, no additional commit is necessary. The work is complete.
