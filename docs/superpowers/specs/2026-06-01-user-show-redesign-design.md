# User Show Page Redesign — Design

**Date:** 2026-06-01
**Status:** Approved for planning

## Goal

Modernize the user detail page (`User/Show`) and its related components into a cleaner two-column layout, keeping the green brand. Along the way, fix a real correctness bug in how user permissions are displayed and edited (direct vs. role-inherited).

## Visual Direction

Keep the green brand but tidy it. Cards adopt a refined version of the MyProfile design language with green accents:

- Card surface: `bg-white dark:bg-gray-800`
- Border: soft green — `border border-green-200/60 dark:border-gray-700`
- Shape/elevation: `rounded-2xl shadow-sm`
- Accents: green headers, badges, and primary buttons (`bg-green-600 hover:bg-green-500`, dark: gray equivalents already used in the codebase)

The current full-bleed green gradient header (the large clip-path blur block) is removed in favor of a compact green-tinted identity strip.

## Layout

```
Breadcrumb
┌──────────── Identity strip (full width) ────────────┐
│ [avatar] Name · role · Staff#   email  ✓ Verified   │
└─────────────────────────────────────────────────────┘
LEFT RAIL (≈1/3)            RIGHT MAIN (≈2/3)
┌──────────────┐           ┌────────────────────────────┐
│ Account      │           │ Roles            [+ Add]    │
│  email/verif │           │  ● admin ×  ● auditor ×     │
│  user id     │           ├────────────────────────────┤
├──────────────┤           │ Permissions      [+ Add]    │
│ Staff record │           │  Direct:  ● users.create ×  │
│  link/assoc  │           │  Inherited (read-only):     │
│  change/unlink│          │   ● reports.view (via admin)│
└──────────────┘           └────────────────────────────┘
```

- Desktop: two columns — left rail ≈ 1/3, right main ≈ 2/3.
- Mobile: single column, rail stacks first.
- Whole page constrained to `max-w-7xl` with the standard `px-4 sm:px-6 lg:px-8 py-6` padding used by MyProfile.

## Components

### New presentational components — `resources/js/Components/User/`

(Mirrors the `Components/MyProfile/` convention. These are presentational: props in, events out, no router/axios calls.)

- **`UserIdentityCard.vue`** — full-width strip. Avatar (image or initials fallback), name, summary line (primary role · staff number), email, and a verified badge.
- **`UserAccountCard.vue`** — left rail. Email, verified status, user id.
- **`UserStaffRecordCard.vue`** — left rail. Shows staff link state ("Not linked" or `name — staff_number`). Emits `associate`, `change`, and `unlink`; the page owns the modal and the unlink confirm.

### Reworked existing partials — `resources/js/Pages/User/partials/`

These remain the stateful orchestrators (they own the modals and the Inertia router calls), restyled into the new chip-card look.

- **`UserRoles.vue`** — roles rendered as chips with an `×` remove control and an "Add" button that opens the (restyled) role modal. (Role pre-check bug already fixed in `UserRoleForm.vue`.)
- **`UserPermissions.vue`** — two sections:
  - **Direct** — removable chips (`×`).
  - **Inherited from roles** — read-only chips, each labeled with its source role ("via {role}").
- **`RolesList.vue` / `PermissionsList.vue`** — converted from `<table>` layouts to chip renderers, or folded into their parent panels if that reads cleaner during implementation.

### Restyled modals

Green-clean restyle of the modal internals (behavior unchanged except where noted in Backend):

- `AddUserRole.vue` / `UserRoleForm.vue`
- `AddUserPermission.vue`
- `AssociateStaff.vue`
- `partials/Delete.vue` (the revoke confirm dialog)

## Backend Change (correctness)

Today `UserController@show` returns `getAllPermissions()` — direct **plus** role-inherited permissions — as one flat list. This produces two real bugs:

1. **Revoke of an inherited permission silently no-ops.** `revokePermission` calls `revokePermissionTo`, which only removes *direct* grants. A permission inherited from a role stays after "revoke" because the role still grants it.
2. **Opening + saving the Add-Permission modal materializes inherited permissions as direct ones.** The modal pre-checks all permissions (`getAllPermissions`), and `addPermission` calls `syncPermissions($request->permissions)`. Saving therefore converts every role-inherited permission into a direct grant — a destructive side effect from merely opening the modal.

### Fix

- **`UserController@show`** returns two lists instead of one `permissions`:
  - `direct_permissions` — from `$user->getDirectPermissions()`, mapped to `{ id, name }`. These are revokable.
  - `inherited_permissions` — permissions granted via roles but not held directly, each annotated with its originating role name(s): `{ id, name, via }`.
  - `roles`, `email`, `verified` continue to be sent (email/verified are already loaded but currently unused by the page).
- **`AddUserPermission` modal** pre-fills from **direct** permissions only, so saving no longer materializes inherited permissions.

No new routes. Add/remove for roles and permissions keep their existing routes:
`user.add.roles`, `user.revoke.roles`, `user.add.permissions`, `user.revoke.permissions`.

## Data Flow

- No new server round-trips on page load: `email`/`verified` are already loaded; permissions are simply re-split in the controller.
- `UserPermissions.vue` receives `direct_permissions` and `inherited_permissions` as separate props and passes the **direct** list as the modal's pre-fill source.
- Staff associate/change/unlink continue to flow through `AssociateStaff.vue` and `user.dissociate-staff`.

## Testing

- Feature test on `UserController@show` asserting the Inertia payload splits `direct_permissions` and `inherited_permissions` correctly for a user who has both a direct permission and a role that grants other permissions (PHPUnit + `assertInertia`).
- Test that revoking a **direct** permission removes it, and that an inherited-only permission is not presented as revokable.
- Confirm existing add/revoke role & permission feature tests still pass.

## Out of Scope

- No changes to the roles/permissions data model or the Spatie configuration.
- No changes to the user Index/list page.
- No inline (modal-less) editing — add still uses the restyled modal with chip lists.
