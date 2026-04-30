# Assessment Requirements Mapping

This document maps every line item from the original `intermediate_assessment.md` to where it is implemented in the codebase, so reviewers can audit coverage at a glance.

## 1. Authentication

| Requirement | Implementation |
|-------------|----------------|
| Register with name, email, password | `App\Livewire\Auth\Register` + `resources/views/livewire/auth/register.blade.php` |
| Login with email + password | `App\Livewire\Auth\Login` (email/password validated via `#[Validate]` attributes; calls `Auth::attempt`) |
| Logout (clear session, redirect to login) | `App\Livewire\Auth\LogoutButton` embedded in `resources/views/layouts/app.blade.php` (`Auth::logout`, session invalidation, token regeneration, full-reload redirect) |
| Guard all pages except login | `routes/web.php` + per-module route files all use `Route::middleware('auth')->group(...)`; auth routes use `guest` |

Tests: `tests/Feature/Livewire/Auth/LoginTest.php`, `tests/Feature/Livewire/Auth/RegisterTest.php`, `tests/Feature/Livewire/Auth/LogoutButtonTest.php`.

## 2. Department CRUD

| Requirement | Implementation                                                                        |
|-------------|---------------------------------------------------------------------------------------|
| List all departments | `App\Livewire\Departments\Index` paginated 10/page with `withCount('employees')`      |
| Create / Edit / Delete | Same component — modal CRUD actions `openCreate`, `openEdit`, `save`, `delete` |
| Cannot delete with employees | `delete()` short-circuits with a flash `error` if `$department->employees()->exists()` |

Tests: `tests/Feature/Livewire/Departments/IndexTest.php`.

## 3. Employee CRUD

| Requirement | Implementation |
|-------------|----------------|
| List all employees, show department name | `App\Livewire\Employees\Index` eager-loads `department` |
| Filter by department | URL-bound `#[Url] $department_id` property; combinable with URL-bound `#[Url] $search` for name |
| Create / Edit / Delete | Modal CRUD actions on the same component (`openCreate`, `openEdit`, `save`, `delete`) |
| Cannot delete with payroll records | `delete()` checks `$employee->payrollRecords()->exists()` |

Tests: `tests/Feature/Livewire/Employees/IndexTest.php`.

## 4. Payroll processing

| Requirement | Implementation |
|-------------|----------------|
| Select Month + Year | `App\Livewire\Payroll\Run` (view: `resources/views/livewire/payroll/run.blade.php`); `rules()` enforces `between:1,12` and `between:2000,2100` |
| Run payroll for ALL employees | `Run::run()` chunks `Employee::query()` 200 at a time inside a `DB::transaction` |
| Prevent duplicate per `(employee, month, year)` | App-level skip check in `Run::run()` + DB unique composite index on `payroll_records` |
| Save to `payroll_records` | `PayrollRecord::create([...$breakdown->toPersistableArray()])` inside the transaction |

Tests: `tests/Feature/Livewire/Payroll/RunTest.php` + `tests/Unit/Services/Payroll/PayrollCalculatorTest.php` (9 tests).

## 5. Payroll history

| Requirement | Implementation |
|-------------|----------------|
| List all payroll records | `App\Livewire\Payroll\History`, paginated 10/page |
| Filter by month / year / department | All three URL-bound (`#[Url]`); department uses `whereHas`. Bonus: URL-bound `search` for employee name |
| View individual payslip | `App\Livewire\Payroll\Payslip` renders `resources/views/payroll/_payslip-card.blade.php` (matches the assessment's mockup) |

Tests: `tests/Feature/Livewire/Payroll/HistoryTest.php`, `tests/Feature/Livewire/Payroll/PayslipTest.php`.

## Payroll formula correctness (25% weight)

The formula is implemented exactly per spec in `app/Services/Payroll/PayrollCalculator.php` (unchanged by the Livewire migration). See [payroll-formula.md](payroll-formula.md) for the worked example and the constants used.

The example `4000 / 600 / 10 / 25 → net 3 928.50` is asserted in `PayrollCalculatorTest::it('matches the assessment spec example exactly')` and is also seeded into the database as employee `Siti Aminah` so it can be visually verified in the running app.

## Bonus items

| Bonus item | Status | Where |
|------------|--------|-------|
| Dockerize | ✗ not implemented | — |
| Unit tests on payroll formula | ✓ | `tests/Unit/Services/Payroll/PayrollCalculatorTest.php` (9 tests) |
| Export payslip as PDF or CSV | ✓ (PDF) | `App\Http\Controllers\Payroll\PayslipController@pdf` + `barryvdh/laravel-dompdf` + `resources/views/payroll/payslip-pdf.blade.php` (the only remaining controller action in the app) |
| Pagination | ✓ | departments (10/page), employees (10/page), payroll history (10/page) |

## Submission guidelines

| Item | Status |
|------|--------|
| Public Git repo | TBD — not yet pushed |
| README with setup, migration, default login, assumptions | Yes — see `README.md` |
| Runnable locally | Yes — Herd serves at `http://mdpms.test` |
| Docs deeper than the README | Yes — `docs/` folder (this file is part of it) |

## Evaluation criteria coverage (publisher's rubric)

| Criterion | Weight | Coverage |
|-----------|--------|----------|
| Auth (login/register/guard) works | 20% | Hand-rolled in Livewire, tested, guard verified live |
| CRUD (dept + employee) works correctly | 20% | Both CRUD flows implemented as Livewire modal CRUD + tested + delete-protection |
| Payroll formula is correct | 25% | Implemented per spec; 9 unit tests; spec example verified live |
| Relational data handled properly | 15% | FK with `restrictOnDelete`, eager-loading, `whereHas` filtering |
| Code structure and readability | 15% | Sub-namespaced flat layout, module routes, Livewire components, DTO, service class, Pint-clean |
| Bonus | 5% | Pest unit tests + pagination + PDF export (3 of 4 bonus items) |
