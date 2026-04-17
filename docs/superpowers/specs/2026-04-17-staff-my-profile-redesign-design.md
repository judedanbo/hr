# Staff "My Profile" — Redesign for Self-Service Photo & Qualifications

**Status:** Draft — pending approval
**Branch:** `feature/staff-my-profile-redesign`
**Date:** 2026-04-17

## Problem

The HR system is about to be opened to all staff. The first two actions a new staff user is expected to take — upload a photo and add their qualifications — are awkward on the existing profile page (`resources/js/Pages/Staff/NewShow.vue`):

- The photo is a small corner avatar; upload is a pencil icon on hover, not an obvious action.
- Qualifications sit below many HR-admin cards (Dates, Status, Type, Position, Promotions, Transfers), so a first-time user scrolls past a wall of mostly-empty data to reach the one section they can act on.
- There is no guidance or empty-state coaching for either action, and no signal of completion progress.
- The same page serves two audiences — staff viewing themselves and HR/admin managing the record — which makes both experiences mediocre.

## Goals

- A dedicated staff-facing `GET /my-profile` page, distinct from the admin `Staff/NewShow.vue`.
- Photo upload and qualification management are the two most visible actions on first visit, with equal weight.
- Clear, labelled empty states and a lightweight completion signal guide a new user to act.
- Staff can also edit their own personal contact info (email, phone, primary address) from this page.
- Admin `Staff/NewShow.vue` is unchanged.
- Page works cleanly on desktop, tablet, and mobile, and supports dark mode to the same standard as the existing app.

## Non-Goals (Deferred to Spec 2)

The following are explicitly out of scope for this spec and will be picked up in a follow-up:

- Admin approval workflow for staff-uploaded photos (the photo goes live immediately here).
- Email-change verification via tokenised link (new email is saved immediately here).
- Phone-number-change verification via SMS OTP (new phone is saved immediately here; no SMS vendor is installed in the project today).
- Any dependent self-service (the dependents workflow is incomplete; dependents appear read-only on My Profile for now).
- Any change to admin staff management screens.

## Design

### Architecture & routing

A single new controller and page, backed by a shared service so we don't duplicate the eager-load logic already in `InstitutionPersonController@show`.

```
GET /my-profile ─► MyProfileController@show
                    │
                    ├─► StaffProfileProvider::forPerson($authUser->person_id)
                    │        (shared with InstitutionPersonController@show)
                    │
                    └─► Inertia::render('MyProfile/Index', [...])
```

- **Route:** `Route::middleware(['auth','password_changed'])->get('/my-profile', [MyProfileController::class, 'show'])->name('my-profile.show');` — authenticated users only; a `403` is returned for users whose account has no linked `person_id`.
- **No new resource routes for photo, contacts, qualifications.** Existing endpoints are reused:
  - `POST /person/{person}/avatar` (`PersonAvatarController@update`)
  - `DELETE /person/{person}/avatar/delete` (`PersonAvatarController@delete`)
  - Qualification CRUD under `qualification.*`
  - Contact & address edit endpoints already wired into `EditContactForm.vue` and the address modal.
- **Admin view:** `resources/js/Pages/Staff/NewShow.vue` is untouched. Staff with admin rights can still reach it via `/staff/{id}`. A later follow-up may consolidate.

### Backend

#### `app/Services/StaffProfileProvider.php` (new)

Extracts the eager-load-and-map logic that currently lives inline in `InstitutionPersonController@show` (lines ~174–290) so it can be reused by `MyProfileController`. Returns the payload shape already produced by that controller: `['person' => ..., 'staff' => ..., 'contacts' => ..., 'address' => ..., 'qualifications' => ..., ]`.

```php
final class StaffProfileProvider
{
    public function forPerson(int $personId): ?array
    {
        // Mirrors InstitutionPersonController@show's eager load + map.
        // Returns null when no active InstitutionPerson is found for the person.
    }
}
```

`InstitutionPersonController@show` is refactored to call this service, so there is a single source of truth. The public JSON shape stays identical — an important constraint, because `NewShow.vue` already consumes it.

#### `app/Http/Controllers/MyProfileController.php` (new)

```php
public function show(Request $request, StaffProfileProvider $provider): Response
{
    $personId = $request->user()->person_id;
    abort_unless($personId, 403, 'Your account is not linked to a staff record.');

    $profile = $provider->forPerson($personId);
    abort_if($profile === null, 404, 'Staff record not found.');

    return Inertia::render('MyProfile/Index', $profile);
}
```

No `authorize()` gate is needed beyond the `person_id` ownership check — the route itself scopes to the authenticated user. All per-field edit endpoints already enforce their own authorization.

### Vue components

```
resources/js/Pages/MyProfile/Index.vue         (page — composes the strip, priority grid, secondary grid)

resources/js/Components/MyProfile/
  IdentityStrip.vue        (avatar, name, meta, progress chip)
  PhotoCard.vue            (empty + filled + uploading + error states; drag-and-drop)
  QualificationsCard.vue   (empty + filled states; wraps existing AddQualification/EditQualification/AttachDocument modals and QualificationList)
  ContactCard.vue          (email, phone, primary address; opens existing EditContactForm + address modals)
  ReadOnlyKvCard.vue       (generic key-value card — used for Employment and Dependents)
  ProfileProgress.vue      (computes & renders the completion chip)
```

Everything uses `<script setup>`. Existing `Avatar.vue`, `ImageUpload.vue`, `NewModal.vue`, `EditContactForm.vue`, `AddQualification`, `EditQualification`, `AttachDocument`, and `QualificationList` are reused — no forks.

### Photo upload UX

Single `PhotoCard.vue` with four visual states driven by local component state plus props.

| State | Trigger | UI |
|---|---|---|
| Empty | `person.image === null` | 2 px dashed drop zone filling the card body; "Drop your photo here" + file-picker button. Amber "Not set" chip in the card header. |
| Uploading | submit in flight | Drop zone greys out; progress bar + spinner over it. |
| Filled | `person.image !== null` and no in-flight submit | 120 × 120 preview + "Change photo" (ghost, green) + "Remove" (ghost, red) buttons. Green "✓ Set" chip in the card header. |
| Error | validation or server failure | Inline message under the drop zone (no modal). Drop zone remains interactive so the user can retry without a page reload. |

- **Client-side validation:** JPG or PNG, ≤ 2 MB. Anything else shows the error state before any HTTP call.
- **Submit:** `router.post(route('person.avatar.update', { person: props.person.id }), formData, { forceFormData: true, preserveScroll: true, onSuccess: () => router.reload({ only: ['person'] }) })`.
- **Remove:** `router.delete(route('person.avatar.delete', { person: props.person.id }), { preserveScroll: true, onSuccess: () => router.reload({ only: ['person'] }) })`. A small confirm dialog (reuse existing `DeleteAvatar.vue`) is shown first.
- **No full-page modal.** The current flow opens a `NewModal` wrapping `EditAvatarForm`; the new flow is inline on the card.

### Qualifications flow

`QualificationsCard.vue` delegates heavily to what already exists.

| State | Trigger | UI |
|---|---|---|
| Empty | `qualifications.length === 0` | Centered empty-state with 🎓 glyph, "Add your first qualification" heading, one-line helper, primary `+ Add qualification` button, and a row of non-interactive example tags ("Degree", "Diploma", "Certificate", "Training"). |
| Filled | `qualifications.length > 0` | Denser list than `QualificationList.vue`'s admin layout — one row per qualification with title, institution · year · status tag · document count, plus inline Edit / Attach actions. A full-width dashed `+ Add another qualification` button sits below the list. |

- The add / edit / delete / attach modals are the existing `Pages/Qualification/Add.vue`, `Edit.vue`, `Delete.vue`, `AttachDocument.vue`, mounted inside `NewModal` exactly as `PersonQualifications.vue` does today.
- The existing permission-gated **Approve** button is not rendered on My Profile (staff can't approve their own); all other permission checks (`edit staff qualification`, `delete staff qualification`, `create staff qualification`) carry over.
- After any successful mutation: close the modal, `router.reload({ only: ['qualifications'] })`.
- The existing "Download Profile PDF" link (rendered when the user has `qualifications.reports.export`) is kept in the card header for users with the permission.

### Identity strip & progress

`IdentityStrip.vue` is a single row on desktop, stacked on mobile:

```
[avatar 72×72]  Name (22px, bold)                              [progress chip]
                Current rank · Department · Staff #{staff_number}
```

`ProfileProgress.vue` computes completion client-side from the same props:

```js
const checkpoints = computed(() => [
  Boolean(props.person.image),
  props.qualifications.length > 0,
  Boolean(props.contacts?.some(c => c.contact_type === 'email'))
    && Boolean(props.contacts?.some(c => c.contact_type === 'phone')),
]);
const percent = computed(() => Math.round(checkpoints.value.filter(Boolean).length / 3 * 100));
```

- `< 100 %` renders an **amber** chip (`bg-amber-50 text-amber-800`) reading `X% complete`.
- `= 100 %` renders a **green** chip (`bg-emerald-50 text-emerald-800`) reading `✓ Profile complete`.
- No persistence, no API. Purely derived from the already-loaded props.

### Secondary section (Contact, Employment, Dependents)

Three equal-width `ReadOnlyKvCard.vue` instances, except Contact which has an Edit action.

- **Contact:** rows for Email, Phone, Primary Address. "Edit" link in the header opens the existing `EditContactForm.vue` (phone/email) or address modal. No new backend work.
- **Employment:** read-only rows — Rank (current), Unit (current), Joined (hire date). Header label reads "HR-managed" in muted grey; there is no pencil or edit affordance.
- **Dependents:** read-only list — Spouse name (if any), number of children. Header label reads "View only"; a muted, italic footer reads "Need changes? Contact HR." All add/edit buttons that `StaffDependents/Index.vue` currently renders are suppressed here.

### Dashboard banner & nav link

- **Nav:** append a "My Profile" link to the authenticated top bar in `resources/js/Layouts/NewAuthenticated.vue`, visible to any user whose `page.props.auth.user.person_id` is truthy.
- **Dashboard banner:** `resources/js/Components/MyProfile/CompletionBanner.vue`, rendered on the dashboard when the authenticated user has a `person_id` and at least one of "photo missing" or "qualifications count = 0" is true. Dismissible for the session via `sessionStorage` (no backend persistence, no new column). Clicking the banner deep-links to `/my-profile`. The banner needs `person_id`, `has_photo`, and `qualifications_count` to be present in `HandleInertiaRequests` shared props — add those three fields there so the banner can render without an extra HTTP call.
- **No forced redirect.** The banner is an invitation, not a gate.

### Mobile and dark mode

- `<768 px`: identity strip stacks (avatar on top, name/meta centred, chip on its own row); priority grid becomes a single column (photo card first, qualifications second); secondary grid collapses to one column. Drag-and-drop gracefully degrades to tap-to-pick.
- Every colour token has a `dark:` variant matching the existing app palette (greens and greys already in use across `NewShow.vue`, `QualificationList.vue`).

## Testing

Feature tests (PHPUnit, `tests/Feature/MyProfile/`):

- `MyProfileShowTest`
  - `guest_is_redirected_to_login`
  - `authenticated_user_without_person_id_gets_403`
  - `authenticated_user_with_active_staff_record_sees_page_with_their_data`
  - `authenticated_user_with_separated_staff_record_sees_404`
  - `payload_shape_matches_admin_staff_show_payload` (golden-shape test — asserts `StaffProfileProvider` returns the same keys/structure the admin page consumes)
- `MyProfilePhotoUploadTest`
  - `staff_can_upload_own_photo` (hits existing `person.avatar.update` — coverage here protects the My Profile flow specifically)
  - `staff_cannot_upload_photo_for_another_person` (existing behaviour; regression guard)
- `MyProfileQualificationTest`
  - `staff_can_add_qualification_to_own_record`
  - `approve_action_is_not_exposed_on_my_profile_page_props` (asserts permissions gate does what the UI assumes)
- `MyProfileContactEditTest`
  - `staff_can_update_own_email_and_phone`
  - `staff_cannot_update_contacts_for_another_person`

The refactor of `InstitutionPersonController@show` to use `StaffProfileProvider` is covered by the existing admin show tests (which must continue to pass). Add a regression test if none currently assert that the admin payload contains its expected keys — otherwise rely on the existing coverage.

Vue unit tests are not part of the existing project's conventions; we follow that. Manual verification checklist lives in the PR description.

## Rollout

- Feature is gated by the existence of the `/my-profile` route plus the nav link. No feature flag.
- No migrations, no seeders, no new permissions.
- The dashboard banner is dismissible per-session so it won't block anyone.
- Admin `Staff/NewShow.vue` behaviour is unchanged — no coordinated release needed.

## Future work (Spec 2)

The following was discussed and intentionally deferred to a follow-up spec so Spec 1 can ship quickly:

- **Admin approval for photo changes.** Mirror the existing `Qualification` approval pattern (`approved_by`, `approved_at`, an `approve photo` permission, admin queue UI). Until Spec 2 lands, photo updates go live immediately.
- **Email-change verification.** New `email_change_tokens` table, mailable, and verify endpoint. Until Spec 2 lands, email updates go live immediately.
- **Phone-change verification via OTP.** Requires an SMS vendor decision (Africa's Talking, Twilio, Vonage, …), a `phone_verification_codes` table with expiry + rate limit, send/verify endpoints, and a code-entry UI. Until Spec 2 lands, phone updates go live immediately.

When Spec 2 is written, the hook points are: `PhotoCard.vue` (add a "pending review" state), `ContactCard.vue` (add "verification sent — check your inbox" and "enter 6-digit code" states), and a new admin queue route for photo review. The current data shape is designed so those additions are new fields rather than schema rewrites.
