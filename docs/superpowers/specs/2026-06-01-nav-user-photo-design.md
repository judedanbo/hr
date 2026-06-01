# Design: User photo in top navigation

**Date:** 2026-06-01
**Branch:** `feat/nav-user-photo`

## Goal

Show the authenticated user's profile photo in the top-right of the top navigation
bar, next to their name. When a user has not set a photo, fall back to the existing
generic placeholder image.

## Background

The app has three authenticated layouts with a top-right user menu:

| Layout | Pages | Current top-right state |
|---|---|---|
| `NewAuthenticated.vue` → `Components/TopMenu.vue` | 71 | Already renders an `<img>` next to the name, hardcoded to `/images/placeholder.webp` |
| `HrAuthenticated.vue` | 12 | Text-only dropdown trigger: `{{ user.name }}` + chevron, no image |
| `Authenticated.vue` | 1 | Text-only dropdown trigger: `{{ user.name }}` + chevron, no image |

The authenticated user's photo lives on the related `Person` model in the `image`
column (relative path such as `avatars/abc123.png`), served from the `public` disk
at `/storage/{image}`. The shared Inertia `auth` data currently exposes a `has_photo`
boolean (at `auth.has_photo`, a sibling of `auth.user`), but not the photo URL.

The existing helper `HandleInertiaRequests::profileFactsForCurrentUser()` already
loads `$person->image` (it computes `has_photo` from it) and memoizes the result for
the life of the request. Deriving a photo URL there adds **no new database work**.

## Scope

All three layouts get the photo. The no-photo fallback is the existing
`/images/placeholder.webp` everywhere — no initials logic.

## Design

### Backend — shared Inertia data

In `app/Http/Middleware/HandleInertiaRequests.php`:

1. Extend `profileFactsForCurrentUser()` to also return a `photo_url` key:
   - `'/storage/' . $person->image` when `$person->image` is set (matching the
     existing convention in `PhotoApprovalController`).
   - `null` when there is no photo, no `person`, or no authenticated user.
   - Update the method's PHPDoc array shape to include `photo_url: ?string`.
2. Add to the `auth` share block, next to `has_photo` (i.e. at `auth.photo_url`,
   a sibling of `auth.user` — matching the existing `has_photo` placement):
   ```php
   'photo_url' => fn () => $this->profileFactsForCurrentUser($request)['photo_url'] ?? null,
   ```

`photo_url` is then globally available on every Inertia page at `auth.photo_url`
with no extra HTTP request.

### Frontend — the three menus

The photo URL is read from `page.props.auth.photo_url` (a sibling of `auth.user`),
and the photo source uses the same fallback expression in all three menus:
`:src="photoUrl || '/images/placeholder.webp'"`, where
`const photoUrl = computed(() => page.props?.auth?.photo_url)`.

1. **`Components/TopMenu.vue`** — add a `photoUrl` computed, then change the
   existing `<img>`'s hardcoded `src="/images/placeholder.webp"` to the bound `:src`
   expression. The `<img>` and its `h-8 w-8 rounded-full bg-gray-50` classes already
   exist; `usePage`/`computed` are already imported.

2. **`Layouts/HrAuthenticated.vue`** — add a `photoUrl` computed and an `<img
   class="h-8 w-8 rounded-full object-cover mr-2">` inside the dropdown trigger
   button, before `{{ user.name }}`, using the same `:src` expression.

3. **`Layouts/Authenticated.vue`** — same addition as `HrAuthenticated.vue`.

### Fallback behaviour

When `photo_url` is `null` (user has no photo set), all three menus render
`/images/placeholder.webp`, identical to today's behaviour in `TopMenu.vue`.

## Testing

Two new test methods added to `tests/Feature/MyProfile/SharedPropsTest.php`, reusing
its existing `createActiveStaff()` helper and assertion style:

- **Has photo:** staff whose `Person` has `image = 'avatars/example.jpg'` → shared
  `auth.photo_url` equals `/storage/avatars/example.jpg`.
- **No photo:** staff whose `Person` has no `image` → `auth.photo_url` is `null`.

Run with `php artisan test --filter=SharedPropsTest`, then `vendor/bin/pint --dirty`.

## Out of scope

- Initials-based avatar fallback (deferred; placeholder image is sufficient for now).
- Photo upload/approval workflow changes (already exists via `PhotoCard.vue` /
  `PhotoApprovalController`).
- Showing photos for other users elsewhere in the UI (this is only the current
  user's nav avatar).
