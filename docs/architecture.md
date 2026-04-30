# Architecture

## Overview

MDPMS is a server-rendered Laravel 12 + **Livewire 4** application. Requests come in over HTTP, the `web` middleware stack handles session + CSRF, route files dispatch to Livewire class-based components (and one residual `pdf` controller action), components mutate state via Eloquent and a payroll service, and Blade views render inside a shared layout slot.

```
Browser ──HTTP──▶ Laravel router (web middleware) ──▶ Livewire component
                                                            │
                                          (mount/action) ──▶ Eloquent / PayrollCalculator ──▶ SQLite
                                                            │
                                                            └──▶ Blade view (component) ──▶ {{ $slot }} in layouts/app.blade.php ──HTML──▶ Browser
```

After the initial render Livewire takes over: subsequent interactions (`wire:click`, `wire:submit`, `wire:model`, modal open/close, pagination, filter changes) round-trip over Livewire's own endpoint and patch the DOM in place. Top-level navigation between pages uses `wire:navigate` for SPA-style transitions without full reloads. There is no SPA framework, no JSON API, and no queue worker on the hot path. Payroll runs synchronously inside a DB transaction.

## Directory layout

The project follows the **flat Laravel 12 layout, sub-namespaced by domain** pattern, with HTTP-facing logic concentrated in `app/Livewire` rather than `app/Http/Controllers`.

```
app/
├── Http/
│   └── Controllers/
│       ├── Controller.php             ← base controller
│       └── Payroll/
│           └── PayslipController.php  ← only @pdf remains; streams dompdf output
├── Livewire/
│   ├── Dashboard.php
│   ├── Auth/
│   │   ├── Login.php
│   │   ├── Register.php
│   │   └── LogoutButton.php           ← embedded in layout, not routed
│   ├── Departments/
│   │   └── Index.php                  ← modal CRUD
│   ├── Employees/
│   │   └── Index.php                  ← modal CRUD + URL-bound filters
│   └── Payroll/
│       ├── Run.php
│       ├── History.php                ← URL-bound filters
│       └── Payslip.php
├── Models/
│   ├── User.php
│   ├── Department.php
│   ├── Employee.php
│   └── PayrollRecord.php
└── Services/
    └── Payroll/
        ├── PayrollCalculator.php       ← pure formula service (unchanged)
        └── PayrollBreakdown.php        ← readonly DTO of all 7 numbers (unchanged)

resources/views/
├── layouts/
│   ├── app.blade.php                   ← authenticated shell; renders {{ $slot }}; <title>{{ $title ?? 'MDPMS' }}</title>; embeds <livewire:auth.logout-button />
│   └── guest.blade.php                 ← centred shell for login/register
├── livewire/
│   ├── dashboard.blade.php
│   ├── auth/{login,register,logout-button}.blade.php
│   ├── departments/index.blade.php     ← table + inline modal markup
│   ├── employees/index.blade.php       ← table + inline modal markup
│   └── payroll/{run,history,payslip}.blade.php
├── partials/
│   └── flash-messages.blade.php        ← @included at the top of every component view
└── payroll/
    ├── _payslip-card.blade.php         ← shared payslip card (used by Payslip component view)
    └── payslip-pdf.blade.php           ← print-friendly variant for dompdf

routes/
├── web.php             ← / and /dashboard only
├── auth.php            ← login, register (guest); logout is a Livewire action, not a route
├── departments.php     ← 1 route (Departments\Index)
├── employees.php       ← 1 route (Employees\Index)
└── payroll.php         ← 4 routes (run / history / payslip / payslip.pdf)

bootstrap/app.php       ← registers the per-module route files
```

There are no longer any directories under `app/Http/Requests/` or `app/Http/Controllers/{Auth,Department,Employee,Dashboard}` — they were removed during the Livewire migration.

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

Each module file applies its own `Route::middleware('auth')->group(...)` (or `'guest'` for the auth file) wrapper internally, so the bootstrap only attaches the shared `web` stack.

## Request lifecycle

1. **Routing.** A request URL is matched against `web.php` and the four module files. Domain routes are wrapped in `auth` middleware so guests bounce to `/login`. Auth routes (`login`, `register`) sit inside a `guest` group; logged-in users hitting them are redirected to `/dashboard`.
2. **Component mount.** The matched route resolves to a Livewire class component. `mount()` (where defined) hydrates initial state — including any `#[Url]`-bound properties read from the query string (e.g. employee `search` / `department_id`, history filters).
3. **Validation.** Form-bound components validate via either a `protected function rules(): array` method (Departments, Employees, Register, Payroll\Run) or `#[Validate]` attributes on properties (Login). On failure, errors live in Livewire's `$errors` bag and the same component re-renders in place — there is no redirect-back-with-old-input cycle.
4. **Action.** Component action methods (`save`, `delete`, `run`, `login`, `register`, `logout`) delegate state changes to Eloquent (`$model->update(...)`) or, for payroll, to `App\Services\Payroll\PayrollCalculator`. CRUD components manage modal visibility through `bool $showForm` + `?int $editingId` properties.
5. **Persistence.** Eloquent writes through to **SQLite** at `database/database.sqlite`. The `payroll_records` table has a unique composite index on `(employee_id, month, year)`, so the database itself rejects duplicates.
6. **Response.** The component's Blade view renders inside `{{ $slot }}` of `resources/views/layouts/app.blade.php` (or `layouts/guest.blade.php` for auth pages). The layout's `<title>` reads from the component's `#[Title]` attribute. Each component view starts with `@include('partials.flash-messages')`. After actions, components either re-render in place, emit a flash message, or `redirect()` (e.g. payroll run → history).

## Authentication

Authentication is hand-rolled rather than scaffolded with Breeze, but lives entirely in Livewire:

- `App\Livewire\Auth\Login` — `email` / `password` fields with `#[Validate]` attributes. The `login()` action calls `Auth::attempt`, regenerates the session, and redirects to the intended URL or `/dashboard`. On failure it adds a validation error to `email`.
- `App\Livewire\Auth\Register` — validates `name`, `email` (unique), `password` (confirmed, `Password::min(8)`) via `rules()`. The `User` model has the `password` cast set to `hashed`, so the component passes the plain string and Laravel hashes once on write. After create, `Auth::login` and redirect.
- `App\Livewire\Auth\LogoutButton` — embedded in the layout via `<livewire:auth.logout-button />`. Its `logout()` action calls `Auth::logout`, invalidates the session, regenerates the token, and Livewire-redirects to `/login` with `navigate: false` so the page does a full reload and Livewire state is fully discarded.
- All other routes are wrapped in `auth` middleware and `Route::redirectTo` defaults to `/login`.

## Payroll service

`App\Services\Payroll\PayrollCalculator` is unchanged by the Livewire migration. It is a pure, framework-light service exposing:

- `calculate(Employee $employee): PayrollBreakdown` — convenience for production calls.
- `calculateFrom(float $basicSalary, float $allowance, int $overtimeHours, float $hourlyRate): PayrollBreakdown` — pure variant used by unit tests; takes raw scalars so the formula can be tested without touching the database.

Both routes return the same readonly `PayrollBreakdown` DTO with all seven monetary values rounded to 2 decimal places. `App\Livewire\Payroll\Run::run()` injects the calculator, iterates employees with `chunkById(200, ...)` inside a `DB::transaction`, skips employees that already have a record for the period, and persists the breakdown via `PayrollRecord::create([...$breakdown->toPersistableArray()])`.

See [payroll-formula.md](payroll-formula.md) for the formula and worked examples.

## Frontend

- **Tailwind CSS v4** is loaded via the Vite plugin (`@tailwindcss/vite`); base config lives in `resources/css/app.css`.
- **Layouts.** `layouts/guest.blade.php` is a centred shell for login/register. `layouts/app.blade.php` is the authenticated shell with a horizontal topbar, flash banners, and Font Awesome. It renders `{{ $slot }}` (no `@yield('content')`) and reads `<title>{{ $title ?? 'MDPMS' }}</title>` from each component's `#[Title]` attribute. The topbar puts `wire:navigate` on every internal link for SPA-style transitions, and embeds `<livewire:auth.logout-button />`.
- **Modal CRUD.** Departments and Employees Index components own modal state via `bool $showForm` + `?int $editingId` properties. The form markup is **inlined** inside each Index component view (not a shared `_form.blade.php` partial). Modals use `role="dialog"`, `aria-modal="true"`, `aria-labelledby`, Alpine `x-trap.noscroll` for focus trapping, and `wire:keydown.escape` to close.
- **Confirmations.** Delete buttons use Alpine `x-on:click` to open a SweetAlert2 dialog and, on confirm, call `$wire.delete(id)` to invoke the Livewire action. There is no native `confirm()` anywhere.
- **Icons.** Action columns use Font Awesome 6 (CDN) with `aria-label` + `sr-only` text so screen readers still get the action name.

## Validation strategy

Validation lives **inside each Livewire component**, never in a separate FormRequest class. Two flavours are used:

- `protected function rules(): array` for components with multiple fields and conditional rules (Departments, Employees, Register, Payroll\Run).
- `#[Validate('rule')]` attributes directly on properties for simpler cases (Login).

Failed validation re-renders the same component in place with errors in `$errors`; no redirect-back-with-input is needed because the component already holds the user's input in its public properties. Authorisation is handled by route-level `auth` middleware plus per-action ownership / dependency guards inside component methods.

## Data integrity

- `employees.department_id` and `payroll_records.employee_id` use `restrictOnDelete()` foreign keys. The DB rejects child orphans even if the component-level check is somehow skipped.
- `payroll_records` carries a unique composite index on `(employee_id, month, year)`. If a future change ever bypasses the in-memory dedup loop, the DB still rejects the duplicate.
- All decimal columns are `decimal(10, 2)` and cast as `decimal:2` on the Eloquent model.

## Testing

Pest 3 feature tests mirror the component tree under `tests/Feature/Livewire/{Auth,Departments,Employees,Payroll}/`. Unit tests live under `tests/Unit/Services/Payroll/`. Tests use Livewire's `Livewire::test(Component::class)` helper to drive components directly — `set()`, `call('action')`, `assertSee()`, `assertRedirect()`, etc. — with no HTTP layer involved.
