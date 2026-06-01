# User Photo in Top Navigation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Show the authenticated user's profile photo in the top-right of the top navigation, next to their name, falling back to the existing placeholder image when no photo is set.

**Architecture:** Add a `photo_url` to the shared Inertia `auth` data (sibling of `auth.user`, derived from the already-loaded `Person.image` with no new query), then bind it into the three authenticated layout menus with a placeholder fallback.

**Tech Stack:** Laravel 11 (Inertia middleware), Vue 3 + Inertia.js, PHPUnit feature tests.

---

## File Structure

- **Modify:** `app/Http/Middleware/HandleInertiaRequests.php` — add `photo_url` to shared `auth` data and to the `profileFactsForCurrentUser()` helper.
- **Modify:** `tests/Feature/MyProfile/SharedPropsTest.php` — add two assertions for `auth.photo_url`.
- **Modify:** `resources/js/Components/TopMenu.vue` — bind the existing `<img>` src to the photo URL (NewAuthenticated layout, 71 pages).
- **Modify:** `resources/js/Layouts/HrAuthenticated.vue` — add a photo `<img>` to the dropdown trigger (12 pages).
- **Modify:** `resources/js/Layouts/Authenticated.vue` — add a photo `<img>` to the dropdown trigger (1 page).

Backend and its test ship first (Task 1), so the frontend tasks (Tasks 2-4) consume a prop that already exists and is tested.

---

## Task 1: Share `photo_url` from the Inertia middleware

**Files:**
- Modify: `app/Http/Middleware/HandleInertiaRequests.php`
- Test: `tests/Feature/MyProfile/SharedPropsTest.php`

- [ ] **Step 1: Write the failing tests**

Add these two methods to `tests/Feature/MyProfile/SharedPropsTest.php`, after the existing `test_has_photo_flips_true_when_person_image_is_set` method (around line 38). They reuse the existing `createActiveStaff()` helper already in the file.

```php
    public function test_photo_url_is_storage_path_when_person_image_is_set(): void
    {
        $staff = $this->createActiveStaff();
        $staff->person->update(['image' => 'avatars/example.jpg']);
        $user = User::factory()->create(['person_id' => $staff->person_id]);

        $this->actingAs($user)
            ->get(route('my-profile.show'))
            ->assertInertia(fn ($page) => $page->where('auth.photo_url', '/storage/avatars/example.jpg'));
    }

    public function test_photo_url_is_null_when_person_has_no_image(): void
    {
        $staff = $this->createActiveStaff();
        $user = User::factory()->create(['person_id' => $staff->person_id]);

        $this->actingAs($user)
            ->get(route('my-profile.show'))
            ->assertInertia(fn ($page) => $page->where('auth.photo_url', null));
    }
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `php artisan test --filter=SharedPropsTest`
Expected: the two new tests FAIL because `auth.photo_url` is missing (Inertia assertion reports the prop does not exist / value mismatch). The four pre-existing tests still PASS.

- [ ] **Step 3: Add `photo_url` to the `profileFactsForCurrentUser()` helper**

In `app/Http/Middleware/HandleInertiaRequests.php`, update the helper so every return path includes a `photo_url` key.

Update the PHPDoc return shape (currently `array{has_photo: bool, qualifications_count: int}|null`):

```php
     * @return array{has_photo: bool, qualifications_count: int, photo_url: ?string}|null
```

Change the "no `$person`" early return (currently returns `has_photo`/`qualifications_count` only):

```php
        if (! $person) {
            return $this->profileFactsCache = [
                'has_photo' => false,
                'qualifications_count' => 0,
                'photo_url' => null,
            ];
        }
```

Change the final return:

```php
        return $this->profileFactsCache = [
            'has_photo' => (bool) $person->image,
            'qualifications_count' => (int) $person->qualifications_count,
            'photo_url' => $person->image ? '/storage/'.$person->image : null,
        ];
```

(The `! $user || ! $user->person_id` path already returns `null`, so callers using `['photo_url'] ?? null` get `null` there — no change needed.)

- [ ] **Step 4: Add `photo_url` to the shared `auth` block**

In the same file, in the `share()` method, add this line immediately after the existing `'has_photo' => ...` line (currently line 59), inside the `'auth' => [ ... ]` array:

```php
                'photo_url' => fn () => $this->profileFactsForCurrentUser($request)['photo_url'] ?? null,
```

- [ ] **Step 5: Run the tests to verify they pass**

Run: `php artisan test --filter=SharedPropsTest`
Expected: all six tests PASS (four pre-existing + two new).

- [ ] **Step 6: Format and commit**

```bash
vendor/bin/pint --dirty
git add app/Http/Middleware/HandleInertiaRequests.php tests/Feature/MyProfile/SharedPropsTest.php
git commit -m "feat: share user photo_url via Inertia auth props

Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>"
```

---

## Task 2: Show the photo in `TopMenu.vue` (NewAuthenticated, 71 pages)

**Files:**
- Modify: `resources/js/Components/TopMenu.vue`

This layout already renders an `<img>` with the correct classes; only the `src` is hardcoded. There is no JS-level test harness for these layout components in this repo (the test coverage for this feature is the Inertia-prop test in Task 1). Verification here is a manual build + visual check.

- [ ] **Step 1: Add a `photoUrl` computed**

In `resources/js/Components/TopMenu.vue`, the script already has `import { computed } from "vue";`, `import { ... usePage } from "@inertiajs/vue3";`, `const page = usePage();`, and `const user = computed(() => page.props?.auth.user);` (around line 14). Add directly below the `user` line:

```js
const photoUrl = computed(() => page.props?.auth?.photo_url);
```

- [ ] **Step 2: Bind the `<img>` src**

In the same file, find the profile-dropdown `<img>` (around lines 64-68):

```vue
						<img
							class="h-8 w-8 rounded-full bg-gray-50"
							src="/images/placeholder.webp"
							alt=""
						/>
```

Replace the `src="/images/placeholder.webp"` attribute with a bound src and an object-cover for non-square photos:

```vue
						<img
							class="h-8 w-8 rounded-full bg-gray-50 object-cover"
							:src="photoUrl || '/images/placeholder.webp'"
							alt=""
						/>
```

- [ ] **Step 3: Build the frontend to verify it compiles**

Run: `npm run build`
Expected: build completes with no errors referencing `TopMenu.vue`.

- [ ] **Step 4: Commit**

```bash
git add resources/js/Components/TopMenu.vue
git commit -m "feat: show user photo in TopMenu nav avatar

Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>"
```

---

## Task 3: Add the photo to `HrAuthenticated.vue` (12 pages)

**Files:**
- Modify: `resources/js/Layouts/HrAuthenticated.vue`

This layout's dropdown trigger is currently text-only (`{{ user.name }}` + chevron). Add an avatar `<img>` before the name.

- [ ] **Step 1: Add a `photoUrl` computed**

In `resources/js/Layouts/HrAuthenticated.vue`, the script setup already has `import { computed } from "vue";`, `import { ... usePage } from "@inertiajs/vue3";`, `const page = usePage();`, and `const user = computed(() => page.props?.auth.user);` (line 11). Add directly below the `user` line:

```js
const photoUrl = computed(() => page.props?.auth?.photo_url);
```

- [ ] **Step 2: Add the `<img>` to the dropdown trigger button**

In the same file, find the trigger button content (around line 80):

```vue
											{{ user.name }}
```

Insert the avatar image immediately before that `{{ user.name }}` line, so the button shows photo then name:

```vue
											<img
												class="h-8 w-8 rounded-full object-cover mr-2 bg-gray-50"
												:src="photoUrl || '/images/placeholder.webp'"
												alt=""
											/>
											{{ user.name }}
```

- [ ] **Step 3: Build the frontend to verify it compiles**

Run: `npm run build`
Expected: build completes with no errors referencing `HrAuthenticated.vue`.

- [ ] **Step 4: Commit**

```bash
git add resources/js/Layouts/HrAuthenticated.vue
git commit -m "feat: show user photo in HrAuthenticated nav

Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>"
```

---

## Task 4: Add the photo to `Authenticated.vue` (1 page)

**Files:**
- Modify: `resources/js/Layouts/Authenticated.vue`

Identical change to Task 3, in the other Breeze-style layout. Its trigger button content `{{ user.name }}` is around line 63.

- [ ] **Step 1: Add a `photoUrl` computed**

In `resources/js/Layouts/Authenticated.vue`, the script setup already has `import { ref, computed } from "vue";`, `import { Link, usePage } from "@inertiajs/vue3";`, `const page = usePage();`, and `const user = computed(() => page.props?.auth.user);` (line 11). Add directly below the `user` line:

```js
const photoUrl = computed(() => page.props?.auth?.photo_url);
```

- [ ] **Step 2: Add the `<img>` to the dropdown trigger button**

In the same file, find the trigger button content (around line 63):

```vue
											{{ user.name }}
```

Insert the avatar image immediately before that `{{ user.name }}` line:

```vue
											<img
												class="h-8 w-8 rounded-full object-cover mr-2 bg-gray-50"
												:src="photoUrl || '/images/placeholder.webp'"
												alt=""
											/>
											{{ user.name }}
```

- [ ] **Step 3: Build the frontend to verify it compiles**

Run: `npm run build`
Expected: build completes with no errors referencing `Authenticated.vue`.

- [ ] **Step 4: Commit**

```bash
git add resources/js/Layouts/Authenticated.vue
git commit -m "feat: show user photo in Authenticated nav

Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>"
```

---

## Final Verification

- [ ] **Run the affected backend test:** `php artisan test --filter=SharedPropsTest` → all six pass.
- [ ] **Run Pint:** `vendor/bin/pint --dirty` → no changes needed (clean).
- [ ] **Build:** `npm run build` → succeeds.
- [ ] Confirm the four commits are present: `git log --oneline -4`.
