# Testing

The project uses **Pest 3** with `pest-plugin-laravel`. Tests run against **SQLite in-memory** (configured in `phpunit.xml`) for speed and isolation, while the dev environment uses MySQL.

| Metric | Value |
|--------|-------|
| Total tests | 30 |
| Total assertions | 88 |
| Duration | ~0.6s on a recent Mac |

## Running

| Command | Result |
|---------|--------|
| `composer test` | Clears config, runs the full suite. |
| `php artisan test --compact` | Same, but compact reporter. |
| `php artisan test --compact --filter=PayrollCalculator` | Run a single file by name fragment. |
| `php artisan test --testsuite=Unit` | Unit suite only. |
| `php artisan test --testsuite=Feature` | Feature suite only. |

`tests/Pest.php` extends `Tests\TestCase` and enables `RefreshDatabase` for everything in `Feature/`. Unit tests do not touch the DB.

## Suite overview

```
tests/
├── Unit/
│   └── Services/Payroll/
│       └── PayrollCalculatorTest.php       ← 9 tests, formula coverage
├── Feature/
│   ├── Auth/AuthFlowTest.php               ← 5 tests: register, login, logout, guard
│   ├── DepartmentCrudTest.php              ← 6 tests: list, create, dup-name, update, delete, blocked-delete
│   ├── EmployeeCrudTest.php                ← 5 tests: list+filter, create, update, delete, blocked-delete
│   └── PayrollProcessingTest.php           ← 4 tests: spec example, dedup, validation, payslip view
└── Pest.php
```

## What each test guarantees

### `PayrollCalculatorTest` (Unit)

The 25%-weighted piece. Covers:

- **Spec example.** 4000 / 600 / 10 / 25 → net 3 928.50 — exact match to the assignment document.
- **Zero overtime.** `overtime_pay` is 0; `gross_pay` is `basic + allowance`.
- **Zero allowance.** `gross_pay` is `basic + overtime_pay`; net pay matches the manual computation.
- **Decimal rounding.** Inputs like 3333.33 / 12.34 round at every step exactly as the formula prescribes.
- **Gross invariant** (data-driven). For four parameter sets, `grossPay == round(basic + allowance + (hours * rate), 2)`.
- **Eloquent integration.** Building an `Employee` from an array and calling `calculate($employee)` returns the same 3 928.50 net pay — verifies the model→service path.

### `Auth/AuthFlowTest` (Feature)

- Guests are redirected from `/dashboard` to `/login`.
- `POST /register` creates the user, signs them in, redirects to `/dashboard`.
- `POST /login` with valid credentials authenticates and redirects.
- Invalid credentials yield `ValidationException` with `email` error and the user remains a guest.
- `POST /logout` (while authed) signs the user out and redirects to `/login`.

### `DepartmentCrudTest` (Feature)

- Index renders the page with the right text.
- Store creates a department.
- Duplicate names are rejected by `StoreDepartmentRequest`.
- Update changes the name.
- Empty department can be deleted.
- Department with employees **cannot** be deleted; flash `error` set; record persists.

### `EmployeeCrudTest` (Feature)

- Index lists employees and `?department_id=` narrows the result set.
- Store creates an employee.
- Update changes a field.
- Employee with no payroll records can be deleted.
- Employee **with** payroll records cannot be deleted; flash `error` set; record persists.

### `PayrollProcessingTest` (Feature)

- Running payroll for a freshly created employee yields the exact spec numbers in the persisted row.
- Re-running for the same period skips employees that already have a record (asserts only one row exists).
- Invalid `month` (13) and `year` (1999) trigger validation errors.
- The payslip view renders with the employee name and a "Payslip" heading.

## Browser verification (live, manual)

Per project preference, UI changes are smoke-tested through the **Playwright MCP browser tools** at `http://mdpms.test`. The full happy path covered during the build:

- Register → dashboard.
- Logout → guarded routes redirect to login.
- Create dept → create employee → try delete dept → blocked friendly error.
- Run payroll for current month → 11/58 records appear; re-run → "0 new, X skipped".
- Open payslip → numbers match the spec; download PDF; verify content.
- Filter history by month/year/dept; pagination links work.
- `browser_console_messages` returns zero errors (only a benign favicon 404).

## Adding new tests

1. `php artisan make:test --pest [--unit] Path/Of/TestName`
2. Mirror the source structure (`app/Foo/Bar.php` ↔ `tests/Unit/Foo/BarTest.php` or `tests/Feature/Foo/BarTest.php`).
3. Use factories — they live under `database/factories/` and are wired into the models (`HasFactory`).
4. Use `actingAs(User::factory()->create())` for authenticated requests; the test `RefreshDatabase` trait ensures a clean schema per test.
