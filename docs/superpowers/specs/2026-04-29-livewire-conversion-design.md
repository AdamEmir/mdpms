# Livewire 4 Conversion — Design

**Date:** 2026-04-29
**Status:** Approved for planning
**Scope:** Convert the entire `mdpms` HTTP surface (auth, dashboard, departments, employees, payroll) from controllers + Blade to Livewire 4 class-based components, with modal CRUD on index pages and SPA-style topbar navigation. The PDF payslip endpoint stays as a controller.

---

## 1. Goals

- Eliminate full-page refreshes in the topbar (`wire:navigate` everywhere internal).
- Replace controllers + Blade views with Livewire 4 class-based components for every page except the binary PDF download.
- Collapse the per-resource `index` / `create` / `edit` route triples into a single index route per resource with modal CRUD.
- Preserve all current behavior: authentication, validation, "cannot delete with dependents" guards, status/error flash messages, search and filter URL state.
- Keep the diff focused on the Livewire conversion. No domain-model changes, no module restructure (planned as a separate pass).

## 2. Non-goals

- Restructuring into `app/Modules/` (deferred to a follow-up pass).
- Changing payroll calculation logic, models, or migrations.
- Upgrading to Pest 4 / adding browser tests.
- Replacing SweetAlert2 with native dialogs or `wire:confirm`.
- Real-time / broadcast features (`wire:poll`, Laravel Echo).

## 3. Component Inventory

All components are class-based under `app/Livewire/`, with views in `resources/views/livewire/`.

| Component | Route | Replaces |
|---|---|---|
| `Auth\Login` | `GET /login` | `Auth\LoginController@create` + `@store` |
| `Auth\Register` | `GET /register` | `Auth\RegisterController@create` + `@store` |
| `Auth\LogoutButton` | embedded in layout | `Auth\LoginController@destroy` + topbar form |
| `Dashboard` | `GET /dashboard` | `DashboardController@index` |
| `Departments\Index` | `GET /departments` | `DepartmentController` + `create.blade.php` + `edit.blade.php` |
| `Employees\Index` | `GET /employees` | `EmployeeController` + `create.blade.php` + `edit.blade.php` |
| `Payroll\Run` | `GET /payroll/run` | `Payroll\PayrollController@create` + `@store` |
| `Payroll\History` | `GET /payroll/history` | `Payroll\PayrollHistoryController` |
| `Payroll\Payslip` | `GET /payroll/{record}/payslip` | `Payroll\PayslipController@show` |

**Stays as controller:** `Payroll\PayslipController@pdf` at `GET /payroll/{record}/payslip.pdf`. Returns a binary PDF; not a Livewire fit.

## 4. Routing

`routes/web.php` becomes:

```php
use App\Livewire\Auth\{Login, Register};
use App\Livewire\Dashboard;
use App\Livewire\Departments\Index as DepartmentsIndex;
use App\Livewire\Employees\Index as EmployeesIndex;
use App\Livewire\Payroll\{Run as PayrollRun, History as PayrollHistory, Payslip};
use App\Http\Controllers\Payroll\PayslipController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));

Route::middleware('guest')->group(function () {
    Route::get('/login',    Login::class)->name('login');
    Route::get('/register', Register::class)->name('register');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard',                  Dashboard::class)->name('dashboard');
    Route::get('/departments',                DepartmentsIndex::class)->name('departments.index');
    Route::get('/employees',                  EmployeesIndex::class)->name('employees.index');
    Route::get('/payroll/run',                PayrollRun::class)->name('payroll.run');
    Route::get('/payroll/history',            PayrollHistory::class)->name('payroll.history');
    Route::get('/payroll/{record}/payslip',   Payslip::class)->name('payroll.payslip');
    Route::get('/payroll/{record}/payslip.pdf', [PayslipController::class, 'pdf'])->name('payroll.payslip.pdf');
});
```

Per the user preference in memory, every verb is written explicitly — no `Route::resource`. Logout is **not** a route; it is the `Auth\LogoutButton` Livewire component which calls `Auth::logout()` directly.

## 5. Layout

`resources/views/layouts/app.blade.php`:

- Add `@livewireStyles` in `<head>`, `@livewireScripts` immediately before `</body>`.
- Replace `@yield('content')` with `{{ $slot }}`. Full-page Livewire components declare `#[Layout('layouts.app')]` on the class.
- Every internal `<a>` in the topbar gets `wire:navigate`. Logo link too.
- The current logout `<form>` is replaced with `<livewire:auth.logout-button />`.
- The existing inline SweetAlert2 listener for `form[data-confirm]` stays (still used by the logout button via Alpine, and by other server-rendered confirm dialogs if any).

`layouts/guest.blade.php` gets the same `@livewireStyles`/`@livewireScripts` treatment for the auth pages.

## 6. Index Components — Modal CRUD Pattern

Both `Departments\Index` and `Employees\Index` follow the same shape.

**Traits / attributes:**

- `use WithPagination;`
- `#[Layout('layouts.app')]` on the class.
- `#[Url]` on `$search` (and `$departmentId` for employees) so query-string state survives reload and `wire:navigate`.

**State (public properties):**

- `?int $editingId = null;` — null means "create mode" when modal is open.
- `bool $showForm = false;`
- Form fields with `#[Validate]` attributes — the rule set is the same as today's `Store*Request` / `Update*Request` form requests.

**Actions:**

- `openCreate()` — reset form fields, `$editingId = null`, `$showForm = true`.
- `openEdit(int $id)` — load record, hydrate form fields, set `$editingId`, `$showForm = true`.
- `closeForm()` — reset form fields and errors, `$showForm = false`.
- `save()` — `$this->validate()`; create when `$editingId` is null, otherwise update; flash status; close modal; `$this->resetPage()` if list ordering may have changed.
- `delete(int $id)` — guards against dependents (employees in department; payroll records on employee), flashes error if blocked, otherwise deletes and flashes status.

**Computed (`#[Computed]`):**

- `departments()` on `Employees\Index` — caches the dropdown source for one render.
- `rows()` on each — returns the paginated query result, applying search/filter.

**View structure:**

- Single Blade file under `resources/views/livewire/{departments,employees}/index.blade.php`.
- Sections: header + "New" button, search/filter form (`wire:model.live.debounce.300ms="search"`), table, pagination links, modal.
- Modal is conditional (`@if($showForm)`), Tailwind-only, with a backdrop, `wire:keydown.escape="closeForm"`, `x-trap` from Alpine for focus trap.
- Accessibility: `role="dialog"`, `aria-modal="true"`, `aria-labelledby` to the heading, focus returns to the triggering button on close (Alpine `x-on:close` handler).

**Delete confirmation (SweetAlert2 — per global CLAUDE.md):**

The delete button has Alpine wiring:

```html
<button
    type="button"
    x-data
    x-on:click="
        Swal.fire({
            title: 'Delete department?',
            text: 'This cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            confirmButtonText: 'Yes, delete',
        }).then(result => { if (result.isConfirmed) $wire.delete({{ $row->id }}); });
    "
    class="...">
    Delete
</button>
```

Native `wire:confirm` is **not** used.

## 7. Auth Components

**`Auth\Login`:**

- Properties: `#[Validate('required|email')] $email`, `#[Validate('required')] $password`, `bool $remember = false`.
- `submit()`:
  - Throttle key from `Str::lower($this->email).'|'.request()->ip()`. Use `RateLimiter::tooManyAttempts(...)` mirroring the current controller (5 attempts/min).
  - On success: `Auth::attempt(...)` with remember, `request()->session()->regenerate()`, clear rate-limiter, `return $this->redirectIntended(route('dashboard'), navigate: true)`.
  - On failure: `RateLimiter::hit($key)`, `addError('email', __('auth.failed'))`.
- `#[Layout('layouts.guest')]`.

**`Auth\Register`:**

- Properties: `$name`, `$email`, `$password`, `$password_confirmation` with rules from the current `RegisterController` (`required|string|max:255`, `required|email|unique:users,email`, `required|confirmed|min:8`).
- `submit()`: create user, `Auth::login($user)`, `session()->regenerate()`, redirect to dashboard with `navigate: true`.
- `#[Layout('layouts.guest')]`.

**`Auth\LogoutButton`:**

- No properties. One action `logout()` that calls `Auth::logout()`, `session()->invalidate()`, `session()->regenerateToken()`, redirects to `/login` with `navigate: false` (full reload to clear all Livewire state).
- View is the existing button markup. Alpine triggers SweetAlert2 confirm before calling `$wire.logout()`.

## 8. Payroll Components

**`Payroll\Run`:**

- Form-style component. Public properties match the current `Payroll\PayrollController@store` request fields.
- `process()` calls the existing payroll service (no service changes), flashes status, redirects to `payroll.history` with `navigate: true`.

**`Payroll\History`:**

- `WithPagination`, paginated list of payroll records.
- Rows link to `payroll.payslip` with `wire:navigate`.
- PDF link is a normal `<a target="_blank">` to `payroll.payslip.pdf` — no `wire:navigate` (binary download).

**`Payroll\Payslip`:**

- Route-model binding: `public function mount(PayrollRecord $record): void { $this->record = $record; }`. Authorization preserved if the existing controller had any.
- View renders `_payslip-card.blade.php` (kept) plus a "Download PDF" button linking to `payroll.payslip.pdf`.

## 9. Validation

`app/Http/Requests/Department/*` and `app/Http/Requests/Employee/*` are **deleted**. Rules move to `#[Validate]` attributes on the corresponding component properties. Rule strings are copied verbatim from the current FormRequests so behavior matches.

`#[Validate]` is preferred over a `rules()` method because the rules are simple per-property strings and this keeps each property's contract local to its declaration.

## 10. Cleanup (Final Step)

After all components are green:

- Delete `app/Http/Controllers/Auth/{LoginController,RegisterController}.php`.
- Delete `app/Http/Controllers/DashboardController.php`.
- Delete `app/Http/Controllers/Department/`.
- Delete `app/Http/Controllers/Employee/`.
- Delete `app/Http/Controllers/Payroll/{PayrollController,PayrollHistoryController}.php`.
- Trim `Payroll/PayslipController.php` to the `pdf` action only.
- Delete `app/Http/Requests/Department/`, `app/Http/Requests/Employee/`, and any payroll request classes that are no longer referenced.
- Delete `resources/views/auth/`, `resources/views/dashboard.blade.php`, `resources/views/departments/`, `resources/views/employees/`, `resources/views/payroll/{run,history,payslip}.blade.php`. Keep `resources/views/payroll/payslip-pdf.blade.php` and `resources/views/payroll/_payslip-card.blade.php`.
- Audit `php artisan route:list --except-vendor` — only the routes in §4 should remain.

## 11. Testing Strategy

- One Livewire feature test per component under `tests/Feature/Livewire/...` mirroring `app/Livewire/...`.
- Test pattern: `Livewire::test(Component::class)->set(...)->call(...)->assertHasErrors(...)|assertSee(...)|assertRedirect(...)`.
- Existing HTTP feature tests targeting routes that disappear are rewritten as Livewire tests (the old routes won't exist, so leaving them in place would break the suite).
- Coverage targets per Index component:
  - Lists current rows, paginates correctly.
  - Search/filter narrows results and updates the URL.
  - `openCreate` shows modal; `save` validates required fields; valid input creates a row and flashes status.
  - `openEdit` hydrates the modal with the row's values; `save` updates.
  - `delete` is blocked when dependents exist (asserts flash `error` and row is still present); succeeds otherwise.
- Auth coverage: success, invalid creds, throttling, register flow.
- Payroll coverage: run validates, processes, redirects; history paginates and links correctly; payslip renders.
- After each component lands: `vendor/bin/pint --dirty --format agent`, then `php artisan test --compact --filter=<Component>`, then a Playwright MCP smoke-check (golden path + one error path) per the memory rule about UI verification.

## 12. Rollout Order

Each step ships independently with passing tests and a Playwright smoke check. If we stop after any step, the app remains end-to-end functional.

1. **Layout foundation** — `@livewireStyles`/`@livewireScripts`, `wire:navigate` on topbar, `Auth\LogoutButton` component, `layouts/guest.blade.php` updated.
2. **`Dashboard`** — smallest full-page component; validates layout slot + nav transitions.
3. **`Departments\Index`** — establishes the modal-CRUD pattern (no search; simpler than employees).
4. **`Employees\Index`** — extends the pattern with `#[Url]` search + department filter.
5. **`Payroll\Run`, `Payroll\History`, `Payroll\Payslip`** — payroll subsystem, smaller scope each.
6. **`Auth\Login`, `Auth\Register`** — auth pages, including throttle behavior.
7. **Cleanup pass** — delete dead controllers, FormRequests, and Blade views per §10; rewrite/remove orphaned HTTP tests; final route-list audit; full `php artisan test`.

## 13. Risks & Open Questions

- **`wire:navigate` + Alpine modal state:** Alpine state on a page is reset across `wire:navigate`, but a modal that's open at navigation time could leave the body in a scroll-locked state. Mitigation: `closeForm()` fires on Livewire's `navigating` event in each Index component.
- **SweetAlert2 + Livewire navigate:** The Swal CDN script is in `<head>`; persists across `wire:navigate`. No change needed, but verify in step 1's Playwright smoke check.
- **Throttling on `Auth\Login`:** the `RateLimiter` key is per-IP + email, same as today. Confirmed test coverage in step 6.
- **Session flash after `redirect()->intended(..., navigate: true)`:** Livewire's navigate-redirect carries flash messages on the next request. Confirmed by step 1's smoke check.
- **PDF link in `wire:navigate` context:** plain `<a>` (no `wire:navigate`) opens normally because Livewire only intercepts links with the directive.