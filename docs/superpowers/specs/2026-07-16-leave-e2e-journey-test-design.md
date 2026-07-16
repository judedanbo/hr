# Leave Management End-to-End Journey Test — Design

**Date:** 2026-07-16
**Target:** PR #44 (`claude/leave-management-feature-RHiVm`) — Leave Management, Phases 1–7
**Deliverable:** `tests/Feature/Leave/LeaveManagementJourneyTest.php`

## Context

PR #44 ships the complete leave management module with 141 passing tests, but every
test is per-controller with fresh fixtures per assertion. Nothing walks the *whole*
journey — HR configuration through planning, request, approval, post-approval
lifecycle, and reporting — against one shared database state. Cross-step state drift
(e.g. a decline early in the year corrupting the ledger months later) is invisible to
the existing suite. This test closes that gap as a permanent, CI-runnable HTTP journey
feature test, following the repo's established pattern (`LeaveLifecycleTest`).

Decisions made during brainstorming:

- **Form:** HTTP-level PHPUnit feature test (not Dusk, not a one-off browser walkthrough).
- **Coverage:** golden path plus decline/re-request, cancel/amend, guard rails, and
  balance adjustment + reporting.
- **Permissions:** actors use the **seeded roles** (`staff`, `hr-user`) rather than
  direct `givePermissionTo`, so the test also validates the role→permission maps in
  `database/seeders/RolesAndPermissionsSeeder.php` — the exact wiring CLAUDE.md warns
  fails silently on deploy.
- **Structure:** one class, five chaptered scenario methods (PHPUnit +
  `RefreshDatabase` resets the DB per method, so each chapter is a self-contained
  mini-journey).

## Test class conventions

- `tests/Feature/Leave/LeaveManagementJourneyTest.php`, `use RefreshDatabase`.
- `tests/TestCase.php` sets `$seed = true`, so the full `DatabaseSeeder` (roles +
  all leave permission seeders) runs before each test; roles exist out of the box.
- `Carbon::setTestNow('2030-06-01')` in `setUp`, cleared in `tearDown` (mirrors
  `LeaveLifecycleTest`) for deterministic notice-period and day math.
- MySQL `testing` database per `phpunit.xml`.

## Actors (built in `setUp`)

All users get `password_change_at => now()` to pass the `password_changed` middleware.
Staff identities follow the repo pattern: `Person` → `InstitutionPerson` (+ active
`Status`) → `User` sharing `person_id`.

| Actor | Role (seeded) | Setup |
|---|---|---|
| **HR** | `hr-user` | Plain user; performs config, adjustments, reads reports. |
| **Requester** | `staff` | InstitutionPerson assigned to **Unit A** (open-ended `StaffUnit` pivot). |
| **Unit head** | `staff` only | Own InstitutionPerson set as Unit A's `head_staff_id`. Approves via `LeaveRequestPolicy::decide()`'s resolved-approver path — deliberately **without** the `approve staff leave` pool permission. |
| **Colleague** | `staff` | Second staff in Unit A; relieving officer + coverage-cap subject. |

> **Note (post-implementation):** during Task 2, `hr-user` turned out to lack
> `create leave year`/`create leave type`/`create leave entitlement`/`create holiday`
> in `database/seeders/RolesAndPermissionsSeeder.php` — only `admin-user` holds them.
> This was reported, not fixed (per the plan's report-don't-fix rule for new gaps),
> so the golden path uses a fifth, locally-created `admin-user`-role actor for those
> four config-creation POSTs only; `hrUser` still performs the planning-window,
> calendar, and reports steps it does hold permission for.

## Chapters (test methods)

### 1. `test_golden_path_journey`
The only chapter that does configuration **through HTTP**, using HR where it holds
the permission and a locally-created `admin-user` actor for the four structural
config creates it currently doesn't (see note above):

1. An admin-user config actor creates a leave year (2030, active), a leave type, an
   entitlement (null job category default), a holiday inside the planned leave
   range; HR opens the planning window — each via the real POST routes.
2. Requester submits an annual plan item (assert HR receives the database
   notification; assert planned days in the ledger).
3. Requester creates a leave request linked to the plan item, colleague as relieving
   officer. Assert: `requested_days` excludes the holiday per the type's counting
   rules; `approver_id` resolved to the unit head; head receives the submitted
   notification; plan item gains `converted_request_id`.
4. Unit head loads the approvals index (assert the request appears via
   `assertInertia`) and **approves with reduced days** (`approved_days` <
   `requested_days`). Assert status Approved, `decided_by`, requester notified,
   status history rows (pending → approved/reduced).
5. Requester **resumes early**. Assert Completed, `actual_days` capped correctly,
   unused days freed.
6. Reconcile: `LeaveBalanceService::ledger()` assigned/planned/taken/remaining all
   consistent; requester's balance page shows the same numbers; leave calendar
   (as head, own-unit scope) shows the leave; HR's report figures
   (`LeaveReportService` via the reports page) match taken days.

### 2. `test_decline_and_rerequest_journey`
Factory-config shortcut (year/type/entitlement). Request → head declines with a
reason (assert Declined, `decline_reason`, requester notified, balance fully freed
via `committedRequestDays`) → requester submits a corrected request for the same
window (no overlap error — Declined doesn't block) → head approves it.

### 3. `test_cancel_and_amend_journey`
Two approved requests (via HTTP create + approve helper):
- Cancel the first before its start date. Assert Cancelled + `takenDays` re-credited.
- Amend the second. Assert original Cancelled, new Pending linked via
  `amended_from_id` with recomputed `requested_days`, and the freed days made the
  amendment pass the balance guard.

### 4. `test_guard_rails_hold`
Each guard asserted as a validation error on the real endpoint, with no
`leave_requests` row created (or no approval applied):
- **Overdraw:** request exceeding `remainingForRequest` rejected.
- **Overlap:** request overlapping an existing Pending/Approved one rejected.
- **Missing evidence:** type with `requires_evidence` rejects a request without a
  document upload.
- **Min notice:** request starting sooner than `min_notice_days` rejected.
- **Coverage cap:** leave type with `max_concurrent_per_unit = 1` (the cap lives on
  `LeaveType`, enforced per unit by `LeaveCoverageService`); colleague's overlapping
  request reaches the head but **approval** is blocked while the requester's
  approved leave overlaps.

### 5. `test_adjustment_reconciles_in_reports`
HR posts a signed `LeaveBalanceAdjustment` via HTTP (positive, then negative).
Assert `assignedDays` shifts both ways (floored at 0 per `LeaveBalanceService`),
the requester's balance page reflects it, and the HR report/ledger figures
reconcile after a subsequent approved request consumes part of the adjusted balance.

## Assertion style

- HTTP: `assertRedirect` / `assertSessionHasErrors` on writes; `assertInertia`
  (component + props) where a page must *display* the right state (approvals inbox,
  balance page, reports).
- Persistence: `assertDatabaseHas` for statuses, links, day counts.
- Ledger math: direct `LeaveBalanceService` calls (`assignedDays`, `takenDays`,
  `committedRequestDays`, `ledger`).
- Notifications: database channel — assert rows in `notifications` for the right
  notifiable and type (no `Notification::fake()` in the golden path, so the real
  pipeline is exercised end-to-end).

## Out of scope

- Browser/Dusk coverage of the Vue pages (may be a follow-up).
- Excel/PDF export binary content (the export routes' 200 status only, if at all).
- Delegation and reassign flows (covered by existing controller tests).
- Multi-institution scoping.

## Verification

1. `php artisan test tests/Feature/Leave/LeaveManagementJourneyTest.php` — all
   chapters green (requires the MySQL `testing` DB from `phpunit.xml`).
2. `./vendor/bin/pint --dirty` — clean.
3. `php artisan test tests/Feature/Leave/ tests/Unit/` — no regressions in the
   existing 141 leave tests.
