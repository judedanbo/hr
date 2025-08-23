# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a Laravel 9.x HR Management System with Vue.js 3 + Inertia.js frontend. The system manages staff, organizational units, geographic regions, and provides comprehensive reporting capabilities.

## Development Commands

```bash
# Backend Development
composer install              # Install PHP dependencies
php artisan serve            # Start development server (http://127.0.0.1:8000)
php artisan migrate          # Run database migrations
php artisan migrate:fresh    # Reset and re-run all migrations (WARNING: destroys data)
php artisan test             # Run PHPUnit tests
./vendor/bin/pint            # Format PHP code (Laravel Pint)
php artisan telescope        # Debug with Laravel Telescope

# Frontend Development  
npm install                  # Install Node dependencies
npm run dev                  # Start Vite dev server with hot reload
npm run build                # Production build
npm run lint                 # Run ESLint
npm run format               # Format with Prettier

# Docker Environment
docker-compose up -d         # Start containerized environment
```

## Architecture & Key Patterns

### Tech Stack
- **Backend**: Laravel 9.x, PHP 8.0.2+, MySQL
- **Frontend**: Vue.js 3, Inertia.js, Tailwind CSS, FormKit
- **Authorization**: Spatie Laravel Permission (role-based)
- **Authentication**: Laravel Breeze with custom password change middleware

### Core Domain Model

```
Person (central entity)
├── InstitutionPerson (Staff relationship)
│   └── StaffUnit (Unit assignments)
├── Dependents
├── Contacts  
├── Identities (national ID, passport, etc.)
└── Qualifications

Institution → Units (hierarchical departments/divisions)
             └── Parent/Child relationships

Geographic Hierarchy:
Region → District → Office → Units

Job → JobCategory (rank/position hierarchy)
```

### Key Architectural Decisions

1. **Inertia.js Pattern**: Server-side routing with client-side reactivity. Controllers return `Inertia::render()` with Vue components.

2. **Authorization**: Always use `Gate::allows()` instead of `auth()->user()->can()` for consistency.

3. **Soft Deletes**: Implemented across all models - use `withTrashed()` when needed.

4. **Activity Logging**: All model changes tracked via Spatie Activity Log.

5. **Export System**: Dedicated export classes in `app/Exports/` for each report type.

## Common Development Tasks

### Creating New Features

1. **New Model/Migration**:
```bash
php artisan make:model ModelName -m
php artisan make:policy ModelNamePolicy --model=ModelName
```

2. **New Controller with Views**:
```bash
php artisan make:controller ModelNameController --resource
# Create corresponding Vue components in resources/js/Pages/
```

3. **Add Permissions**:
- Define in database seeder or migration
- Check with `Gate::allows('permission-name')` in controllers
- Assign via Spatie methods

### Working with Vue Components

- Components located in `resources/js/Pages/` (page components) and `resources/js/Components/` (reusable)
- Use FormKit for complex forms: `@formkit/vue`
- Charts via Chart.js with Vue wrapper
- Follow existing component patterns for consistency

### Database Operations

```bash
# Create new migration
php artisan make:migration create_table_name

# Run specific migration
php artisan migrate --path=database/migrations/2025_01_22_example.php

# Rollback last migration
php artisan migrate:rollback

# Seed database
php artisan db:seed
```

### Testing

```bash
# Run all tests
php artisan test

# Run specific test
php artisan test --filter TestClassName

# Run with coverage
php artisan test --coverage
```

## Important Business Logic

### Staff Management Flow
1. Create Person → Create InstitutionPerson (links to Institution)
2. Assign to Unit via StaffUnit
3. Manage promotions through Job/JobCategory changes
4. Track transfers via unit assignment history

### Unit Hierarchy
- Units can have parent-child relationships
- Units belong to Institutions
- Units can be assigned to geographic Offices
- Department → Division → Unit hierarchy common

### Permission System
- Three main roles: `super-administrator`, `admin`, `staff`
- Permissions follow pattern: `model.action` (e.g., `staff.create`, `unit.delete`)
- Super-administrators bypass all permission checks

## Code Style Guidelines

### PHP/Laravel
- Use Laravel Pint for formatting (configured for PSR-12)
- Follow Laravel naming conventions
- Use Eloquent relationships over raw queries
- Implement policies for authorization

### Vue/JavaScript  
- Use Composition API for new components
- Follow existing FormKit patterns for forms
- Use Tailwind classes, avoid inline styles
- Components should be self-contained with clear props

### Git Workflow
- Feature branches from `main`
- Descriptive commit messages
- Run linters before committing:
```bash
./vendor/bin/pint && npm run lint
```

## Debugging

- Laravel Telescope available at `/telescope` (local only)
- Check `storage/logs/laravel.log` for errors
- Vue Devtools for frontend debugging
- `dd()` and `dump()` for backend debugging

## Environment Configuration

Required `.env` variables:
- Standard Laravel database config (DB_*)
- Mail configuration for notifications
- `APP_URL` must match your local domain
- `SESSION_DOMAIN` for Inertia.js