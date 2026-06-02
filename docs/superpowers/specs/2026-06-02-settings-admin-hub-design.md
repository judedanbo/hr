# Settings Admin Hub — Design

**Date:** 2026-06-02
**Status:** Approved for planning
**Cycle:** 1 of 2 (this spec). Cycle 2 — persisted app-settings store — is a separate spec/plan (see "Follow-up").

## Goal

Turn the bare `/settings` page into a green-clean **admin dashboard**: a central, permission-aware landing page with stat cards, quick-links into the existing management areas, and a small recent-activity list. Also fixes the current page's prop mismatch (the Vue expects `admins`/`hrUser` props the controller never sends correctly).

## Visual Direction

Match the recently-merged user-show redesign:
- Card surface: `bg-white dark:bg-gray-800`
- Border: soft green — `border border-green-200/60 dark:border-gray-700`
- Shape/elevation: `rounded-2xl shadow-sm`
- Accents: green icon chips, green "Manage →" links
- Page constrained to `max-w-7xl` with `px-4 sm:px-6 lg:px-8 py-6`

## Layout

```
Breadcrumb: Home / Settings
┌──────────────────────────────────────────────┐
│  Settings                                      │  ← heading + subtitle
├──────────────────────────────────────────────┤
│ ┌──────────┐ ┌──────────┐ ┌──────────┐         │
│ │ Users 42 │ │ Roles  7 │ │ Perms 88 │         │  ← management cards
│ │30 staff· │ │          │ │          │         │     (count + optional sub)
│ │ Manage → │ │ Manage → │ │ Manage → │         │
│ └──────────┘ └──────────┘ └──────────┘         │
│ ┌──────────┐ ┌──────────┐                       │
│ │Audit 1.2k│ │Institut.3│                       │
│ │ View   → │ │ Manage → │                       │
│ └──────────┘ └──────────┘                       │
├──────────────────────────────────────────────┤
│  Recent admin activity                         │  ← last 5 activity entries
│   • Jude added a role · 2 Jun 2026 10:14        │
│   • Ama revoked a permission · 2 Jun 2026 09:50 │
│   …                                             │
└──────────────────────────────────────────────┘
```

Cards grid: `grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5`. The recent-activity panel is full-width below the grid.

## Cards (each conditionally rendered by permission)

| Card | Count | Secondary | Link (route) | Gate (auth.permissions) |
|---|---|---|---|---|
| Users | total users | "{staff} staff · {hrUser} HR" | `user.index` (`/user`) | `view all users` |
| Roles | role count | — | `role.index` (`/role`) | `view roles` |
| Permissions | permission count | — | `permission.index` (`/permission`) | `view permissions` |
| Audit Log | activity count | — | `audit-log.index` (`/audit-log`) | `view user activity` |
| Institutions | institution count | — | `institution.index` (`/institution`) | `view admin settings` |

A card the admin lacks permission for simply does not render. Gating is client-side via `page.props.auth.permissions` (matches existing app convention); the page itself remains server-gated on `view admin settings`.

## Recent Admin Activity

A full-width panel below the cards listing the **5 most recent** `Spatie\Activitylog\Models\Activity` records. Each row shows: `description`, `causer_name` (causer relation `->name`, fallback "System"), and formatted `created_at`. The controller eager-loads `causer` to avoid N+1. The panel renders only when the admin has `view user activity`; otherwise it is omitted. An empty list shows a friendly "No recent activity." state.

## Components

- **New `resources/js/Components/Settings/SettingCard.vue`** — presentational card. Props: `title` (String), `count` (Number), `secondary` (String, optional), `href` (String), `linkLabel` (String, default "Manage"), `icon` (Object, optional). Renders as an Inertia `<Link>` to `href`. Single responsibility, single root.
- **New `resources/js/Components/Settings/RecentActivityCard.vue`** — presentational panel. Prop: `activities` (Array of `{ id, description, causer_name, created_at }`). Renders the list or the empty state. Single root.
- **Rewrite `resources/js/Pages/Settings/Index.vue`** — builds the card list locally from the `stats` prop + permission gates (each card entry pairs a count from `stats` with its route, gate, icon, and label), renders a `SettingCard` per visible entry, and renders `RecentActivityCard` from `recentActivity`. Drops `lang="ts"` to match the plain `<script setup>` convention used across the app.

## Backend

Rewrite `SettingsController@__invoke`:
- Keep the `view admin settings` gate and the existing success/failure activity logging.
- Compute counts: `users` (`User::count()`), `staff` (`User::role('staff')->count()`), `hrUser` (`User::role('hr-user')->count()`), `roles` (`Role::count()`), `permissions` (`Permission::count()`), `auditLogs` (`Activity::count()`), `institutions` (`Institution::count()`).
- Recent activity: `Activity::with('causer')->latest()->limit(5)->get()` mapped to `{ id, description, causer_name, created_at }`.
- Return a clean payload:
  ```php
  Inertia::render('Settings/Index', [
      'stats' => [
          'users' => $users,
          'staff' => $staff,
          'hrUser' => $hrUser,
          'roles' => $roles,
          'permissions' => $permissions,
          'auditLogs' => $auditLogs,
          'institutions' => $institutions,
      ],
      'recentActivity' => $recentActivity,
  ]);
  ```
- This replaces the current mismatched `users`/`hr-user`/`staff`/`roles`/`permissions` keys.

## Data Flow

- Single server round-trip on page load (all counts + 5 activity rows).
- No new routes; the hub links to existing named routes.
- No writes from this page; it is read-only navigation + stats.

## Testing

- Feature test `tests/Feature/SettingsControllerTest.php`:
  - An admin with `view admin settings` gets `Settings/Index` with a `stats` payload whose counts match seeded/created data, and a `recentActivity` array.
  - A user **without** `view admin settings` is redirected back with an error (matches current behavior).
- Frontend: no JS test runner in this repo — verified via `npm run build`.

## Out of Scope / Follow-up (Cycle 2)

A new persisted **application-settings store** — settings table + model, edit forms, validation, seeded defaults, and tests — is a separate spec/plan to be done after this hub ships. The hub is built so a future "App settings" card can be added with one entry in the `sections` array once that store exists.
