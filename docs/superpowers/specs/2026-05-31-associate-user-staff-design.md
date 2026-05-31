# Associate User with Staff Record Before Granting the "staff" Role

**Date:** 2026-05-31
**Status:** Approved design

## Problem

An admin can give an existing user account the `staff` role with no link to a real
staff record. The `users.person_id` column (nullable, `belongsTo Person`) is only
populated when brand-new staff are created or by direct database edits — there is no
UI or controller action to associate an existing user with an existing staff record.

We want to gate "making a user a staff" (assigning the `staff` role) on the admin first
associating that user account with an existing staff record.

## Goals

1. Let an admin associate a `User` with an existing staff `Person` (set `users.person_id`),
   change the link, or clear it — from the user detail page and the user list.
2. Block assigning the `staff` role to a user that is not linked to a staff record,
   enforced server-side.
3. Enforce a one-to-one relationship: a staff record can be linked to at most one user.

## Non-Goals

- Creating new staff/person records from the user screens (that flow already exists at
  `staff.create`).
- Changing how brand-new staff + user records are created together.
- Any change to roles other than `staff`.

## Decisions

- **Meaning of "make a user staff":** assigning the `staff` role. The association is a
  prerequisite, not the same action.
- **Entry points:** both the user detail page (`User/Show.vue`) and the user list
  (`User/Index.vue`).
- **Picker:** searchable select (reuse `resources/js/Components/Forms/SearchSelect.vue`)
  querying staff by name and staff/file number.
- **Uniqueness:** block — a staff record already linked to another user is not selectable
  and is rejected server-side (one user per staff).
- **Unlink:** allowed (change or clear the link later).
- **On unlink:** also remove the `staff` role from the user, since an unlinked user must
  not be staff. Keeps the invariant consistent.
- **Permission:** a new dedicated permission `associate user staff`, seeded to
  `super-administrator` and `admin`.

## Architecture

Two coordinated parts: the **association** capability, and the **role gate** that depends
on it. Both enforce the invariant *"a user with the `staff` role must have a `person_id`."*

### Part 1 — Backend: association

**Routes** (added to the existing `UserController` group in `routes/web.php`):

| Method | URI | Action | Middleware |
|--------|-----|--------|------------|
| GET    | `/users/staff-options` | `UserController::staffOptions` | `can:associate user staff` |
| PATCH  | `/user/{user}/associate-staff` | `UserController::associateStaff` | `can:associate user staff` |
| DELETE | `/user/{user}/associate-staff` | `UserController::dissociateStaff` | `can:associate user staff` |

**`staffOptions(Request $request): JsonResponse`**
- Returns staff `Person` records — those that have at least one `institution_person` row.
- Excludes any `Person` whose `id` is already referenced by another user's `person_id`.
- Filters by `?search=` against person name and `institution_person.staff_number` /
  `file_number`.
- Result shape: `[{ value: <person_id>, label: "<Full Name> — <staff_number>" }, ...]`,
  capped at 20 rows.

**`StoreUserStaffRequest`** (Form Request, `php artisan make:request`)
- `authorize()`: `Gate::allows('associate user staff')`.
- Rules:
  - `person_id` → `required|integer|exists:people,id`
  - the person must be staff: a rule/closure asserting an `institution_person` row exists
    for that `person_id`.
  - one-to-one: `Rule::unique('users', 'person_id')->ignore($user->id)` (resolve `$user`
    from the route) so the person is not already linked to a different user.
- Custom messages for each failure (follow sibling Form Request conventions; check whether
  the app uses array- or string-based rules and match it).

**`associateStaff(StoreUserStaffRequest $request, User $user)`**
- Sets `$user->person_id` and saves.
- Logs via the existing `LogsAuthorization` trait / `activity()` pattern used in the
  controller.
- Redirects back with a success flash message.

**`dissociateStaff(User $user)`**
- Authorize `associate user staff`.
- Clears `person_id`; if the user has the `staff` role, removes it (invariant).
- Logs and redirects back with success.

### Part 2 — Backend: the role gate

**`UpdateUserRolesRequest`** (Form Request) used by `RoleController::addRole`:
- `authorize()`: `Gate::allows('assign roles to user')` (preserve current gate).
- Rule: if the submitted `roles` array includes `staff` and the route `{user}` has a null
  `person_id`, fail with:
  *"Associate this user with a staff record before assigning the staff role."*
- `addRole` continues to `syncRoles($request->roles)` on success; existing activity
  logging preserved.

**`RoleController::addUsers`** (assigning a role to many users from the role side):
- When the target role is `staff`, reject any user in the batch that has a null
  `person_id` (validation error / clear message), so the invariant cannot be bypassed from
  the role page. Rich picker UI is not added here — server-side guard only.

### Part 3 — Frontend

- **`resources/js/Pages/User/partials/AssociateStaff.vue`** (new): modal form with a
  `SearchSelect` backed by `route('users.staff-options')` (debounced search), submitting
  via `router.patch(route('user.associate-staff', { user }))`. Handles validation errors.
  Reused by both the detail and list pages.
- **`User/Show.vue`**: a "Staff record" panel showing the linked staff (name + staff
  number) or "Not linked". Buttons *Associate* / *Change* / *Unlink*, gated on the
  `associate user staff` permission. *Unlink* calls
  `router.delete(route('user.associate-staff', { user }))`.
- **`User/Index.vue`**: a row action "Associate staff" opening the same modal, plus a small
  indicator of link status per row.
- **`User/partials/UserRoleForm.vue`**: disable the `staff` role checkbox when the user has
  no `person_id`, with hint text "Associate a staff record first" and a control to open the
  associate modal. The backend remains the source of truth regardless of the UI state.
- The user payload sent to `User/Show.vue` / `User/Index.vue` must include `person_id` and
  the linked staff's display info (name, staff number) so the UI can render link status.

## Data Flow

1. Admin opens a user (detail or list) → sees link status.
2. Admin clicks Associate → modal → searches staff (`staffOptions` returns only unlinked
   staff) → selects → PATCH `associate-staff` → `StoreUserStaffRequest` validates
   (exists, is staff, not already linked) → `person_id` set.
3. Admin assigns roles → if `staff` is selected and `person_id` is set → allowed; if
   `person_id` is null → `UpdateUserRolesRequest` rejects with the guidance message.
4. Admin unlinks → `person_id` cleared and `staff` role removed.

## Error Handling

- Non-staff person selected → 422 with message.
- Person already linked to another user → 422 with message (one-to-one).
- `staff` role assigned to unlinked user → 422 with the "associate first" message
  (both `addRole` and `addUsers`).
- Missing `associate user staff` permission → 403 (and route middleware blocks access).

## Testing (PHPUnit feature tests)

- Associate links a user to a staff record (happy path).
- Reject associating a non-staff person.
- Reject associating a person already linked to another user (one-to-one).
- Assigning the `staff` role to an unlinked user is rejected via `addRole`.
- Assigning the `staff` role to unlinked users is rejected via `addUsers`.
- Assigning the `staff` role succeeds once the user is linked.
- Unlinking clears `person_id` and removes the `staff` role.
- Authorization: a user without `associate user staff` receives 403 on associate /
  dissociate / staff-options.

## Seeding / Permissions

- Add permission `associate user staff` in the appropriate permission seeder.
- Grant it to `super-administrator` and `admin` in the role-permission assignment seeder.
