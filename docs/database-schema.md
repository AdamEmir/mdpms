# Database Schema

MySQL 8 in dev, SQLite in-memory in tests. All tables are migrated by Laravel migrations under `database/migrations/`.

## Entity-relationship diagram

```
┌──────────────────────┐
│        users         │  (Laravel default — registers application accounts)
├──────────────────────┤
│ id          BIGINT   │
│ name        VARCHAR  │
│ email       VARCHAR  │  (unique)
│ password    VARCHAR  │  (hashed)
│ created_at  TIMESTAMP│
│ updated_at  TIMESTAMP│
└──────────────────────┘

┌──────────────────────┐         ┌──────────────────────────────┐
│    departments       │ 1     N │           employees          │
├──────────────────────┤────────▶├──────────────────────────────┤
│ id          BIGINT   │         │ id              BIGINT       │
│ name        VARCHAR  │ (uniq)  │ department_id   FK ──────────┼──── ON DELETE RESTRICT
│ created_at  TIMESTAMP│         │ name            VARCHAR      │
│ updated_at  TIMESTAMP│         │ position        VARCHAR      │
└──────────────────────┘         │ basic_salary    DECIMAL(10,2)│
                                 │ allowance       DECIMAL(10,2)│  (default 0)
                                 │ overtime_hours  UNSIGNED INT │  (default 0)
                                 │ hourly_rate     DECIMAL(10,2)│  (default 0)
                                 │ created_at      TIMESTAMP    │
                                 │ updated_at      TIMESTAMP    │
                                 └──────────────────────────────┘
                                                  │ 1
                                                  │
                                                  ▼ N
                                 ┌──────────────────────────────────┐
                                 │         payroll_records          │
                                 ├──────────────────────────────────┤
                                 │ id              BIGINT           │
                                 │ employee_id     FK ──────────────┼─ ON DELETE RESTRICT
                                 │ month           UNSIGNED TINYINT │
                                 │ year            UNSIGNED SMALLINT│
                                 │ gross_pay       DECIMAL(10,2)    │
                                 │ overtime_pay    DECIMAL(10,2)    │
                                 │ tax             DECIMAL(10,2)    │
                                 │ epf_employee    DECIMAL(10,2)    │
                                 │ epf_employer    DECIMAL(10,2)    │
                                 │ net_pay         DECIMAL(10,2)    │
                                 │ created_at      TIMESTAMP        │
                                 │ updated_at      TIMESTAMP        │
                                 │                                  │
                                 │ UNIQUE (employee_id, month, year)│
                                 └──────────────────────────────────┘
```

## Constraints

| Constraint | Rationale |
|------------|-----------|
| `departments.name` UNIQUE | Form requests already validate uniqueness, but the DB enforces it as the source of truth. |
| `employees.department_id` FK ON DELETE RESTRICT | Mirrors the controller-level "cannot delete a department with employees" rule. |
| `payroll_records.employee_id` FK ON DELETE RESTRICT | Mirrors "cannot delete an employee with payroll records". |
| UNIQUE `(employee_id, month, year)` on `payroll_records` | Protects against duplicate payroll runs even if the application-level dedup loop is bypassed. |

## Eloquent relationships

| Model | Relationship | Defined in |
|-------|-------------|-----------|
| `Department` | `hasMany(Employee::class)` | `app/Models/Department.php::employees()` |
| `Employee` | `belongsTo(Department::class)` | `app/Models/Employee.php::department()` |
| `Employee` | `hasMany(PayrollRecord::class)` | `app/Models/Employee.php::payrollRecords()` |
| `PayrollRecord` | `belongsTo(Employee::class)` | `app/Models/PayrollRecord.php::employee()` |

## Casts

| Model | Field(s) | Cast |
|-------|---------|------|
| `Employee` | `basic_salary`, `allowance`, `hourly_rate` | `decimal:2` |
| `Employee` | `overtime_hours` | `integer` |
| `PayrollRecord` | all monetary fields | `decimal:2` |
| `PayrollRecord` | `month`, `year` | `integer` |
| `User` | `password` | `hashed` (Laravel 11+ default) |

## Seeders

`database/seeders/DatabaseSeeder.php`:

1. Creates the seeded admin user (`admin@mdpms.test` / `password`).
2. Creates 10 named departments (Engineering, Finance, Human Resources, Sales, Marketing, Operations, Product, Customer Support, Legal, Research).
3. Creates the spec example employee (`Siti Aminah` in Engineering with the assignment's exact numbers) so the formula can be verified against the assessment document.
4. For each department, creates 4–7 random employees via `EmployeeFactory`, ending up with ~58 employees total.

`PayrollRecordFactory` is provided for tests but is not invoked from the seeder — payroll records are created through the actual `PayrollController` flow when the user runs payroll from the UI.
