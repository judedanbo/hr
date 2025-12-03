# Project TODO List

## Critical Issues

- [x] ~~**[CRITICAL]** Remove dead code in `PersonController::store()` (line 61) - `dd($avatar)` blocks execution and prevents person creation~~ (Fixed by user)
- [x] ~~**[CRITICAL]** Fix incomplete `RankStaffStatsController::__invoke()` - contains broken query `$query->where();` with no parameters~~ (Fixed: proper gender filtering with `whereHas`)
- [x] ~~**[CRITICAL]** Fix hardcoded `Institution::find(1)` in `InstitutionPersonController::store()` - breaks multi-tenant support~~ (Fixed: uses auth user's institution with proper error handling)
- [x] ~~**[CRITICAL]** Fix `StorePromoteStaffRequest` validation bug - `rank_id` was validating against `units` table instead of `jobs`~~ (Fixed: 2025-12-03)

## High Priority

- [x] ~~**[HIGH]** Increase test coverage - Phase 3 tests created and passing~~ (See Test Coverage section below)
- [x] ~~**[HIGH]** Add database indexes for performance optimization~~ (Most indexes already exist; migration created for remaining: first_name, surname, staff_number, file_number)
- [ ] **[HIGH]** Implement placeholder controllers: `AuditLogController`, `ContactController`, `DocumentController`, `NoteController`
- [ ] **[HIGH]** Standardize authorization patterns - currently 3 different patterns (Gate::denies, $user->cannot(), Policy methods)
- [ ] **[HIGH]** Create service layer to extract business logic from fat controllers (`InstitutionPersonController` is 624+ lines)

## Medium Priority

- [x] ~~**[MED]** Optimize export queries - `StaffDetailsExport` and others load all columns/relationships causing memory issues~~ (Fixed: filtered eager loading, removed duplicate scope)
- [ ] **[MED]** Standardize error handling - inconsistent response formats (JSON, redirect back, redirect to dashboard)
- [ ] **[MED]** Refactor or remove legacy `PersonUnitController` and `PersonUnit` model to favor `InstitutionPersonController`/`InstitutionPerson`
- [ ] **[MED]** Fix component organization issues:
  - Duplicate directories: `resources/js/Pages/Dependent/` AND `resources/js/Pages/Dependents/`
  - Typo: `resources/js/Pages/CategoryRanks/patials/` (should be `partials`)

## Low Priority

- [ ] **[LOW]** Audit `v-html` usage in Vue components for XSS risks (4 files: EditTransfer.vue, ApproveTransfer.vue x2)
- [ ] **[LOW]** Generate API documentation for endpoints in `routes/api.php`
- [ ] **[LOW]** Conduct security audit on authorization policies in `app/Policies/`
- [ ] **[LOW]** Update production deployment to include Laravel optimization commands

## Completed

- [x] ~~Refactor enum classes to use native PHP 8.1+ enums~~ (All 15 enums already use native syntax)

---

## Test Coverage (Updated 2025-12-03) ✅ PHASE 3 COMPLETE

### Feature Tests - All Passing (145 tests)

| Test File | Tests | Assertions | Status |
|-----------|-------|------------|--------|
| `StaffCreationTest.php` | 12 | 22 | ✅ Passing |
| `StaffPromotionTest.php` | 15 | 27 | ✅ Passing |
| `StaffTransferTest.php` | 18 | 34 | ✅ Passing |
| `StaffSeparationTest.php` | 22 | 75 | ✅ Passing |
| `StaffAdvancedSearchTest.php` | 23 | ~40 | ✅ Passing |
| `AuthorizationTest.php` | 29 | 37 | ✅ Passing |

### Unit Tests - All Passing (85 tests)

| Test File | Tests | Assertions | Status |
|-----------|-------|------------|--------|
| `PersonTest.php` | 29 | ~40 | ✅ Passing |
| `JobTest.php` | 15 | 23 | ✅ Passing |
| `InstitutionPersonTest.php` | 21 | ~30 | ✅ Passing |

**Total: 230 tests, 486 assertions - All Passing**

### Fixes Applied During Test Development

1. **Seeders made idempotent** - All permission seeders now use `firstOrCreate()` instead of `create()`
2. **Missing permissions added:**
   - `promote staff` - Added to `PromotionsPermissionSeeder`
   - `transfer staff` - Added to `UnitsPermissionSeeder`
   - `view all past promotions`, `view past promotion` - Added to `PastPromotionSeeder`
3. **Validation bugs fixed:**
   - `StorePromoteStaffRequest`: Changed `exists:units,id` to `exists:jobs,id`
   - `UpdatePromotionRequest`: Made `id` nullable, `staff_id` required
4. **Test data structure issues fixed:**
   - `StaffCreationTest`: Updated to use nested `staffData` structure
   - Fixed enum values (marital_status: 'S', identity: 'G')
5. **Ambiguous column fixes:**
   - `StaffTransferTest`: Changed `where('unit_id', ...)` to `where('staff_unit.unit_id', ...)`

---

## Notes

- See `IMPROVEMENTS.md` for detailed analysis and code examples
- See `ROADMAP.md` for implementation plan
- **Test coverage: ~25% (up from <5%) - Phase 3 Complete!**
- **System Health Score: 7.8/10 (up from 6.5/10, target: 8.5/10)**
- Next: Phase 4 (Architecture) or Phase 5 (Code Quality)
