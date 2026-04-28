# MDPMS — Multi-Department Payroll Management System

A Laravel 12 implementation of the intermediate-level payroll assessment. Authenticated users manage departments and employees, then run a monthly payroll for all employees with a fixed multi-component salary formula. Payslips can be viewed in-app or exported to PDF.

---

## Highlights

- **Hand-rolled Blade authentication** (register / login / logout) with `auth` middleware guarding every app route.
- **Department + Employee CRUD** with relational delete-protection (cannot delete a department with employees, cannot delete an employee with payroll history). Employee list supports name search and department filter.
- **Monthly payroll processing** that runs the fixed formula for every employee in a single transaction and skips duplicates via a unique `(employee_id, month, year)` index.
- **Payroll history** with month / year / department filters, employee-name search, and a payslip detail view.
- **PDF payslip export** via `barryvdh/laravel-dompdf`.
- **Pest tests** — 9 unit tests on the formula plus 21 feature tests covering auth, CRUD, and payroll flows (30 tests / 88 assertions).
- **Pagination** on every list view.
- **Module-organised routes** (`routes/{auth,departments,employees,payroll}.php`) registered in `bootstrap/app.php`.

## Stack

| Layer | Tool |
|------|------|
| Language | PHP 8.4 |
| Framework | Laravel 12 |
| Database (dev) | MySQL 8 (via Laravel Herd) |
| Database (tests) | SQLite in-memory |
| Frontend | Tailwind CSS v4 + Vite 7 |
| Icons | Font Awesome 6 (CDN) |
| Confirms | SweetAlert2 (CDN) |
| PDF | `barryvdh/laravel-dompdf` |
| Tests | Pest 3 (`pest-plugin-laravel`) |

## Quick start

```bash
git clone <repo-url> mdpms && cd mdpms
composer install
cp .env.example .env
# edit DB_DATABASE / DB_USERNAME / DB_PASSWORD to match your local MySQL
php artisan key:generate
php artisan migrate --seed
npm install
npm run build
```

The site is served by Laravel Herd at <http://mdpms.test>. To run server, queue listener, log tail, and Vite together: `composer dev`.

### Default seeded login

| Email                | Password   |
|----------------------|------------|
| `admin@mdpms.test`   | `password` |

The seeder also creates 10 departments and ~58 employees, including the spec example employee `Siti Aminah` (basic 4000, allowance 600, 10 OT hours @ RM 25) so the formula can be verified end-to-end against the assignment document.

## Common commands

| Action                | Command                                                   |
|-----------------------|-----------------------------------------------------------|
| Dev (server + Vite)   | `composer dev`                                            |
| All tests             | `composer test`                                           |
| Single test           | `php artisan test --compact --filter=PayrollCalculator`   |
| Code style            | `vendor/bin/pint --dirty --format agent`                  |
| Route list            | `php artisan route:list --except-vendor`                  |
| DB reset              | `php artisan migrate:fresh --seed`                        |

## Documentation

| Topic | File |
|-------|------|
| Architecture & directory layout | [docs/architecture.md](docs/architecture.md) |
| Database schema & relationships | [docs/database-schema.md](docs/database-schema.md) |
| Payroll formula reference | [docs/payroll-formula.md](docs/payroll-formula.md) |
| Routes & controllers | [docs/routes.md](docs/routes.md) |
| Testing strategy | [docs/testing.md](docs/testing.md) |
| Development workflow | [docs/development.md](docs/development.md) |
| Assessment requirement matrix | [docs/assessment-mapping.md](docs/assessment-mapping.md) |

## Bonus items implemented

- Pest unit tests on the payroll formula (9 cases, including the spec example)
- Pagination on department, employee, and payroll history lists
- PDF payslip export (`barryvdh/laravel-dompdf`)

## Assumptions

- Reviewers run the project on macOS via Laravel Herd. The `.env` defaults match Herd's local MySQL credentials (`root` / vendor-supplied password).
- Overtime hours are capped at 744 (max hours in a 31-day month).
- "EPF Employer" is stored on every payroll record but is informational only — it does not affect the employee's net pay, per the assessment specification.
- The seeded `admin@mdpms.test` account is intended for review; rotate before any non-local use.

## License

Built for assessment purposes; no public license attached.
