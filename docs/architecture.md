# Architecture

## Overview

MDPMS is a server-rendered Laravel 12 application. Requests come in over HTTP, the `web` middleware stack handles session + CSRF, route files dispatch to controllers, controllers delegate to Eloquent models and (for payroll) a domain service, and Blade views render the response.

```
Browser ──HTTP──▶ Laravel router (web middleware) ──▶ Controller ──▶ Service / Eloquent ──▶ MySQL
                                                          │
                                                          └──▶ Blade view ──HTML──▶ Browser
```

There is no SPA, no API, no queue worker on the hot path. Payroll runs synchronously inside a DB transaction.

## Directory layout

The project follows the **flat Laravel 12 layout, sub-namespaced by domain** pattern. Each standard top-level directory exists, and inside it controllers, requests, and views are grouped by domain folder.

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Controller.php             ← base controller
│   │   ├── DashboardController.php
│   │   ├── Auth/
│   │   │   ├── LoginController.php
│   │   │   └── RegisterController.php
│   │   ├── Department/
│   │   │   └── DepartmentController.php
│   │   ├── Employee/
│   │   │   └── EmployeeController.php
│   │   └── Payroll/
│   │       ├── PayrollController.php          ← run payroll (create/store)
│   │       ├── PayrollHistoryController.php   ← list/filter
│   │       └── PayslipController.php          ← view + PDF export
│   └── Requests/
│       ├── Auth/{Login,Register}Request.php
│       ├── Department/{Store,Update}DepartmentRequest.php
│       ├── Employee/{Store,Update}EmployeeRequest.php
│       └── Payroll/RunPayrollRequest.php
├── Models/
│   ├── User.php
│   ├── Department.php
│   ├── Employee.php
│   └── PayrollRecord.php
└── Services/
    └── Payroll/
        ├── PayrollCalculator.php       ← pure formula service
        └── PayrollBreakdown.php        ← readonly DTO of all 7 numbers

resources/views/
├── layouts/{app,guest}.blade.php
├── auth/{login,register}.blade.php
├── dashboard.blade.php
├── departments/{index,create,edit,_form}.blade.php
├── employees/{index,create,edit,_form}.blade.php
└── payroll/
    ├── run.blade.php
    ├── history.blade.php
    ├── payslip.blade.php
    ├── payslip-pdf.blade.php           ← print-friendly variant for dompdf
    └── _payslip-card.blade.php

routes/
├── web.php             ← / and /dashboard only
├── auth.php            ← login, register, logout
├── departments.php     ← 6 department routes
├── employees.php       ← 6 employee routes
└── payroll.php         ← 5 payroll routes

bootstrap/app.php       ← registers the per-module route files
```

## Route module loading

`routes/web.php` deliberately holds only the entry redirect and dashboard route. The four domain route files are loaded in `bootstrap/app.php` via the `then` callback on `withRouting()`:

```php
->withRouting(
    web: __DIR__.'/../routes/web.php',
    commands: __DIR__.'/../routes/console.php',
    health: '/up',
    then: function () {
        Route::middleware('web')->group(base_path('routes/auth.php'));
        Route::middleware('web')->group(base_path('routes/departments.php'));
        Route::middleware('web')->group(base_path('routes/employees.php'));
        Route::middleware('web')->group(base_path('routes/payroll.php'));
    },
)
```

Each module file applies its own `Route::middleware('auth')->group(...)` (or `'guest'` for the auth file) wrapper internally, so the bootstrap only needs to attach the shared `web` middleware stack.

## Request lifecycle

1. **Routing.** A request URL is matched against routes from `web.php` and the four module files. Domain routes are wrapped in `auth` middleware so guests are bounced to `/login`. Auth routes (`login`, `register`) sit inside a `guest` group; logged-in users hitting them are redirected to `/dashboard`.
2. **Validation.** Form-binding controllers receive a sub-namespaced `FormRequest` (e.g. `App\Http\Requests\Department\StoreDepartmentRequest`) which authorises the user and validates input. Failed validation flashes errors and redirects back with old input.
3. **Action.** Controllers delegate state changes to Eloquent (`$model->update(...)`) or, for payroll, to `App\Services\Payroll\PayrollCalculator`.
4. **Persistence.** Eloquent writes through to MySQL. The `payroll_records` table has a unique composite index on `(employee_id, month, year)` so the database itself cannot be tricked into accepting a duplicate.
5. **Response.** Controllers redirect with a flash `status` (success) or `error` (blocked operation), or render a Blade view. Layouts pull Tailwind/JS via Vite and Font Awesome / SweetAlert2 from CDNs.

## Authentication

Authentication is hand-rolled rather than scaffolded with Breeze:

- `App\Http\Controllers\Auth\LoginController` exposes `create`, `store`, `destroy`. `store` validates via `LoginRequest`, calls `Auth::attempt`, regenerates the session on success, and throws a `ValidationException` keyed to `email` on failure (so the form re-renders with the right error).
- `App\Http\Controllers\Auth\RegisterController` exposes `create`, `store`. The `User` model has the `password` cast set to `hashed`, so the controller passes the plain string and Laravel hashes once on write.
- All other routes are wrapped in `auth` middleware and `Route::redirectTo` defaults to `/login`.

## Payroll service

`App\Services\Payroll\PayrollCalculator` is a pure, framework-light service. It exposes:

- `calculate(Employee $employee): PayrollBreakdown` — convenience for production calls.
- `calculateFrom(float $basicSalary, float $allowance, int $overtimeHours, float $hourlyRate): PayrollBreakdown` — pure variant used by unit tests; takes raw scalars so the formula can be tested without touching the database.

Both routes return the same readonly `PayrollBreakdown` DTO with all seven monetary values rounded to 2 decimal places. `PayrollController@store` injects the calculator, iterates employees with `chunkById(200, ...)` inside a `DB::transaction`, skips employees that already have a record for the period, and persists the breakdown via `PayrollRecord::create([...$breakdown->toPersistableArray()])`.

See [payroll-formula.md](payroll-formula.md) for the formula and worked examples.

## Frontend

- **Tailwind CSS v4** is loaded via the Vite plugin (`@tailwindcss/vite`); base config lives in `resources/css/app.css`.
- **Layouts.** `resources/views/layouts/guest.blade.php` is a centred shell for login/register. `resources/views/layouts/app.blade.php` is the authenticated shell with a horizontal nav, flash banners, SweetAlert2 confirmation handler, and Font Awesome.
- **Forms.** Each domain has a shared `_form.blade.php` partial included by `create.blade.php` and `edit.blade.php` to avoid drift.
- **Confirmations.** Forms that need confirmation declare `data-confirm="delete"` (or `"run"`) plus `data-confirm-title` / `data-confirm-text` / `data-confirm-button`. A small JS handler in the layout intercepts the submit, opens a SweetAlert2 dialog, and re-submits if the user confirms.
- **Icons.** Action columns use Font Awesome 6 (CDN) with `aria-label` + `sr-only` text so screen readers still get the action name.

## Validation strategy

Validation lives in `FormRequest` classes — never inline in controllers. This keeps controllers thin and makes the rules testable in isolation. Authorisation is handled by the same form requests via `authorize()`; for now they just confirm the user is authenticated, but the hook is there for future per-resource policies.

## Data integrity

- `employees.department_id` and `payroll_records.employee_id` use `restrictOnDelete()` foreign keys. The DB rejects child orphans even if the controller-level check is somehow skipped.
- `payroll_records` carries a unique composite index on `(employee_id, month, year)`. If a future change ever bypasses the in-memory dedup loop, the DB still rejects the duplicate.
- All decimal columns are `decimal(10, 2)` and cast as `decimal:2` on the Eloquent model.
