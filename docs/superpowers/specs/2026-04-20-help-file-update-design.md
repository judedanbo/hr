# Help File Update Design Spec

**Date:** 2026-04-20
**Approach:** In-place update of `docs/HELP.md`
**Scope:** Add documentation for all features added since January 6, 2026

---

## Context

The help system consists of:
- `docs/HELP.md` — single markdown file (~780 lines), the source of truth
- `app/Http/Controllers/HelpController.php` — reads HELP.md, converts to HTML via `Str::markdown()`
- `resources/js/Pages/Help/Index.vue` — renders the HTML in a styled article
- Route: `GET /help` (auth + password_changed middleware)

Last updated: January 6, 2026 (commit `4292eee`)

## Features Requiring Documentation

1. **In-app Notifications** — bell icon, notifications index page, filtering, mark as read, delete
2. **My Profile page** — card-based self-service profile with photo upload, contacts, address, qualifications
3. **Photo Approvals** — HR workflow for approving/rejecting staff photo submissions
4. **Qualifications Reports** — KPI dashboard, 4 report types, 6 chart types, filtering, PDF/Excel export
5. **Data Integrity additions** — 2 new check types (Expired Active Status, Pending Qualifications)

## Audience

All user roles — staff, personnel, HR, admin, super-admin. Content is organized with role annotations (e.g., "*This section is for users with X permission*") matching the existing document style.

## Design Decisions

- **Approach A: In-place update** — update the existing HELP.md rather than splitting or rebuilding
- **Detail level:** Match existing depth per section — step-by-step for complex workflows, lighter for simple features
- **Update FAQ and Glossary** with new feature entries
- **No code changes required** — HelpController and Help/Index.vue remain unchanged

## Changes to HELP.md

### 1. Table of Contents (Reordered & Expanded)

```
1.  Getting Started
2.  User Roles Overview
3.  Dashboard & Navigation
4.  Notifications                    ← NEW
5.  Staff Directory
6.  My Profile                       ← REPLACES "Your Profile"
7.  Staff Management
8.  Photo Approvals                  ← NEW
9.  Units & Departments
10. Ranks & Job Categories
11. Staff Transitions
12. Qualifications Reports           ← NEW
13. Reports & Exports
14. User Management
15. Data Integrity                   ← UPDATED
16. Common Tasks
17. Frequently Asked Questions       ← UPDATED
18. Getting Help
19. Keyboard Shortcuts
20. Glossary                         ← UPDATED
```

**Rationale:**
- Notifications after Dashboard — it's a global UI element (bell in header)
- My Profile replaces Your Profile — same concept, expanded
- Photo Approvals near Staff Management — it's an HR admin workflow
- Qualifications Reports before general Reports — specialized reporting module

### 2. New Section: Notifications

**Placement:** After "Dashboard & Navigation" (new section 4)

**Subsections:**

#### Notification Bell
- Location: always visible in top-right header area
- Red badge showing unread count (shows "9+" when exceeding 9)
- Click to open dropdown with 10 most recent notifications
- Each notification shows: icon, title, body text, relative time
- Actions in dropdown: dismiss individual notification (X button), "Mark all as read" button
- Click notification to navigate to related content

#### Notifications Page
- Access: click "View all" in dropdown, or navigate to /notifications
- Paginated list (20 per page)
- Filter by status: All, Unread, Read (tab buttons)
- Filter by type: dropdown with notification types
- Per-notification actions: mark as read (check icon), delete (trash icon)
- Click any notification to mark as read and navigate to its URL

**Access:** All authenticated users. Each user sees only their own notifications.

### 3. Expanded Section: My Profile (Replaces "Your Profile")

**Placement:** Section 6 (was section 5 "Your Profile")

**Subsections:**

#### Viewing Your Profile
- Access: profile menu in top-right → "Profile", or /my-profile
- Card-based layout overview

#### Profile Photo
- Upload via drag-drop or file browser
- Requirements: JPG/PNG, max 2MB
- After upload: photo enters "pending" state awaiting HR approval
- Pending status shown with timestamp
- Can remove current photo
- Approved/rejected via notification

#### Contact Information (User Editable)
- View all phone numbers and email addresses
- Add new contact (phone or email) via modal form
- Edit existing contacts (except organizational email)
- Delete optional contacts (cannot delete last phone number or org email)

#### Address (User Editable)
- View current address
- Add address if none exists
- Edit existing address
- Required fields: address line 1, city
- Optional: address line 2, region, country, post code

#### Qualifications (User Managed, HR Approved)
- View all qualifications with status badges (Approved, Pending)
- Add new qualification: name, institution, year, level, course
- Attach supporting documents to qualifications
- Delete qualifications (if permitted)
- View qualification details in modal

#### HR-Managed Information (Read-Only)
- Personal details: DOB, gender, nationality, religion, marital status, identity documents
- Employment info: hire date, current rank, current unit
- Dependents: table of registered dependents
- Note: "Contact HR to update these fields"

#### Changing Your Password
- Kept from original section (no changes needed)

### 4. New Section: Photo Approvals

**Placement:** After "Staff Management" (new section 8)
**Access note:** *"This section is for users with the 'approve staff photo' permission"*

**Subsections:**

#### Accessing Photo Approvals
- Navigate to Photo Approvals in the main menu
- Requires "approve staff photo" permission

#### Reviewing Pending Submissions
- Table view with columns: Staff Member, Current Photo, Pending Photo, Submitted time, Actions
- Current vs pending photo shown side-by-side
- Empty state: "No pending photo submissions"

#### Approving or Rejecting
- **Approve** (green button): pending photo becomes the staff member's official photo; staff receives approval notification
- **Reject** (red button): pending photo is removed; staff receives rejection notification with option to re-upload
- Page refreshes after each action

### 5. New Section: Qualifications Reports

**Placement:** After "Staff Transitions" (new section 12)
**Access note:** *"This section is for users with the 'qualifications.reports.view' permission"*

**Subsections:**

#### Accessing Qualifications Reports
- Navigate to Qualifications → Reports in the main menu
- Requires `qualifications.reports.view` permission
- Export requires additional `qualifications.reports.export` permission

#### KPI Dashboard
Four summary cards at the top of the page:
1. **Total Qualifications** — count matching current filters, trend over time
2. **Staff Covered** — number and percentage of active staff with qualifications
3. **Pending** — qualifications awaiting approval, with oldest pending age
4. **Staff Without Qualifications** — count and percentage of active staff without any qualifications

#### Filtering Reports
Available filters (all optional, combinable):
- Department, Unit (unit list updates based on department)
- Qualification Level, Status
- Gender
- Year range (from/to)
- Institution, Course (free text)
- Filters apply automatically (300ms debounce)
- Active filters shown as removable pills

#### Report Types
Four types available via dropdown:
1. **Staff List** — individual qualification records
2. **By Unit** — aggregated by organizational unit
3. **By Level** — aggregated by qualification level
4. **Gaps** — staff without qualifications (training needs analysis)

#### Charts & Visualizations
Six expandable charts:
1. Qualification Level Distribution
2. Highest Level by Gender
3. Qualifications by Unit
4. Acquired Over Time (trend)
5. Top Institutions
6. Top Qualifications (courses)

Each chart is expandable to full screen and supports count/percentage toggle.

#### Exporting Reports
- PDF export: styled document with filters summary, charts, and data tables
- Excel export: .xlsx with formatted headers and full detail rows
- Select report type before exporting
- Both available via dropdown menu buttons

### 6. Data Integrity Updates

**Action:** Add two new checks to the existing "Available Checks" list:

- **Expired Active Status** — Staff with expired active status dates that need review
- **Pending Qualifications** — Qualifications submitted by staff that are awaiting HR review and approval

### 7. FAQ Additions

Four new entries under appropriate subsections:

**General Questions:**
- **Q: How do I get notified about approvals and updates?**
  A: The system sends you in-app notifications. Look for the bell icon in the top-right corner — a red badge shows your unread count. Click it to see recent notifications, or visit the Notifications page to view and filter all your notifications.

- **Q: I uploaded a new profile photo but it's not showing yet. Why?**
  A: Profile photos require HR approval before they appear. After you upload a photo, it enters a "pending" state. You'll see a "Pending review" badge on your profile. Once an HR administrator approves or rejects your photo, you'll receive a notification.

**Staff Management Questions:**
- **Q: How do I add qualifications to my profile?**
  A: Go to My Profile and scroll to the Qualifications card. Click "Add Qualification", fill in the details (name, institution, year, level), and save. You can also attach supporting documents. New qualifications may require HR approval.

**Reports Questions:**
- **Q: Where can I see qualification statistics for my department?**
  A: Navigate to Qualifications Reports (requires reporting permission). Use the Department and Unit filters to narrow results. The KPI dashboard shows summary metrics, and you can export detailed reports as PDF or Excel.

### 8. Glossary Additions

Five new terms added to the glossary table:

| Term | Definition |
|------|------------|
| **Notification** | An in-app alert informing you of approvals, status changes, or system events |
| **Photo Approval** | The HR review process for staff profile photo submissions |
| **Data Integrity Check** | An automated validation that identifies data quality issues for administrator review |
| **KPI (Key Performance Indicator)** | A summary metric displayed on report dashboards |
| **Qualification Level** | The classification of a credential (e.g., Degree, Diploma, Certificate, Training) |

### 9. Footer Update

- Change "Last Updated: December 2024" → "Last Updated: April 2026"
- Change "Version 2024.12" → "Version 2026.04"

### 10. User Roles Overview Update

Add new capabilities to relevant roles:

**Staff User** — add:
- View and manage their notifications
- Access My Profile to update photo, contacts, address, qualifications

**HR User** — add:
- Review and approve/reject staff photo submissions

**Admin User** — add:
- Access qualifications reports and exports

**Super Administrator** — add:
- All new capabilities above

## Out of Scope

- No changes to HelpController.php
- No changes to Help/Index.vue
- No new routes or Vue components
- No screenshot additions (references kept as placeholders matching existing pattern)

## Estimated Size

Current HELP.md: ~780 lines
Estimated after update: ~1050-1100 lines (~270-320 lines added)
