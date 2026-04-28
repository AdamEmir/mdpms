# Assessment Requirements Mapping

This document maps every line item from the original `intermediate_assessment.md` to where it is implemented in the codebase, so reviewers can audit coverage at a glance.

## 1. Authentication

| Requirement | Implementation |
|-------------|----------------|
| Register with name, email, password | `App\Http\Controllers\Auth\RegisterController` + `Auth\RegisterRequest` + `resources/views/auth/register.blade.php` |
| Login with email + password | `App\Http\Controllers\Auth\LoginController@store` + `Auth\LoginRequest` |
| Logout (clear session, redirect to login) | `LoginController@destroy` (`Auth::logout`, session invalidation, token regeneration) |
| Guard all pages except login | `routes/web.php` + per-module route files all use `Route::middleware('auth')->group(...)`; auth routes use `guest` |

Tests: `tests/Feature/Auth/AuthFlowTest.php` (5 tests).

## 2. Department CRUD

| Requirement | Implementation                                                                        |
|-------------|---------------------------------------------------------------------------------------|
| List all departments | `DepartmentController@index`, paginated 10/page, with `withCount('employees')`        |
| Create / Edit / Delete | `create`, `store`, `edit`, `update`, `destroy` actions                                |
| Cannot delete with employees | `destroy` returns redirect with flash `error` if `$department->employees()->exists()` |

Tests: `tests/Feature/DepartmentCrudTest.php` (6 tests).

## 3. Employee CRUD

| Requirement | Implementation |
|-------------|----------------|
| List all employees, show department name | `EmployeeController@index` eager-loads `department` |
| Filter by department | `?department_id=` query string handled in `index`; combinable with `?search=` for name |
| Create / Edit / Delete | All wired |
| Cannot delete with payroll records | `destroy` checks `$employee->payrollRecords()->exists()` |

Tests: `tests/Feature/EmployeeCrudTest.php` (5 tests).

## 4. Payroll processing

| Requirement | Implementation |
|-------------|----------------|
| Select Month + Year | `payroll/run.blade.php`, validated by `RunPayrollRequest` (`between:1,12`, `between:2000,2100`) |
| Run payroll for ALL employees | `PayrollController@store` chunks `Employee::query()` 200 at a time |
| Prevent duplicate per `(employee, month, year)` | App-level skip check + DB unique composite index on `payroll_records` |
| Save to `payroll_records` | `PayrollRecord::create([...$breakdown->toPersistableArray()])` inside `DB::transaction` |

Tests: `tests/Feature/PayrollProcessingTest.php` (4 tests) + `tests/Unit/Services/Payroll/PayrollCalculatorTest.php` (9 tests).

## 5. Payroll history

| Requirement | Implementation |
|-------------|----------------|
| List all payroll records | `PayrollHistoryController@index`, paginated 10/page |
| Filter by month / year / department | All three filters wired; department uses `whereHas`. Bonus: `?search=` for employee name |
| View individual payslip | `PayslipController@show` renders `payroll/payslip.blade.php` (matches the assessment's mockup) |

## Payroll formula correctness (25% weight)

The formula is implemented exactly per spec in `app/Services/Payroll/PayrollCalculator.php`. See [payroll-formula.md](payroll-formula.md) for the worked example and the constants used.

The example `4000 / 600 / 10 / 25 → net 3 928.50` is asserted in `PayrollCalculatorTest::it('matches the assessment spec example exactly')` and is also seeded into the database as employee `Siti Aminah` so it can be visually verified in the running app.

## Bonus items

| Bonus item | Status | Where |
|------------|--------|-------|
| Dockerize | ✗ not implemented | — |
| Unit tests on payroll formula | ✓ | `tests/Unit/Services/Payroll/PayrollCalculatorTest.php` (9 tests) |
| Export payslip as PDF or CSV | ✓ (PDF) | `PayslipController@pdf` + `barryvdh/laravel-dompdf` + `payroll/payslip-pdf.blade.php` |
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
| Auth (login/register/guard) works | 20% | Hand-rolled, tested, guard verified live |
| CRUD (dept + employee) works correctly | 20% | Both CRUD flows implemented + tested + delete-protection |
| Payroll formula is correct | 25% | Implemented per spec; 9 unit tests; spec example verified live |
| Relational data handled properly | 15% | FK with `restrictOnDelete`, eager-loading, `whereHas` filtering |
| Code structure and readability | 15% | Sub-namespaced flat layout, module routes, form requests, DTO, service class, Pint-clean |
| Bonus | 5% | Pest unit tests + pagination + PDF export (3 of 4 bonus items) |
