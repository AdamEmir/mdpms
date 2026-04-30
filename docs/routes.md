# Routes & Livewire Components

All HTTP-rendered pages are **Livewire 4 class-based components**. There are no controllers for page rendering — the only remaining controller action is `Payroll\PayslipController@pdf`, which streams a dompdf-rendered file. Routes are split across module files, each loaded from `bootstrap/app.php` via `withRouting(then: …)`.

| File | Domain | Middleware (per group) |
|------|--------|------------------------|
| `routes/web.php` | `/` redirect, `/dashboard` | `web` + `auth` (dashboard) |
| `routes/auth.php` | login, register | `web` + `guest` |
| `routes/departments.php` | departments page | `web` + `auth` |
| `routes/employees.php` | employees page | `web` + `auth` |
| `routes/payroll.php` | payroll run / history / payslip / pdf | `web` + `auth` |

## Full route table

| Method | URI                                   | Name                  | Action |
|--------|---------------------------------------|-----------------------|--------|
| GET    | `/`                                   | —                     | redirect to `dashboard` |
| GET    | `/dashboard`                          | `dashboard`           | `App\Livewire\Dashboard` |
| GET    | `/login`                              | `login`               | `App\Livewire\Auth\Login` |
| GET    | `/register`                           | `register`            | `App\Livewire\Auth\Register` |
| GET    | `/departments`                        | `departments.index`   | `App\Livewire\Departments\Index` (modal CRUD) |
| GET    | `/employees`                          | `employees.index`     | `App\Livewire\Employees\Index` (modal CRUD; URL-bound `search` + `department_id` filters) |
| GET    | `/payroll/run`                        | `payroll.run`         | `App\Livewire\Payroll\Run` |
| GET    | `/payroll/history`                    | `payroll.history`     | `App\Livewire\Payroll\History` (URL-bound `search` / `month` / `year` / `department_id` filters) |
| GET    | `/payroll/{record}/payslip`           | `payroll.payslip`     | `App\Livewire\Payroll\Payslip` |
| GET    | `/payroll/{record}/payslip.pdf`       | `payroll.payslip.pdf` | `App\Http\Controllers\Payroll\PayslipController@pdf` |

That is the **entire** route surface — 10 entries. There are no POST/PUT/DELETE routes anywhere in the app: every state change (login, register, CRUD save, delete, payroll run, logout) is dispatched through Livewire over its own internal endpoint, not through a named app route.

Run `php artisan route:list --except-vendor` for the live, authoritative list.

## Logout

Logout is **not** a route. The `<livewire:auth.logout-button />` component is embedded directly in `resources/views/layouts/app.blade.php` and exposes a `logout()` action that calls `Auth::logout()`, invalidates the session, regenerates the token, and Livewire-redirects to `/login`. The button uses `wire:navigate.hover="false"` and the redirect is a full page load (`navigate: false`) so Livewire state is fully cleared.

## Component responsibilities

### `App\Livewire\Dashboard`
Renders three counts: total departments, total employees, and payroll records for the current month.

### `App\Livewire\Auth\Login`
Email/password fields validated via `#[Validate]` attributes. Calls `Auth::attempt`, regenerates session on success, redirects to intended URL or `/dashboard`. Adds a validation error to `email` on failure so the same form re-renders with the right message.

### `App\Livewire\Auth\Register`
Validates `name`, `email` (unique), `password` (confirmed, `Password::min(8)`) via `rules()`. Creates the user (the `password` cast is `hashed`), `Auth::login`, redirects to dashboard.

### `App\Livewire\Auth\LogoutButton`
Embedded in the layout. Single `logout()` action; not routed.

### `App\Livewire\Departments\Index`
Modal CRUD. Owns `bool $showForm` + `?int $editingId` state. Actions: `openCreate`, `openEdit`, `save`, `delete`. Paginated 10/page with `withCount('employees')`. `delete` checks `$department->employees()->exists()` and short-circuits with a flash error if so.

### `App\Livewire\Employees\Index`
Same modal CRUD pattern as Departments (`openCreate` / `openEdit` / `save` / `delete`). Has URL-bound `search` and `department_id` properties (`#[Url]`). Eager-loads `department`. `delete` checks `$employee->payrollRecords()->exists()`.

### `App\Livewire\Payroll\Run`
`month` / `year` selects with current month/year defaults. `run()` action validates `between:1,12` / `between:2000,2100`, then iterates `Employee::query()->chunkById(200, ...)` inside a `DB::transaction`, skipping employees that already have a record for the period, and persists via `PayrollRecord::create([...$breakdown->toPersistableArray()])`. See [payroll-formula.md](payroll-formula.md).

### `App\Livewire\Payroll\History`
Paginated history with URL-bound `search` / `month` / `year` / `department_id` filters. Department uses `whereHas`; search matches employee name. Eager-loads `employee.department`.

### `App\Livewire\Payroll\Payslip`
Loads the `PayrollRecord` (with `employee.department`) and renders `resources/views/payroll/_payslip-card.blade.php` inside its component view. Provides a "Download PDF" link to `route('payroll.payslip.pdf', $record)`.

### `App\Http\Controllers\Payroll\PayslipController@pdf`
The only remaining controller action. Renders `payroll/payslip-pdf.blade.php` through `barryvdh/laravel-dompdf` and streams `payslip-{slug}-{year}-{month}.pdf`.

## Validation

There are no FormRequest classes — they were all removed. Validation lives inside each Livewire component as either:

- A `protected function rules(): array` method (Departments, Employees, Register, Payroll\Run), or
- `#[Validate('rule')]` attributes on the component property (Login).

Authorisation is handled by route-level `auth` middleware. Component-level checks (`findOrFail`, ownership / dependency guards in `delete`) provide defence in depth.
