# Development workflow

## Local environment

The project assumes Laravel Herd on macOS, which provides:

- PHP 8.4 (`herd php:list` to confirm the active version),
- MySQL 8 on `127.0.0.1:3306`,
- Automatic site serving at `http://<directory>.test` (so this project is at `http://mdpms.test`).

There is no need to run `php artisan serve`; Herd handles it.

## First-time setup

```bash
composer install
cp .env.example .env
php artisan key:generate
# verify DB_CONNECTION/DB_DATABASE/DB_USERNAME/DB_PASSWORD in .env
php artisan migrate --seed
npm install
npm run build
```

If the MySQL `mdpms` database does not yet exist, create it first:

```sql
CREATE DATABASE mdpms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

## Day-to-day commands

| Goal | Command |
|------|---------|
| Run server + Vite + queue + log tail together | `composer dev` |
| Watch frontend only | `npm run dev` |
| Build production assets | `npm run build` |
| All tests | `composer test` |
| Filter tests | `php artisan test --compact --filter=PayrollCalculator` |
| Format PHP (only changed files) | `vendor/bin/pint --dirty --format agent` |
| Tail Laravel log | `php artisan pail` |
| List routes | `php artisan route:list --except-vendor` |
| Run a one-off PHP snippet | `php artisan tinker --execute 'App\Models\Department::count();'` |

## Conventions

The project follows the conventions documented in the project root `CLAUDE.md` and the user's global preferences:

- **Routes.** Explicit `Route::get|post|put|delete` calls — never `Route::resource`.
- **Validation.** Always in `FormRequest`, never inline in controllers.
- **DTOs.** `final readonly class` for cross-layer data (`PayrollBreakdown`).
- **Tests.** Pest 3, mirror source structure under `tests/`. Use factories.
- **PHP 8+ features.** Constructor promotion, readonly, enums where appropriate, named args, attributes, match.
- **Format.** `vendor/bin/pint --format agent` before finalising changes.
- **Security.** `findOrFail()` + ownership checks before data access; secrets never committed.
- **UI.** WCAG 2.2 AA — keyboard nav, `aria-label` for icon-only controls, contrast ≥ 4.5:1. SweetAlert2 for confirms instead of native `confirm()`.

## Adding a feature

A typical full-stack feature touches:

1. **Migration** (`php artisan make:migration ...`)
2. **Model** (`php artisan make:model Foo`)
3. **Factory** (auto-generated with `-f`)
4. **Form request(s)** (`php artisan make:request Foo/StoreFooRequest`)
5. **Controller(s)** under the relevant sub-namespace
6. **Routes** in the matching module file under `routes/`
7. **Views** under `resources/views/foos/` (with a shared `_form.blade.php` partial)
8. **Tests** under `tests/Feature/FooCrudTest.php` and, if pure logic is involved, `tests/Unit/...`

Run `vendor/bin/pint --dirty --format agent` and `composer test` before finalising.

## UI verification

UI changes are smoke-tested through the **Playwright MCP** browser tools rather than a manual click-through. Pest feature tests cover the controller logic, but rendered DOM, JS handlers, flash messages, and console errors are verified by driving the live site at `http://mdpms.test` through the MCP `browser_navigate` / `browser_fill_form` / `browser_click` / `browser_snapshot` / `browser_console_messages` tools.

## Code style enforcement

`vendor/bin/pint` uses the Laravel preset (no custom rules). Always run with `--dirty` to limit the formatter to files you have changed in the working tree:

```bash
vendor/bin/pint --dirty --format agent
```

Static analysis (Larastan / PHPStan level 5+) is not currently wired up but is a known follow-up per the user's global preferences.

## Troubleshooting

| Symptom | Likely fix |
|---------|-----------|
| `Vite manifest not found` | `npm run build` (or `npm run dev` while developing) |
| MySQL `Unknown database 'mdpms'` | `CREATE DATABASE mdpms ...` (see [database-schema.md](database-schema.md)) |
| `Cannot find native binding` from Tailwind/Vite | `rm -rf node_modules package-lock.json && npm install` |
| Stale routes after editing module files | `php artisan route:clear` (Laravel auto-discovers, but caches can lag) |
| Stale config after editing `.env` | `php artisan config:clear` |
