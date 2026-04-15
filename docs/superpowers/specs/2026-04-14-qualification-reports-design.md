# Qualification Reports — Design Spec

**Date:** 2026-04-14
**Branch:** `feature/qualifications-reports`
**Status:** Approved

## 1. Goal

Deliver a comprehensive qualifications reporting suite covering:

- Dashboard charts (role-scoped) summarising the workforce academic profile.
- A dedicated `/qualifications/reports` single-page analytics view with filters, charts, and a paginated staff qualifications table.
- Downloadable reports (PDF + Excel) for five report variants.
- Integration with `/data-integrity/pending-qualifications` via a shared pending-approvals widget.
- Per-staff qualification profile PDF downloadable from the staff summary page.

Audience: HR/Admin oversight, institutional planning, and individual staff-centric reporting (comprehensive suite).

## 2. Data Source

Existing `qualifications` table (migrations already in place):

- `person_id`, `course`, `institution`, `qualification`, `qualification_number`, `level`, `pk`, `year`, `status`, `approved_by`, `approved_at`, soft deletes, activity log.
- `level` is `QualificationLevelEnum` (10 tiers: SSSCE/WASSCE, Certificate, Diploma, HND, Degree, PG Certificate, PG Diploma, Masters, Doctorate, Professional).
- `status` is `QualificationStatusEnum` (Pending, Approved, Rejected).
- Documents attached via `MorphMany` to `Document`.

New migration `2026_04_14_000000_add_report_indexes_to_qualifications.php` adds composite indexes `(level, status)`, `(status, approved_at)`, `(year)`, `(institution)` guarded with `Schema::hasIndex` checks.

## 3. Architecture

```
Filter DTO (QualificationReportFilter)
          │
          ▼
Service (QualificationReportService)
   ├─ levelDistribution(filter)        → highest qual per person
   ├─ byUnit(filter)                   → [unit => level => count]
   ├─ topInstitutions(filter, n)       → normalized by LOWER(TRIM())
   ├─ trendByYear(filter)              → [year => count]
   ├─ pendingApprovalsStats()          → count + 30-day sparkline
   ├─ staffWithoutQualifications()     → active staff with no approved quals
   └─ staffList(filter)                → paginated, eager-loaded
          │
  ┌───────┼─────────────┐
  ▼       ▼             ▼
Dashboard  /qualifications  Exports (PDF+Excel)
```

### File layout

- `app/Services/QualificationReportService.php`
- `app/DataTransferObjects/QualificationReportFilter.php` — readonly class; factories: `fromRequest(Request)`, `fromArray(array)`; nullable fields mean "unfiltered"; `toQueryArray()` for URL persistence.
- `app/Http/Controllers/QualificationReportController.php` — `index`, `exportPdf`, `exportExcel`, `staffProfilePdf`.
- `app/Http/Controllers/DashboardController.php` — add `qualificationWidgets()` method.
- `app/Exports/Qualifications/{QualificationListExport,QualificationByUnitExport,QualificationByLevelExport,StaffWithoutQualificationsExport,StaffQualificationProfileExport}.php`
- `resources/views/pdf/qualifications/{layout,list,by_unit,by_level,gaps,staff_profile}.blade.php`
- `resources/js/Pages/Qualification/Reports/Index.vue`
- `resources/js/Components/Charts/Qualifications/{LevelDistributionChart,ByUnitChart,TopInstitutionsChart,AcquiredOverTimeChart,PendingApprovalsWidget}.vue`
- `resources/js/Components/Qualifications/QualificationsDashboardSection.vue` — rendered inside `Dashboard.vue`.
- `database/seeders/QualificationReportPermissionSeeder.php` — three new permissions.
- `database/migrations/2026_04_14_000000_add_report_indexes_to_qualifications.php`

## 4. Data Layer

### Filter DTO fields

`unit_id`, `department_id`, `level`, `status`, `year_from`, `year_to`, `gender`, `job_category_id`, `institution` (LIKE), `course` (LIKE).

### Query rules

- **Level distribution (highest-per-person):** for each person, include only their highest `QualificationLevelEnum` (by tier ordinality). Subquery: `SELECT person_id, MAX(level_rank) FROM qualifications WHERE status = 'approved' GROUP BY person_id`. Prevents Masters-holder being counted under both Degree and Masters.
- **By unit:** join `qualifications → people → institution_person → staff_units (active assignment) → units`. Output shape `[unit_name => [level => count]]`.
- **Top institutions:** group by `LOWER(TRIM(institution))`, take display label as most common casing for that group; order desc, limit `n` (default 10).
- **Trend by year:** group by `year` (string column, cast to int at display). Range bounded by filter.
- **Pending approvals stats:** count + daily counts for last 30 days (for sparkline).
- **Staff without qualifications:** `Person::whereDoesntHave('qualifications', fn ($q) => $q->approved())` scoped to active `InstitutionPerson`.
- **Staff list:** paginated (25/page), eager load `person.currentUnit`, `person.currentRank`, `approver`, `documents`.

### Caching

`Cache::remember("qual-report:{$filterHash}", 600, ...)` for aggregate queries (10 min TTL). Invalidate via `QualificationObserver` on `saved`/`deleted` — flushes cache tag `qualification-reports`. Dashboard endpoint cached at 10 min.

## 5. Routes & Permissions

```php
Route::middleware(['auth'])->prefix('qualifications/reports')->name('qualifications.reports.')->group(function () {
    Route::get('/',                           [QualificationReportController::class, 'index'])->name('index')->middleware('can:qualifications.reports.view');
    Route::get('/export/pdf',                 [QualificationReportController::class, 'exportPdf'])->name('export.pdf')->middleware('can:qualifications.reports.export');
    Route::get('/export/excel',               [QualificationReportController::class, 'exportExcel'])->name('export.excel')->middleware('can:qualifications.reports.export');
    // Authorization handled in controller via QualificationPolicy (self OR qualifications.reports.export holders)
    Route::get('/staff/{person}/profile.pdf', [QualificationReportController::class, 'staffProfilePdf'])->name('staff.profile.pdf');
});

Route::get('/dashboard/qualifications-widgets', [DashboardController::class, 'qualificationWidgets'])
    ->middleware(['auth', 'can:qualifications.reports.view'])->name('dashboard.qualifications');
```

Export endpoints accept `?type=list|by_unit|by_level|gaps|staff_profile` plus filter params.

### New permissions (seeded via `QualificationReportPermissionSeeder`)

- `qualifications.reports.view`
- `qualifications.reports.export`
- `qualifications.reports.view.all` vs `qualifications.reports.view.own_unit` — scoping flag; service auto-injects unit filter for own_unit users.

Role assignments (`AssignRolePermissionSeeder`):

- `super-administrator`, `admin` → all three.
- Unit heads / HR → `view` + `export` + `view.own_unit`.
- Regular staff → none (they have their own staff summary).

## 6. Dashboard Widgets

New `QualificationsDashboardSection.vue` rendered in `Dashboard.vue` behind `can.qualifications.reports.view`. Data loaded async via `/dashboard/qualifications-widgets`; section shows skeletons until JSON lands.

Widgets:

1. **Level Distribution** — donut, highest-per-person, legend with count + %.
2. **By Unit** — horizontal stacked bar, top 8 units by headcount.
3. **Pending Approvals** — KPI card: current count + 30-day sparkline, deep-links to `/data-integrity/pending-qualifications`. Same `PendingApprovalsWidget.vue` used on the data-integrity page.
4. **Top Institutions** — horizontal bar, top 10.
5. **Acquired Over Time** — line, last 10 years.
6. **Staff Without Qualifications** — KPI card + "View list" link to reports page in gaps mode.

Unit-scoping: if user has `view.own_unit` but not `view.all`, service auto-injects their active unit into the filter for all dashboard widget queries.

## 7. `/qualifications/reports` Analytics Page

### Layout (top → bottom)

1. Header: title + `Export PDF ▾` + `Export Excel ▾` dropdowns (4 report types each).
2. Sticky filter bar (collapsible on mobile): Unit, Department, Level, Status, Year range, Gender, Rank/Job category, Institution (autocomplete), Course (text), Clear all.
3. `ActiveFilters` badges (reuse existing component).
4. KPI row: Total qualifications, Staff covered, Pending count, Staff with no quals.
5. 2-column chart grid: Level Distribution (donut), By Unit (stacked bar), Top Institutions (horizontal bar), Acquired Over Time (line).
6. Staff qualifications table: paginated 25/page. Columns: Staff, Rank, Unit, Qualification, Level, Institution, Year, Status. Row click → staff summary page.

### Filter behaviour

Debounced 300ms; updates URL via `router.get(route('qualifications.reports.index'), query, { preserveState: true, preserveScroll: true, replace: true })`. All charts + table refresh together. Reuse `AdvancedSearchPanel`, `ActiveFilters`, `SearchSelect` patterns from staff-search.

### Export triggers

Export dropdown items fire `window.location.href = route('...export.pdf', { type, ...currentFilters })` so browser handles the download. Each report type has a matching Excel and PDF counterpart.

## 8. Exports

### Excel (`app/Exports/Qualifications/`)

All implement `FromQuery`, `WithHeadings`, `WithMapping`, `ShouldAutoSize`, `WithStyles`. Constructor accepts `QualificationReportFilter`.

| Class | Contents |
|-------|----------|
| `QualificationListExport` | Flat list: Staff No, Name, Rank, Unit, Qualification, Level, Institution, Year, Status, Approved At |
| `QualificationByUnitExport` | Pivot: rows = unit; columns = each level; totals row + column |
| `QualificationByLevelExport` | Rows = level; columns = count, % of workforce, # pending |
| `StaffWithoutQualificationsExport` | Staff No, Name, Rank, Unit, Hire Date, Years of Service |
| `StaffQualificationProfileExport` | Single staff: header block + one row per qualification |

### PDF (`resources/views/pdf/qualifications/`)

DomPDF, A4 portrait. Shared `layout.blade.php` with logo, report title, filter summary, generation timestamp, page numbering.

Templates: `list.blade.php`, `by_unit.blade.php`, `by_level.blade.php`, `gaps.blade.php`, `staff_profile.blade.php`.

PDFs render as pure tabular HTML. Bar-like visuals use sized `<div>` elements where helpful — no Chart.js in PDFs (DomPDF doesn't run JS). Keeps generation fast and reliable.

### Staff summary integration

`resources/js/Pages/Person/Summary.vue` gains "Download Qualification Profile (PDF)" button in the qualifications section, visible when `can.qualifications.reports.export`.

## 9. Testing

Feature tests in `tests/Feature/QualificationReports/` (PHPUnit):

- `IndexPageTest.php` — renders with props; filters honored; 403 without permission; unit-scoping for `own_unit` users.
- `ServiceAggregationsTest.php` — highest-per-person rule; by-unit correctness; top-institutions casing normalization; filter combinations.
- `ExcelExportTest.php` — each export returns expected rows/headings (`Excel::fake()`).
- `PdfExportTest.php` — each PDF route returns 200 + `application/pdf`; filter params honored.
- `DashboardWidgetTest.php` — endpoint JSON shape; unit-scoping.
- `PermissionsTest.php` — three new permissions enforced.

Factory additions on `QualificationFactory`: `->approved()`, `->pending()`, `->atLevel(QualificationLevelEnum)`.

## 10. Out of Scope (explicit YAGNI)

- Scheduled/emailed reports.
- Custom report builder UI.
- Qualification expiry tracking (no expiry field in schema).
- Cross-institution benchmarking.
- Pending Approvals Report download (deliberately omitted per product decision).

## 11. Open Questions

None at spec time; implementation plan will surface any detail-level ambiguities.
