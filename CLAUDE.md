# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

HR Management System built with Laravel 11.x and Vue.js 3 + Inertia.js. Manages staff, organizational units, geographic regions, and provides comprehensive reporting capabilities.

### Recent Features (v2024-2025)

- **Permissions & Roles Management** (Jan 2025)
  - Full CRUD for permissions and roles
  - Assign/revoke permissions to roles
  - Assign/revoke roles and direct permissions to users
  - Vue components: Permission management UI with FormKit
  - Controllers: `PermissionController`, `RoleController`, `RolePermissionController`, `PersonRolesController`

- **Staff Separation Feature** (Nov 2024)
  - Global scope for filtering separated staff
  - Staff status tracking with separation dates
  - Dedicated controllers and views for separated staff management

- **Institution Permissions** (Dec 2024)
  - Multi-tenant permission support
  - Institution-level access control for staff

## Tech Stack

- **Backend**: Laravel 11.x, PHP 8.4, MySQL
- **Frontend**: Vue.js 3.5.19, Inertia.js 1.2, Tailwind CSS 3.4.17, FormKit 0.17.x, HeadlessUI 1.7, Chart.js 4.5
- **Authorization**: Spatie Laravel Permission 6.7 (role-based with permissions management)
- **Authentication**: Laravel Breeze 2.x with custom password change middleware
- **Exports**: Maatwebsite Excel 3.1
- **Activity Tracking**: Spatie Activity Log 4.5
- **Debugging**: Laravel Telescope 5.x, Laravel Debugbar 3.7
- **PDF Generation**: DomPDF 2.0
- **Additional Tools**: Laravel Boost 1.0 (MCP server), Laravel Pint 1.13, Ziggy 1.0

### Required PHP Extensions
- mbstring (currently using polyfill at `bootstrap/mbstring-polyfill.php`)
- pdo_mysql (required for database connectivity)
- xml, curl, fileinfo, openssl, json

## Essential Commands

```bash
# Development
php artisan serve                     # Start dev server (http://127.0.0.1:8000)
npm run dev                           # Start Vite with hot reload
composer run dev                      # Alternative to npm run dev

# Database
php artisan migrate                   # Run migrations
php artisan migrate:fresh --seed     # Reset database with seeders (destroys data!)
php artisan db:seed                  # Run seeders

# Testing
php artisan test                      # Run all tests
php artisan test --filter TestName   # Run specific test
php artisan test tests/Feature/      # Run feature tests only

# Code Quality
./vendor/bin/pint                     # Format PHP code (PSR-12)
./vendor/bin/pint --dirty            # Format only changed files
npm run lint                         # ESLint for JavaScript
npm run format                       # Prettier formatting

# Production
npm run build                        # Build frontend assets
php artisan optimize:clear           # Clear all caches
php artisan telescope:prune          # Clean old Telescope entries
```

## Architecture

### Laravel 11 Hybrid Structure
**IMPORTANT**: This project runs Laravel 11 but uses the legacy Laravel 10 structure (recommended by Laravel for upgraded projects).

- **Middleware**: Registered in `app/Http/Kernel.php` (not bootstrap/app.php)
- **Exception Handling**: In `app/Exceptions/Handler.php`
- **Console Commands**: Auto-discovered from `app/Console/Commands`
- **Service Providers**: Located in `app/Providers/`
- **Rate Limiting**: Configured in `RouteServiceProvider` or `app/Http/Kernel.php`

This structure is perfectly valid and recommended when upgrading from Laravel 10 to 11.

### Domain Model

```
Person (central entity)
├── InstitutionPerson (Staff pivot with extra fields)
│   ├── StaffUnit (Unit assignments with dates)
│   ├── JobStaff (Rank/position history)
│   └── PositionStaff (Position assignments)
├── Dependents
├── Contacts (phone, email, emergency)
├── PersonIdentity (national ID, passport)
└── Qualifications (with documents)

Institution
├── Units (hierarchical: Department → Division → Unit)
└── InstitutionPerson (staff relationships)

Geographic Structure:
Region → District → Office → Units

Job Management:
JobCategory → Job (rank hierarchy)
Position (specific roles)
```

### Key Patterns

1. **Inertia.js SPA Pattern**
   - Controllers return `Inertia::render('Page/Component', ['data' => $data])`
   - Vue pages in `resources/js/Pages/`
   - Shared components in `resources/js/Components/`

2. **Authorization & Permissions System**
   - Always use `Gate::allows('permission-name')` in controllers
   - Permissions follow pattern: `model.action` (e.g., `staff.create`, `reports.view`)
   - Three main roles: `super-administrator`, `admin`, `staff`
   - Policies in `app/Policies/`
   - **Permission Management**:
     - Users can be assigned roles and direct permissions
     - Controllers: `PermissionController`, `RoleController`, `RolePermissionController`, `PersonRolesController`
     - Comprehensive permission seeders for all modules (Users, Units, Jobs, Reports, etc.)
     - Permission assignment seeded via `AssignRolePermissionSeeder`

3. **Data Export System**
   - Export classes in `app/Exports/`
   - Implements Maatwebsite Excel interfaces
   - Queued exports for large datasets

4. **Soft Deletes**
   - Implemented on all major models
   - Use `withTrashed()` to include deleted records
   - Activity log tracks all changes

5. **Custom Middleware**
   - `PasswordChanged`: Forces password change on first login
   - `HandleInertiaRequests`: Shares global data with Vue

## Form Request Validation

All validation uses dedicated Form Request classes in `app/Http/Requests/`:
- `Store*Request` for creation
- `Update*Request` for updates
- Consistent validation rules and error messages

## Important Business Rules

1. **Staff Management**
   - Person → InstitutionPerson creates staff record
   - Staff can have multiple unit assignments (history tracked)
   - Promotions tracked through JobStaff pivot
   - Transfers managed via unit assignment dates

2. **Unit Hierarchy**
   - Units can have parent-child relationships
   - Units belong to Institutions
   - Geographic assignment through Office relationships

3. **Status Tracking**
   - Employee statuses: Active, Retired, Leave, Suspended, etc.
   - Each status change is logged with dates
   - Separation types tracked separately

4. **Permissions & Access Control**
   - Granular permission system using Spatie Laravel Permission
   - Permissions organized by module: users, staff, units, jobs, reports, qualifications, etc.
   - Users can have multiple roles and additional direct permissions
   - Role-permission relationships managed through dedicated controllers
   - All permissions seeded with descriptive names (e.g., `users.create`, `reports.promotions.view`)
   - Institution-level permissions for multi-tenant scenarios

## Known Issues & Best Practices

1. **mbstring Extension**
   - Currently using polyfill at `bootstrap/mbstring-polyfill.php`
   - Consider installing proper extension for better performance: `sudo apt-get install php8.4-mbstring`

2. **Export Classes**
   - Some export classes may have complex query logic - verify before modifying
   - Exports are queued for large datasets

3. **Vue 3.5 Compatibility**
   - Vue 3.5+ is stricter about v-model on props
   - **Pattern**: Create local ref copy of props for v-model: `const localProp = ref([...props.propName])`
   - See examples in: `AddUserPermission.vue`, `UserRoleForm.vue`

4. **Git Staging**
   - Uncommitted files in dev branch: boost.json, CategoryRanksController.php updates
   - Run `git status` before committing to avoid including unrelated changes

## Development Workflow

### 1. Starting Development
```bash
# Start backend server
php artisan serve

# Start frontend dev server (in another terminal)
npm run dev
# or
composer run dev

# Check application status
php artisan about
```

### 2. Feature Development Workflow

#### A. Backend (Laravel)
```bash
# Create model with all supporting files
php artisan make:model ModelName -mfsc  # Migration, Factory, Seeder, Controller

# Create Form Request classes for validation
php artisan make:request StoreModelNameRequest
php artisan make:request UpdateModelNameRequest

# Create policy for authorization
php artisan make:policy ModelNamePolicy --model=ModelName

# Create custom middleware (if needed)
php artisan make:middleware CustomMiddlewareName

# Create job for queued tasks (if needed)
php artisan make:job ProcessModelNameJob
```

#### B. Database Workflow
```bash
# Run new migrations
php artisan migrate

# Rollback last migration
php artisan migrate:rollback

# Refresh database with seeders (DESTROYS DATA!)
php artisan migrate:fresh --seed

# Run specific seeder
php artisan db:seed --class=ModelNameSeeder

# Check database status
php artisan migrate:status

# Use tinker for testing queries
php artisan tinker
```

#### C. Routes & Controllers
- Add routes to `routes/web.php` with proper naming
- Use resource routes when appropriate: `Route::resource('model', ModelController::class)`
- Always check authorization with `Gate::allows('permission.name')`
- Return Inertia responses: `Inertia::render('Page/Component', $data)`

```php
// Example route with authorization
Route::middleware(['auth'])->group(function () {
    Route::get('/model', [ModelController::class, 'index'])
        ->name('model.index')
        ->middleware('can:model.view');
});
```

### 3. Frontend Development (Vue + Inertia)

#### A. Vue Component Structure
```
resources/js/
├── Pages/              # Inertia page components
│   └── ModelName/
│       ├── Index.vue   # List view
│       ├── Create.vue  # Create form
│       ├── Edit.vue    # Edit form
│       └── Show.vue    # Detail view
├── Components/         # Reusable components
│   ├── Shared/        # Shared across features
│   └── ModelName/     # Feature-specific
└── Layouts/           # Page layouts
```

#### B. Vue Component Best Practices
- **Always** use `<script setup>` syntax
- Import Inertia components: `import { Link, Head } from '@inertiajs/vue3'`
- Use FormKit for complex forms with validation
- Follow existing Tailwind patterns for styling
- Use HeadlessUI components for dropdowns, modals, etc.
- Check existing components before creating new ones

```vue
<script setup>
import { ref, computed } from 'vue'
import { router, Link, Head } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

const props = defineProps({
    items: Array,
    can: Object
})

// For v-model on props, create local ref
const localItems = ref([...props.items])
</script>

<template>
    <Head title="Page Title" />
    <AuthenticatedLayout>
        <!-- Content -->
    </AuthenticatedLayout>
</template>
```

#### C. Form Handling with Inertia
- Use `router.post()`, `router.put()`, etc. - NOT regular HTML forms
- Handle errors from Form Request validation
- Show loading states during submission

```vue
<script setup>
import { reactive } from 'vue'
import { router } from '@inertiajs/vue3'

const form = reactive({
    name: '',
    description: ''
})

function submit() {
    router.post(route('model.store'), form, {
        onSuccess: () => {
            // Handle success
        },
        onError: (errors) => {
            // Handle validation errors
        }
    })
}
</script>
```

### 4. Permissions & Authorization

#### A. Creating Permissions
```bash
# Create permission seeder
php artisan make:seeder ModelNamePermissionSeeder

# Add permissions following naming pattern:
# model.view, model.create, model.edit, model.delete
```

#### B. Seeder Pattern
```php
Permission::create(['name' => 'model.view']);
Permission::create(['name' => 'model.create']);
Permission::create(['name' => 'model.edit']);
Permission::create(['name' => 'model.delete']);

// Assign to roles
$adminRole = Role::findByName('admin');
$adminRole->givePermissionTo(['model.view', 'model.create', 'model.edit']);
```

#### C. Using Permissions
```php
// In controllers
if (!Gate::allows('model.create')) {
    abort(403);
}

// In Blade/Vue (via shared data)
v-if="$page.props.can.model.create"
```

### 5. Testing

#### A. Create Tests
```bash
# Feature tests (most common)
php artisan make:test ModelNameTest

# Unit tests
php artisan make:test ModelNameTest --unit

# Test specific functionality
php artisan make:test ModelName/CreateModelTest
```

#### B. Running Tests
```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Feature/ModelNameTest.php

# Run specific test method
php artisan test --filter testCanCreateModel

# Run with coverage (if configured)
php artisan test --coverage
```

#### C. Test Best Practices
- Use factories for creating test data: `ModelName::factory()->create()`
- Check for custom factory states before manually setting up models
- Test happy paths, failure paths, and edge cases
- Test authorization (403 responses for unauthorized users)
- Use `$this->actingAs($user)` for authenticated tests

### 6. Code Quality & Formatting

```bash
# Format PHP code (Laravel Pint)
./vendor/bin/pint

# Format only changed files
./vendor/bin/pint --dirty

# Format JavaScript/Vue (ESLint + Prettier)
npm run lint
npm run format

# Full pre-commit check
./vendor/bin/pint --dirty && npm run lint && php artisan test
```

### 7. Debugging

#### A. Laravel Telescope
- Access at: `http://localhost:8000/telescope`
- View queries, requests, exceptions, logs, jobs, etc.
- Prune old entries: `php artisan telescope:prune`

#### B. Laravel Debugbar
- Appears at bottom of page in development
- Shows queries, timeline, views, routes, etc.

#### C. Logs & Tinker
```bash
# View logs
tail -f storage/logs/laravel.log

# Use Tinker for testing
php artisan tinker
>>> User::count()
>>> ModelName::with('relation')->find(1)
```

#### D. Laravel Boost (MCP)
- Use Boost tools for debugging: `tinker`, `database-query`, `last-error`
- Read logs: Use `read-log-entries` tool
- Check application info: `application-info`

### 8. Database Management

```bash
# Dump current schema
php artisan schema:dump

# Check migration status
php artisan migrate:status

# Create migration for existing table
php artisan make:migration add_column_to_table --table=table_name

# Create pivot table migration
php artisan make:migration create_model1_model2_table
```

### 9. Git Workflow

```bash
# Check status before committing
git status

# Stage changes
git add .

# Run pre-commit checks
./vendor/bin/pint --dirty && npm run lint && php artisan test

# Commit with descriptive message
git commit -m "feat: Add model management feature"

# Push to branch
git push origin feature-branch

# Create pull request
gh pr create --title "Feature: Model Management" --body "Description"
```

### 10. Common Issues & Solutions

| Issue | Solution |
|-------|----------|
| Vite manifest error | Run `npm run dev` or `npm run build` |
| Class not found | Run `composer dump-autoload` |
| Permission denied | Check file permissions: `chmod -R 775 storage bootstrap/cache` |
| Migration fails | Check database connection in `.env` |
| Routes not working | Clear cache: `php artisan optimize:clear` |
| Vue component not updating | Check Vite is running, hard refresh browser |
| 403 Forbidden | Check permissions and Gate policies |

## Environment Variables

Required in `.env`:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=audit
DB_USERNAME=root
DB_PASSWORD=

APP_URL=http://localhost:8000
SESSION_DOMAIN=localhost
```

## Debugging Tools

- **Laravel Telescope**: `/telescope` (local only)
- **Laravel Debugbar**: Shown in development mode
- **Logs**: `storage/logs/laravel.log`
- **Vue Devtools**: For frontend debugging

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to enhance the user's satisfaction building Laravel applications.

## Foundational Context
This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.4.13
- inertiajs/inertia-laravel (INERTIA) - v1
- laravel/framework (LARAVEL) - v11
- laravel/prompts (PROMPTS) - v0
- laravel/sanctum (SANCTUM) - v4
- laravel/telescope (TELESCOPE) - v5
- tightenco/ziggy (ZIGGY) - v1
- laravel/breeze (BREEZE) - v2
- laravel/mcp (MCP) - v0
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- phpunit/phpunit (PHPUNIT) - v11
- @inertiajs/vue3 (INERTIA) - v1
- eslint (ESLINT) - v8
- prettier (PRETTIER) - v3
- tailwindcss (TAILWINDCSS) - v3
- vue (VUE) - v3

## Conventions
- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts
- Do not create verification scripts or tinker when tests cover that functionality and prove it works. Unit and feature tests are more important.

## Application Structure & Architecture
- Stick to existing directory structure - don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling
- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Replies
- Be concise in your explanations - focus on what's important rather than explaining obvious details.

## Documentation Files
- You must only create documentation files if explicitly requested by the user.


=== boost rules ===

## Laravel Boost
- Laravel Boost is an MCP server that comes with powerful tools designed specifically for this application. Use them.

## Artisan
- Use the `list-artisan-commands` tool when you need to call an Artisan command to double check the available parameters.

## URLs
- Whenever you share a project URL with the user you should use the `get-absolute-url` tool to ensure you're using the correct scheme, domain / IP, and port.

## Tinker / Debugging
- You should use the `tinker` tool when you need to execute PHP to debug code or query Eloquent models directly.
- Use the `database-query` tool when you only need to read from the database.

## Reading Browser Logs With the `browser-logs` Tool
- You can read browser logs, errors, and exceptions using the `browser-logs` tool from Boost.
- Only recent browser logs will be useful - ignore old logs.

## Searching Documentation (Critically Important)
- Boost comes with a powerful `search-docs` tool you should use before any other approaches. This tool automatically passes a list of installed packages and their versions to the remote Boost API, so it returns only version-specific documentation specific for the user's circumstance. You should pass an array of packages to filter on if you know you need docs for particular packages.
- The 'search-docs' tool is perfect for all Laravel related packages, including Laravel, Inertia, Livewire, Filament, Tailwind, Pest, Nova, Nightwatch, etc.
- You must use this tool to search for Laravel-ecosystem documentation before falling back to other approaches.
- Search the documentation before making code changes to ensure we are taking the correct approach.
- Use multiple, broad, simple, topic based queries to start. For example: `['rate limiting', 'routing rate limiting', 'routing']`.
- Do not add package names to queries - package information is already shared. For example, use `test resource table`, not `filament 4 test resource table`.

### Available Search Syntax
- You can and should pass multiple queries at once. The most relevant results will be returned first.

1. Simple Word Searches with auto-stemming - query=authentication - finds 'authenticate' and 'auth'
2. Multiple Words (AND Logic) - query=rate limit - finds knowledge containing both "rate" AND "limit"
3. Quoted Phrases (Exact Position) - query="infinite scroll" - Words must be adjacent and in that order
4. Mixed Queries - query=middleware "rate limit" - "middleware" AND exact phrase "rate limit"
5. Multiple Queries - queries=["authentication", "middleware"] - ANY of these terms


=== php rules ===

## PHP

- Always use curly braces for control structures, even if it has one line.

### Constructors
- Use PHP 8 constructor property promotion in `__construct()`.
    - <code-snippet>public function __construct(public GitHub $github) { }</code-snippet>
- Do not allow empty `__construct()` methods with zero parameters.

### Type Declarations
- Always use explicit return type declarations for methods and functions.
- Use appropriate PHP type hints for method parameters.

<code-snippet name="Explicit Return Types and Method Params" lang="php">
protected function isAccessible(User $user, ?string $path = null): bool
{
    ...
}
</code-snippet>

## Comments
- Prefer PHPDoc blocks over comments. Never use comments within the code itself unless there is something _very_ complex going on.

## PHPDoc Blocks
- Add useful array shape type definitions for arrays when appropriate.

## Enums
- Typically, keys in an Enum should be TitleCase. For example: `FavoritePerson`, `BestLake`, `Monthly`.


=== inertia-laravel/core rules ===

## Inertia Core

- Inertia.js components should be placed in the `resources/js/Pages` directory unless specified differently in the JS bundler (vite.config.js).
- Use `Inertia::render()` for server-side routing instead of traditional Blade views.
- Use `search-docs` for accurate guidance on all things Inertia.

<code-snippet lang="php" name="Inertia::render Example">
// routes/web.php example
Route::get('/users', function () {
    return Inertia::render('Users/Index', [
        'users' => User::all()
    ]);
});
</code-snippet>


=== inertia-laravel/v1 rules ===

## Inertia v1

- Inertia v1 does _not_ come with these features. Do not recommend using these Inertia v2 features directly.
    - Polling
    - Prefetching
    - Deferred props
    - Infinite scrolling using merging props and `WhenVisible`
    - Lazy loading data on scroll


=== laravel/core rules ===

## Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using the `list-artisan-commands` tool.
- If you're creating a generic PHP class, use `artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Database
- Always use proper Eloquent relationship methods with return type hints. Prefer relationship methods over raw queries or manual joins.
- Use Eloquent models and relationships before suggesting raw database queries
- Avoid `DB::`; prefer `Model::query()`. Generate code that leverages Laravel's ORM capabilities rather than bypassing them.
- Generate code that prevents N+1 query problems by using eager loading.
- Use Laravel's query builder for very complex database operations.

### Model Creation
- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `list-artisan-commands` to check the available options to `php artisan make:model`.

### APIs & Eloquent Resources
- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

### Controllers & Validation
- Always create Form Request classes for validation rather than inline validation in controllers. Include both validation rules and custom error messages.
- Check sibling Form Requests to see if the application uses array or string based validation rules.

### Queues
- Use queued jobs for time-consuming operations with the `ShouldQueue` interface.

### Authentication & Authorization
- Use Laravel's built-in authentication and authorization features (gates, policies, Sanctum, etc.).

### URL Generation
- When generating links to other pages, prefer named routes and the `route()` function.

### Configuration
- Use environment variables only in configuration files - never use the `env()` function directly outside of config files. Always use `config('app.name')`, not `env('APP_NAME')`.

### Testing
- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] <name>` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

### Vite Error
- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.


=== laravel/v11 rules ===

## Laravel 11

- Use the `search-docs` tool to get version specific documentation.
- This project upgraded from Laravel 10 without migrating to the new streamlined Laravel 11 file structure.
- This is **perfectly fine** and recommended by Laravel. Follow the existing structure from Laravel 10. We do not to need migrate to the Laravel 11 structure unless the user explicitly requests that.

### Laravel 10 Structure
- Middleware typically live in `app/Http/Middleware/` and service providers in `app/Providers/`.
- There is no `bootstrap/app.php` application configuration in a Laravel 10 structure:
    - Middleware registration is in `app/Http/Kernel.php`
    - Exception handling is in `app/Exceptions/Handler.php`
    - Console commands and schedule registration is in `app/Console/Kernel.php`
    - Rate limits likely exist in `RouteServiceProvider` or `app/Http/Kernel.php`

### Database
- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
- Laravel 11 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models
- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.

### New Artisan Commands
- List Artisan commands using Boost's MCP tool, if available. New commands available in Laravel 11:
    - `php artisan make:enum`
    - `php artisan make:class`
    - `php artisan make:interface`


=== pint/core rules ===

## Laravel Pint Code Formatter

- You must run `vendor/bin/pint --dirty` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test`, simply run `vendor/bin/pint` to fix any formatting issues.


=== phpunit/core rules ===

## PHPUnit Core

- This application uses PHPUnit for testing. All tests must be written as PHPUnit classes. Use `php artisan make:test --phpunit <name>` to create a new test.
- If you see a test using "Pest", convert it to PHPUnit.
- Every time a test has been updated, run that singular test.
- When the tests relating to your feature are passing, ask the user if they would like to also run the entire test suite to make sure everything is still passing.
- Tests should test all of the happy paths, failure paths, and weird paths.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files, these are core to the application.

### Running Tests
- Run the minimal number of tests, using an appropriate filter, before finalizing.
- To run all tests: `php artisan test`.
- To run all tests in a file: `php artisan test tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `php artisan test --filter=testName` (recommended after making a change to a related file).


=== inertia-vue/core rules ===

## Inertia + Vue

- Vue components must have a single root element.
- Use `router.visit()` or `<Link>` for navigation instead of traditional links.

<code-snippet name="Inertia Client Navigation" lang="vue">

    import { Link } from '@inertiajs/vue3'
    <Link href="/">Home</Link>

</code-snippet>


=== inertia-vue/v1/forms rules ===

## Inertia + Vue Forms

- For form handling in Inertia pages, use `router.post` and related methods. Do not use regular forms.


<code-snippet lang="vue" name="Inertia Vue Form Example">
<script setup>
    import { reactive } from 'vue'
    import { router } from '@inertiajs/vue3'
    import { usePage } from '@inertiajs/vue3'

    const page = usePage()

    const form = reactive({
        first_name: null,
        last_name: null,
        email: null,
    })

    function submit() {
        router.post('/users', form)
    }
</script>

<template>
    <h1>Create {{ page.modelName }}</h1>
    <form @submit.prevent="submit">
        <label for="first_name">First name:</label>
        <input id="first_name" v-model="form.first_name" />
        <label for="last_name">Last name:</label>
        <input id="last_name" v-model="form.last_name" />
        <label for="email">Email:</label>
        <input id="email" v-model="form.email" />
        <button type="submit">Submit</button>
    </form>
</template>
</code-snippet>


=== tailwindcss/core rules ===

## Tailwind Core

- Use Tailwind CSS classes to style HTML, check and use existing tailwind conventions within the project before writing your own.
- Offer to extract repeated patterns into components that match the project's conventions (i.e. Blade, JSX, Vue, etc..)
- Think through class placement, order, priority, and defaults - remove redundant classes, add classes to parent or child carefully to limit repetition, group elements logically
- You can use the `search-docs` tool to get exact examples from the official documentation when needed.

### Spacing
- When listing items, use gap utilities for spacing, don't use margins.

    <code-snippet name="Valid Flex Gap Spacing Example" lang="html">
        <div class="flex gap-8">
            <div>Superior</div>
            <div>Michigan</div>
            <div>Erie</div>
        </div>
    </code-snippet>


### Dark Mode
- If existing pages and components support dark mode, new pages and components must support dark mode in a similar way, typically using `dark:`.


=== tailwindcss/v3 rules ===

## Tailwind 3

- Always use Tailwind CSS v3 - verify you're using only classes supported by this version.


=== tests rules ===

## Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test` with a specific filename or filter.
</laravel-boost-guidelines>
