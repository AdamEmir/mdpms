# Routes & Controllers

All HTTP verbs are declared **explicitly** — `Route::resource` is intentionally not used. Routes are split across module files, each loaded from `bootstrap/app.php` via `withRouting(then: …)`.

| File | Domain | Middleware (per group) |
|------|--------|------------------------|
| `routes/web.php` | `/` redirect, `/dashboard` | `web` + `auth` (dashboard) |
| `routes/auth.php` | login, register, logout | `web` + `guest` (forms), `web` + `auth` (logout) |
| `routes/departments.php` | departments CRUD | `web` + `auth` |
| `routes/employees.php` | employees CRUD | `web` + `auth` |
| `routes/payroll.php` | payroll run / history / payslip | `web` + `auth` |

## Full route table

| Method | URI                                   | Name                  | Action |
|--------|---------------------------------------|-----------------------|--------|
| GET    | `/`                                   | —                     | redirect to `dashboard` |
| GET    | `/dashboard`                          | `dashboard`           | `DashboardController@index` |
| GET    | `/login`                              | `login`               | `Auth\LoginController@create` |
| POST   | `/login`                              | `login.store`         | `Auth\LoginController@store` |
| GET    | `/register`                           | `register`            | `Auth\RegisterController@create` |
| POST   | `/register`                           | `register.store`      | `Auth\RegisterController@store` |
| POST   | `/logout`                             | `logout`              | `Auth\LoginController@destroy` |
| GET    | `/departments`                        | `departments.index`   | `Department\DepartmentController@index` |
| GET    | `/departments/create`                 | `departments.create`  | `Department\DepartmentController@create` |
| POST   | `/departments`                        | `departments.store`   | `Department\DepartmentController@store` |
| GET    | `/departments/{department}/edit`      | `departments.edit`    | `Department\DepartmentController@edit` |
| PUT    | `/departments/{department}`           | `departments.update`  | `Department\DepartmentController@update` |
| DELETE | `/departments/{department}`           | `departments.destroy` | `Department\DepartmentController@destroy` |
| GET    | `/employees`                          | `employees.index`     | `Employee\EmployeeController@index` |
| GET    | `/employees/create`                   | `employees.create`    | `Employee\EmployeeController@create` |
| POST   | `/employees`                          | `employees.store`     | `Employee\EmployeeController@store` |
| GET    | `/employees/{employee}/edit`          | `employees.edit`      | `Employee\EmployeeController@edit` |
| PUT    | `/employees/{employee}`               | `employees.update`    | `Employee\EmployeeController@update` |
| DELETE | `/employees/{employee}`               | `employees.destroy`   | `Employee\EmployeeController@destroy` |
| GET    | `/payroll/run`                        | `payroll.run`         | `Payroll\PayrollController@create` |
| POST   | `/payroll/run`                        | `payroll.process`     | `Payroll\PayrollController@store` |
| GET    | `/payroll/history`                    | `payroll.history`     | `Payroll\PayrollHistoryController@index` |
| GET    | `/payroll/{record}/payslip`           | `payroll.payslip`     | `Payroll\PayslipController@show` |
| GET    | `/payroll/{record}/payslip.pdf`       | `payroll.payslip.pdf` | `Payroll\PayslipController@pdf` |

Run `php artisan route:list --except-vendor` for the live, authoritative list.

## Controller responsibilities

### `DashboardController@index`
Renders `dashboard.blade.php` with three counts: total departments, total employees, and payroll records for the current month.

### `Auth\LoginController`
- `create` — render login form.
- `store` — validate via `LoginRequest`, attempt auth with `Auth::attempt`, regenerate session, redirect to intended URL or `/dashboard`. Throws `ValidationException::withMessages` on failure.
- `destroy` — log out, invalidate session, regenerate token, redirect to login.

### `Auth\RegisterController`
- `create` — render registration form.
- `store` — validate via `RegisterRequest`, create user (the `password` cast is `hashed`, so plain string is fine), `Auth::login`, redirect to dashboard.

### `Department\DepartmentController`
Standard CRUD. `destroy` checks `$department->employees()->exists()`; if so, redirects back with a flash error and the department survives. Otherwise deletes.

### `Employee\EmployeeController`
Standard CRUD plus `?department_id=` and `?search=` filters on `index` (search matches employee name with `LIKE %term%`; combines with the department filter). `destroy` checks `$employee->payrollRecords()->exists()` and behaves analogously to the department case.

### `Payroll\PayrollController`
- `create` — render `payroll/run.blade.php` with current month/year as defaults.
- `store` — see [payroll-formula.md](payroll-formula.md) for the algorithm. Persists rows via `PayrollRecord::create([...])` inside a transaction.

### `Payroll\PayrollHistoryController@index`
Paginated history with month / year / department filters and `?search=` for employee name (matched via `whereHas('employee', name LIKE %term%)`). Eager-loads `employee.department` to avoid N+1.

### `Payroll\PayslipController`
- `show` — render `payroll/payslip.blade.php` (which includes `_payslip-card.blade.php`).
- `pdf` — render `payroll/payslip-pdf.blade.php` through `barryvdh/laravel-dompdf` and stream the result as `payslip-{slug}-{year}-{month}.pdf`.

## Form requests

| Class | Validates |
|-------|-----------|
| `Auth\LoginRequest` | `email` (required, email), `password` (required, string) |
| `Auth\RegisterRequest` | `name`, `email` (unique), `password` (confirmed, `Password::min(8)`) |
| `Department\StoreDepartmentRequest` | `name` required, max 100, unique |
| `Department\UpdateDepartmentRequest` | `name` required, max 100, unique ignoring current id |
| `Employee\StoreEmployeeRequest` / `UpdateEmployeeRequest` | department_id (exists), name, position, basic_salary, allowance, overtime_hours (0–744), hourly_rate |
| `Payroll\RunPayrollRequest` | `month` between 1–12, `year` between 2000–2100 |

Each request also implements `authorize(): bool` returning `$this->user() !== null` so route-level `auth` middleware and request-level authorisation form a defence-in-depth pair.
