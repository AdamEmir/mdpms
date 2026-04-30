# Livewire 4 Conversion Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Convert the entire `mdpms` HTTP surface (auth, dashboard, departments, employees, payroll views) from controllers + Blade to Livewire 4 class-based components with modal CRUD on index pages and SPA-style topbar navigation. The PDF payslip endpoint stays as a controller.

**Architecture:** Class-based Livewire 4 components under `app/Livewire/` (flat by domain), views under `resources/views/livewire/`. Each index page is a single component owning list state (`WithPagination`, `#[Url]`-bound search/filter) plus a Tailwind modal for create/edit (replaces the `/create` and `/{id}/edit` routes). Validation moves from FormRequests into `#[Validate]` attributes on component properties. Topbar links use `wire:navigate`. Delete confirmations use SweetAlert2 via Alpine, calling `$wire.delete(id)` on confirm.

**Tech Stack:** Laravel 12, Livewire 4, Pest 3, Tailwind 4, Alpine.js (bundled with Livewire), SweetAlert2 (CDN, already loaded).

**Reference spec:** `docs/superpowers/specs/2026-04-29-livewire-conversion-design.md`.

**Git policy:** The user manages all git operations manually in this project. Each task ends with a "Hand off to user for commit" step listing the files staged for review — do not run `git add`, `git commit`, or any git mutation. Read-only `git status`/`git diff` are fine for sanity checks.

**Per-task tooling discipline:**
- Run `vendor/bin/pint --dirty --format agent` after every PHP edit, before declaring the task done.
- Run the affected tests with `php artisan test --compact --filter=<TestName>` after each test step.
- After UI changes land, smoke-test the affected page(s) via Playwright MCP browser tools (golden path + one error path) before handoff.

---

## Task 1: Layout Foundation + LogoutButton

Establishes the base everything else depends on: Livewire scripts in both layouts, `wire:navigate` on the topbar, the layout slot transition from `@yield('content')` to `{{ $slot }}`, and the `Auth\LogoutButton` Livewire component used by the topbar.

This task does **not** convert any page. After it lands, the existing controllers + Blade views still work — but pages now render inside `{{ $slot }}` via a tiny shim until they're ported.

**Files:**
- Modify: `resources/views/layouts/app.blade.php` (top-to-bottom rewrite of head, nav, logout area)
- Modify: `resources/views/layouts/guest.blade.php` (add Livewire scripts)
- Create: `app/Livewire/Auth/LogoutButton.php`
- Create: `resources/views/livewire/auth/logout-button.blade.php`
- Create: `tests/Feature/Livewire/Auth/LogoutButtonTest.php`

### Step 1: Add a layout shim so existing `@section('content')` pages keep rendering

Livewire 4 full-page components render into `{{ $slot }}`. The existing pages still use `@yield('content')` / `@section('content')`. To avoid breaking everything in one task, support both for the duration of this conversion: keep `@yield('content')` and add `{{ $slot ?? '' }}` next to it. This is a temporary brace removed in Task 10.

- [ ] **Step 1.1: Read the current layout** to confirm baseline.

Run: `cat resources/views/layouts/app.blade.php`
Expected: matches the file documented in the design spec (header with hardcoded nav links, `@yield('content')`, SweetAlert2 listener at the bottom).

- [ ] **Step 1.2: Rewrite `resources/views/layouts/app.blade.php`** to add Livewire scripts, `wire:navigate` on every internal link, slot rendering, and the LogoutButton placeholder.

Replace the entire file with:

```blade
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') &mdash; {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @livewireStyles
</head>
<body class="h-full bg-slate-50 text-slate-900 antialiased">
    <div class="min-h-full">
        <header class="border-b border-slate-200 bg-white">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6 lg:px-8">
                <div class="flex items-center gap-8">
                    <a href="{{ route('dashboard') }}" wire:navigate class="text-lg font-semibold tracking-tight text-slate-900">MDPMS</a>
                    <nav class="hidden gap-1 md:flex" aria-label="Primary">
                        @php
                            $links = [
                                ['route' => 'dashboard',          'label' => 'Dashboard'],
                                ['route' => 'departments.index',  'label' => 'Departments'],
                                ['route' => 'employees.index',    'label' => 'Employees'],
                                ['route' => 'payroll.run',        'label' => 'Run Payroll'],
                                ['route' => 'payroll.history',    'label' => 'History'],
                            ];
                        @endphp
                        @foreach ($links as $link)
                            @php
                                $active = request()->routeIs($link['route']) || request()->routeIs(str_replace('.index', '.*', $link['route']));
                            @endphp
                            <a href="{{ route($link['route']) }}" wire:navigate
                               class="rounded-md px-3 py-2 text-sm font-medium {{ $active ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                                {{ $link['label'] }}
                            </a>
                        @endforeach
                    </nav>
                </div>
                <div class="flex items-center gap-3">
                    <span class="hidden text-sm text-slate-500 sm:inline">{{ auth()->user()?->name }}</span>
                    @auth
                        <livewire:auth.logout-button />
                    @endauth
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            @if (session('status'))
                <div role="status" class="mb-4 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    {{ session('status') }}
                </div>
            @endif
            @if (session('error'))
                <div role="alert" class="mb-4 rounded-md border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                    {{ session('error') }}
                </div>
            @endif

            @yield('content')
            {{ $slot ?? '' }}
        </main>
    </div>

    <script>
        document.addEventListener('submit', function (e) {
            const form = e.target;
            if (!form.matches('form[data-confirm]')) return;
            if (form.dataset.confirmed === 'yes') return;
            e.preventDefault();
            const isDestructive = form.dataset.confirm === 'delete';
            Swal.fire({
                title: form.dataset.confirmTitle || 'Are you sure?',
                text: form.dataset.confirmText || 'Please confirm to continue.',
                icon: isDestructive ? 'warning' : 'question',
                showCancelButton: true,
                confirmButtonColor: isDestructive ? '#dc2626' : '#0f172a',
                confirmButtonText: form.dataset.confirmButton || (isDestructive ? 'Yes, delete' : 'Confirm'),
            }).then((result) => {
                if (result.isConfirmed) {
                    form.dataset.confirmed = 'yes';
                    form.submit();
                }
            });
        });
    </script>
    @livewireScripts
</body>
</html>
```

Note: `{{ $slot ?? '' }}` next to `@yield('content')` is the temporary shim. Task 10 removes `@yield('content')` after every page is converted.

- [ ] **Step 1.3: Rewrite `resources/views/layouts/guest.blade.php`** to include Livewire scripts and a slot.

Replace the entire file with:

```blade
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'MDPMS') &mdash; {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full bg-slate-50 text-slate-900 antialiased">
    <main class="flex min-h-full items-center justify-center px-4 py-12">
        <div class="w-full max-w-md">
            <div class="mb-8 text-center">
                <h1 class="text-2xl font-semibold tracking-tight text-slate-900">{{ config('app.name') }}</h1>
                <p class="mt-1 text-sm text-slate-500">Multi-Department Payroll Management System</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-8 shadow-sm">
                @yield('content')
                {{ $slot ?? '' }}
            </div>
        </div>
    </main>
    @livewireScripts
</body>
</html>
```

### Step 2: Write the failing `LogoutButton` test

- [ ] **Step 2.1: Create `tests/Feature/Livewire/Auth/LogoutButtonTest.php`:**

```php
<?php

use App\Livewire\Auth\LogoutButton;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

it('logs the user out and redirects to login', function () {
    $user = User::factory()->create();
    actingAs($user);

    Livewire::test(LogoutButton::class)
        ->call('logout')
        ->assertRedirect(route('login'));

    expect(auth()->check())->toBeFalse();
});
```

- [ ] **Step 2.2: Run the test and verify it fails.**

Run: `php artisan test --compact --filter=LogoutButtonTest`
Expected: FAIL — `Class "App\Livewire\Auth\LogoutButton" not found` (or similar).

### Step 3: Create the `LogoutButton` component

- [ ] **Step 3.1: Run the artisan generator.**

Run: `php artisan make:livewire Auth/LogoutButton --no-interaction`
Expected: creates `app/Livewire/Auth/LogoutButton.php` and `resources/views/livewire/auth/logout-button.blade.php`.

- [ ] **Step 3.2: Replace `app/Livewire/Auth/LogoutButton.php`** with:

```php
<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class LogoutButton extends Component
{
    public function logout()
    {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return $this->redirect(route('login'), navigate: false);
    }

    public function render()
    {
        return view('livewire.auth.logout-button');
    }
}
```

`navigate: false` is intentional — a full reload clears all Livewire component state on the client.

- [ ] **Step 3.3: Replace `resources/views/livewire/auth/logout-button.blade.php`** with:

```blade
<button type="button"
        x-data
        x-on:click="
            Swal.fire({
                title: 'Log out?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#0f172a',
                confirmButtonText: 'Yes, log out',
            }).then(result => { if (result.isConfirmed) $wire.logout(); });
        "
        class="rounded-md bg-white px-3 py-2 text-sm font-medium text-slate-700 ring-1 ring-inset ring-slate-300 hover:bg-slate-50">
    Log out
</button>
```

- [ ] **Step 3.4: Run Pint and the test.**

Run: `vendor/bin/pint --dirty --format agent && php artisan test --compact --filter=LogoutButtonTest`
Expected: Pint reports formatted/no changes; LogoutButtonTest PASS.

### Step 4: Smoke-test the topbar in the browser

- [ ] **Step 4.1: Run the full suite to confirm no regressions.**

Run: `php artisan test --compact`
Expected: all existing tests still pass (the layout shim keeps old pages working).

- [ ] **Step 4.2: Smoke-test via Playwright MCP.**

Navigate to `https://mdpms.test/login`, log in with a known seeded/factory user (or register one first), then click each topbar link in turn. Verify:
- Each click triggers a `wire:navigate` transition (no full reload — confirm via the network panel showing only XHR / `?_wireRequest` calls, not document loads).
- The active-link highlight updates correctly.
- "Log out" shows a SweetAlert2 confirm; clicking confirm logs out and lands on `/login` with a fresh document load.

Capture a screenshot of the dashboard after navigating from departments back to dashboard via `wire:navigate` for the handoff message.

### Step 5: Hand off to user for commit

- [ ] **Step 5.1: Tell the user the task is done and list the files for commit.**

Files staged for review:
- `resources/views/layouts/app.blade.php`
- `resources/views/layouts/guest.blade.php`
- `app/Livewire/Auth/LogoutButton.php`
- `resources/views/livewire/auth/logout-button.blade.php`
- `tests/Feature/Livewire/Auth/LogoutButtonTest.php`

Suggested commit message: `feat: livewire scripts in layouts + logout button component`.

---

## Task 2: Dashboard Component

Smallest full-page conversion — no forms, no list state, no modal. Validates the layout slot mechanism end-to-end.

**Files:**
- Create: `app/Livewire/Dashboard.php`
- Create: `resources/views/livewire/dashboard.blade.php`
- Modify: `routes/web.php` (point `/dashboard` at the Livewire component)
- Create: `tests/Feature/Livewire/DashboardTest.php`
- Delete (at end of task): `app/Http/Controllers/DashboardController.php`, `resources/views/dashboard.blade.php`

### Step 1: Write the failing test

- [ ] **Step 1.1: Create `tests/Feature/Livewire/DashboardTest.php`:**

```php
<?php

use App\Livewire\Dashboard;
use App\Models\Department;
use App\Models\Employee;
use App\Models\PayrollRecord;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

it('renders counts for departments, employees, and current-month payroll', function () {
    actingAs(User::factory()->create());

    Department::factory()->count(2)->create();
    $employees = Employee::factory()->count(3)->create();

    $now = now();
    PayrollRecord::factory()->create([
        'employee_id' => $employees->first()->id,
        'month' => $now->month,
        'year' => $now->year,
    ]);

    Livewire::test(Dashboard::class)
        ->assertSee('Dashboard')
        ->assertSee($now->format('F Y'))
        ->assertSeeText('2')   // departments
        ->assertSeeText('3')   // employees
        ->assertSeeText('1');  // payroll runs this month
});

it('redirects guests to login', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});
```

- [ ] **Step 1.2: Run the test.**

Run: `php artisan test --compact --filter=DashboardTest`
Expected: FAIL — `Class "App\Livewire\Dashboard" not found`.

### Step 2: Create the component

- [ ] **Step 2.1: Generate scaffolding.**

Run: `php artisan make:livewire Dashboard --no-interaction`

- [ ] **Step 2.2: Replace `app/Livewire/Dashboard.php`** with:

```php
<?php

namespace App\Livewire;

use App\Models\Department;
use App\Models\Employee;
use App\Models\PayrollRecord;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Dashboard')]
class Dashboard extends Component
{
    public function render()
    {
        $now = now();

        return view('livewire.dashboard', [
            'departmentsCount' => Department::count(),
            'employeesCount' => Employee::count(),
            'payrollThisMonth' => PayrollRecord::query()
                ->where('month', $now->month)
                ->where('year', $now->year)
                ->count(),
            'currentMonthLabel' => $now->format('F Y'),
        ]);
    }
}
```

- [ ] **Step 2.3: Replace `resources/views/livewire/dashboard.blade.php`** with the page body wrapped in a single root element (Livewire 4 requires a single root):

```blade
<div>
    <div class="mb-6">
        <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Dashboard</h1>
        <p class="mt-1 text-sm text-slate-500">Overview as of {{ $currentMonthLabel }}.</p>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-500">Departments</p>
            <p class="mt-2 text-3xl font-semibold tracking-tight text-slate-900">{{ $departmentsCount }}</p>
            <a href="{{ route('departments.index') }}" wire:navigate class="mt-3 inline-block text-sm font-medium text-slate-700 hover:underline">Manage &rarr;</a>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-500">Employees</p>
            <p class="mt-2 text-3xl font-semibold tracking-tight text-slate-900">{{ $employeesCount }}</p>
            <a href="{{ route('employees.index') }}" wire:navigate class="mt-3 inline-block text-sm font-medium text-slate-700 hover:underline">Manage &rarr;</a>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-500">Payroll runs this month</p>
            <p class="mt-2 text-3xl font-semibold tracking-tight text-slate-900">{{ $payrollThisMonth }}</p>
            <a href="{{ route('payroll.run') }}" wire:navigate class="mt-3 inline-block text-sm font-medium text-slate-700 hover:underline">Run payroll &rarr;</a>
        </div>
    </div>
</div>
```

### Step 3: Update routes

- [ ] **Step 3.1: Replace `routes/web.php`** with:

```php
<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Department\DepartmentController;
use App\Http\Controllers\Employee\EmployeeController;
use App\Http\Controllers\Payroll\PayrollController;
use App\Http\Controllers\Payroll\PayrollHistoryController;
use App\Http\Controllers\Payroll\PayslipController;
use App\Livewire\Dashboard;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));

Route::livewire('/demo', 'pages::counter')->name('demo');

Route::middleware('guest')->group(function () {
    Route::get('/login',  [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
    Route::get('/register',  [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->name('register.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/dashboard', Dashboard::class)->name('dashboard');

    Route::get('/departments',                       [DepartmentController::class, 'index'])->name('departments.index');
    Route::get('/departments/create',                [DepartmentController::class, 'create'])->name('departments.create');
    Route::post('/departments',                      [DepartmentController::class, 'store'])->name('departments.store');
    Route::get('/departments/{department}/edit',     [DepartmentController::class, 'edit'])->name('departments.edit');
    Route::put('/departments/{department}',          [DepartmentController::class, 'update'])->name('departments.update');
    Route::delete('/departments/{department}',       [DepartmentController::class, 'destroy'])->name('departments.destroy');

    Route::get('/employees',                  [EmployeeController::class, 'index'])->name('employees.index');
    Route::get('/employees/create',           [EmployeeController::class, 'create'])->name('employees.create');
    Route::post('/employees',                 [EmployeeController::class, 'store'])->name('employees.store');
    Route::get('/employees/{employee}/edit',  [EmployeeController::class, 'edit'])->name('employees.edit');
    Route::put('/employees/{employee}',       [EmployeeController::class, 'update'])->name('employees.update');
    Route::delete('/employees/{employee}',    [EmployeeController::class, 'destroy'])->name('employees.destroy');

    Route::get('/payroll/run',                  [PayrollController::class, 'create'])->name('payroll.run');
    Route::post('/payroll/run',                 [PayrollController::class, 'store'])->name('payroll.process');
    Route::get('/payroll/history',              [PayrollHistoryController::class, 'index'])->name('payroll.history');
    Route::get('/payroll/{record}/payslip',     [PayslipController::class, 'show'])->name('payroll.payslip');
    Route::get('/payroll/{record}/payslip.pdf', [PayslipController::class, 'pdf'])->name('payroll.payslip.pdf');
});
```

This keeps every controller route the conversion hasn't replaced yet. The plan rewrites this file again at each task's "update routes" step, narrowing toward the final shape.

### Step 4: Verify and clean up

- [ ] **Step 4.1: Run Pint and tests.**

Run: `vendor/bin/pint --dirty --format agent && php artisan test --compact --filter=DashboardTest`
Expected: PASS.

- [ ] **Step 4.2: Run the full suite.**

Run: `php artisan test --compact`
Expected: all tests pass.

- [ ] **Step 4.3: Delete the old controller and view.**

Delete `app/Http/Controllers/DashboardController.php` and `resources/views/dashboard.blade.php`.

- [ ] **Step 4.4: Smoke-test via Playwright MCP.**

Log in, navigate to `/dashboard`, confirm the three counter cards render with the expected counts (factory or existing data), confirm the topbar's Dashboard link is highlighted, click "Manage →" on the Departments card and verify it `wire:navigate`s to `/departments` (still controller-rendered at this point).

### Step 5: Hand off to user for commit

- [ ] **Step 5.1: Files for commit:** `app/Livewire/Dashboard.php`, `resources/views/livewire/dashboard.blade.php`, `routes/web.php`, `tests/Feature/Livewire/DashboardTest.php`, deletions of `app/Http/Controllers/DashboardController.php` and `resources/views/dashboard.blade.php`.

Suggested commit message: `refactor: convert dashboard to livewire component`.

---

## Task 3: Departments\Index — Modal CRUD

Establishes the modal-CRUD pattern that Employees\Index will follow. List + paginate + create + edit + delete in a single component, with `/departments/create` and `/departments/{id}/edit` routes deleted.

**Files:**
- Create: `app/Livewire/Departments/Index.php`
- Create: `resources/views/livewire/departments/index.blade.php`
- Modify: `routes/web.php`
- Create: `tests/Feature/Livewire/Departments/IndexTest.php`
- Delete: `app/Http/Controllers/Department/DepartmentController.php`, `app/Http/Requests/Department/StoreDepartmentRequest.php`, `app/Http/Requests/Department/UpdateDepartmentRequest.php`, `resources/views/departments/` (entire folder)
- Delete: `tests/Feature/DepartmentCrudTest.php` (rewritten as the new test)

### Step 1: Write failing tests

- [ ] **Step 1.1: Create `tests/Feature/Livewire/Departments/IndexTest.php`:**

```php
<?php

use App\Livewire\Departments\Index;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(fn () => actingAs(User::factory()->create()));

it('lists departments with employee counts', function () {
    $eng = Department::factory()->create(['name' => 'Engineering']);
    Employee::factory()->count(2)->create(['department_id' => $eng->id]);

    Livewire::test(Index::class)
        ->assertSee('Engineering')
        ->assertSeeText('2');
});

it('paginates 10 per page', function () {
    Department::factory()->count(12)->create();

    Livewire::test(Index::class)
        ->assertSet('paginators.page', 1)
        ->call('gotoPage', 2)
        ->assertSet('paginators.page', 2);
});

it('opens the modal in create mode with empty fields', function () {
    Livewire::test(Index::class)
        ->assertSet('showForm', false)
        ->call('openCreate')
        ->assertSet('showForm', true)
        ->assertSet('editingId', null)
        ->assertSet('name', '');
});

it('opens the modal in edit mode with the row hydrated', function () {
    $dept = Department::factory()->create(['name' => 'Finance']);

    Livewire::test(Index::class)
        ->call('openEdit', $dept->id)
        ->assertSet('showForm', true)
        ->assertSet('editingId', $dept->id)
        ->assertSet('name', 'Finance');
});

it('validates name is required', function () {
    Livewire::test(Index::class)
        ->call('openCreate')
        ->set('name', '')
        ->call('save')
        ->assertHasErrors(['name' => 'required']);
});

it('validates name is unique on create', function () {
    Department::factory()->create(['name' => 'Sales']);

    Livewire::test(Index::class)
        ->call('openCreate')
        ->set('name', 'Sales')
        ->call('save')
        ->assertHasErrors(['name' => 'unique']);
});

it('creates a department', function () {
    Livewire::test(Index::class)
        ->call('openCreate')
        ->set('name', 'Marketing')
        ->call('save')
        ->assertSet('showForm', false)
        ->assertHasNoErrors();

    expect(Department::where('name', 'Marketing')->exists())->toBeTrue();
});

it('updates a department', function () {
    $dept = Department::factory()->create(['name' => 'Old Name']);

    Livewire::test(Index::class)
        ->call('openEdit', $dept->id)
        ->set('name', 'New Name')
        ->call('save')
        ->assertSet('showForm', false)
        ->assertHasNoErrors();

    expect($dept->fresh()->name)->toBe('New Name');
});

it('allows updating without changing name (unique rule ignores self)', function () {
    $dept = Department::factory()->create(['name' => 'Same']);

    Livewire::test(Index::class)
        ->call('openEdit', $dept->id)
        ->set('name', 'Same')
        ->call('save')
        ->assertHasNoErrors();
});

it('blocks deletion when employees exist', function () {
    $dept = Department::factory()->create();
    Employee::factory()->create(['department_id' => $dept->id]);

    Livewire::test(Index::class)
        ->call('delete', $dept->id);

    expect(Department::find($dept->id))->not->toBeNull();
    expect(session('error'))->toContain('Cannot delete');
});

it('deletes a department with no employees', function () {
    $dept = Department::factory()->create();

    Livewire::test(Index::class)
        ->call('delete', $dept->id);

    expect(Department::find($dept->id))->toBeNull();
});
```

- [ ] **Step 1.2: Run the test and verify it fails.**

Run: `php artisan test --compact --filter='Livewire/Departments/IndexTest'`
Expected: FAIL — `Class "App\Livewire\Departments\Index" not found`.

### Step 2: Create the component

- [ ] **Step 2.1: Generate scaffolding.**

Run: `php artisan make:livewire Departments/Index --no-interaction`

- [ ] **Step 2.2: Replace `app/Livewire/Departments/Index.php`** with:

```php
<?php

namespace App\Livewire\Departments;

use App\Models\Department;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Departments')]
class Index extends Component
{
    use WithPagination;

    public bool $showForm = false;

    public ?int $editingId = null;

    #[Validate]
    public string $name = '';

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('departments', 'name')->ignore($this->editingId),
            ],
        ];
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $department = Department::findOrFail($id);
        $this->editingId = $department->id;
        $this->name = $department->name;
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->resetForm();
    }

    public function save(): void
    {
        $data = $this->validate();

        if ($this->editingId === null) {
            Department::create($data);
            session()->flash('status', 'Department created.');
        } else {
            Department::findOrFail($this->editingId)->update($data);
            session()->flash('status', 'Department updated.');
        }

        $this->resetForm();
        $this->resetPage();
    }

    public function delete(int $id): void
    {
        $department = Department::findOrFail($id);

        if ($department->employees()->exists()) {
            session()->flash('error', 'Cannot delete a department with employees. Reassign or remove them first.');

            return;
        }

        $department->delete();
        session()->flash('status', 'Department deleted.');
    }

    private function resetForm(): void
    {
        $this->reset(['showForm', 'editingId', 'name']);
        $this->resetErrorBag();
    }

    public function render()
    {
        return view('livewire.departments.index', [
            'departments' => Department::query()
                ->withCount('employees')
                ->orderBy('name')
                ->paginate(10),
        ]);
    }
}
```

`#[Validate]` on `$name` is the marker; the actual rules come from `rules()` because `Rule::unique(...)->ignore(...)` needs the dynamic `$editingId`. Both `#[Validate]`-attribute rules and `rules()` are valid in Livewire 4; using `rules()` here is the simplest way to express the conditional ignore.

- [ ] **Step 2.3: Replace `resources/views/livewire/departments/index.blade.php`** with:

```blade
<div>
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Departments</h1>
            <p class="mt-1 text-sm text-slate-500">Organisational units that group employees.</p>
        </div>
        <button type="button" wire:click="openCreate"
                class="inline-flex items-center rounded-md bg-slate-900 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-800">
            New department
        </button>
    </div>

    <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Name</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Employees</th>
                    <th class="px-4 py-3"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 bg-white">
                @forelse ($departments as $department)
                    <tr wire:key="dept-{{ $department->id }}">
                        <td class="px-4 py-3 text-sm font-medium text-slate-900">{{ $department->name }}</td>
                        <td class="px-4 py-3 text-sm text-slate-600">{{ $department->employees_count }}</td>
                        <td class="px-4 py-3 text-right text-sm">
                            <div class="inline-flex items-center gap-1">
                                <button type="button" wire:click="openEdit({{ $department->id }})"
                                        title="Edit"
                                        aria-label="Edit {{ $department->name }}"
                                        class="inline-flex items-center justify-center rounded-md p-2 text-slate-600 hover:bg-slate-100 hover:text-slate-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-slate-900">
                                    <i class="fa-regular fa-pen-to-square" aria-hidden="true"></i>
                                    <span class="sr-only">Edit</span>
                                </button>
                                <button type="button"
                                        x-data
                                        x-on:click="
                                            Swal.fire({
                                                title: 'Delete {{ $department->name }}?',
                                                text: 'This cannot be undone.',
                                                icon: 'warning',
                                                showCancelButton: true,
                                                confirmButtonColor: '#dc2626',
                                                confirmButtonText: 'Yes, delete',
                                            }).then(result => { if (result.isConfirmed) $wire.delete({{ $department->id }}); });
                                        "
                                        title="Delete"
                                        aria-label="Delete {{ $department->name }}"
                                        class="inline-flex items-center justify-center rounded-md p-2 text-rose-600 hover:bg-rose-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-rose-600">
                                    <i class="fa-regular fa-trash-can" aria-hidden="true"></i>
                                    <span class="sr-only">Delete</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-4 py-8 text-center text-sm text-slate-500">No departments yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $departments->links() }}</div>

    @if ($showForm)
        <div class="fixed inset-0 z-40 flex items-center justify-center bg-slate-900/40 px-4"
             role="dialog" aria-modal="true" aria-labelledby="dept-form-title"
             wire:key="dept-modal"
             x-data x-trap.noscroll="true"
             wire:keydown.escape="closeForm">
            <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
                <h2 id="dept-form-title" class="mb-4 text-lg font-semibold text-slate-900">
                    {{ $editingId ? 'Edit department' : 'New department' }}
                </h2>
                <form wire:submit="save" class="space-y-5" novalidate>
                    <div>
                        <label for="name" class="block text-sm font-medium text-slate-700">Department name</label>
                        <input id="name" type="text" wire:model="name" required
                               class="mt-1 block w-full rounded-md border-0 px-3 py-2 text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-slate-900">
                        @error('name')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="flex justify-end gap-3">
                        <button type="button" wire:click="closeForm"
                                class="rounded-md bg-white px-3 py-2 text-sm font-medium text-slate-700 ring-1 ring-inset ring-slate-300 hover:bg-slate-50">
                            Cancel
                        </button>
                        <button type="submit"
                                class="rounded-md bg-slate-900 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-800">
                            {{ $editingId ? 'Update' : 'Create' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
```

### Step 3: Update routes & remove dead code

- [ ] **Step 3.1: Edit `routes/web.php`** — replace the `/departments` controller block (six lines) with:

```php
    Route::get('/departments', \App\Livewire\Departments\Index::class)->name('departments.index');
```

Add to the imports: `use App\Livewire\Departments\Index as DepartmentsIndex;` (and use `DepartmentsIndex::class` instead of the FQN if you prefer; either works). Remove the three `departments.create`, `departments.edit`, `departments.update`, `departments.store`, `departments.destroy` route declarations.

- [ ] **Step 3.2: Run the test.**

Run: `php artisan test --compact --filter='Livewire/Departments/IndexTest'`
Expected: PASS.

- [ ] **Step 3.3: Delete the old controller, FormRequests, and views.**

Delete:
- `app/Http/Controllers/Department/` (whole folder)
- `app/Http/Requests/Department/` (whole folder)
- `resources/views/departments/` (whole folder)

- [ ] **Step 3.4: Replace the old HTTP test.**

Delete `tests/Feature/DepartmentCrudTest.php`. Its coverage is replaced by `tests/Feature/Livewire/Departments/IndexTest.php`.

- [ ] **Step 3.5: Run the full suite.**

Run: `vendor/bin/pint --dirty --format agent && php artisan test --compact`
Expected: all tests pass.

### Step 4: Smoke-test via Playwright MCP

- [ ] **Step 4.1:** Log in, go to `/departments`. Verify list renders. Click "New department" — modal opens, ESC closes it. Re-open, submit empty — required error appears under the name input. Submit `Sales` — modal closes, status flash appears, row appears in list. Click edit on the new row — modal opens with name prefilled. Click delete on a department with no employees — Swal confirm shows; on confirm, row disappears with status flash. Click delete on one with employees — error flash appears.

### Step 5: Hand off to user for commit

- [ ] **Step 5.1: Files for commit:** the new component + view + test, the route file, deletions of the controller/requests/views/old test.

Suggested commit message: `refactor: convert departments crud to livewire with modal`.

---

## Task 4: Employees\Index — Modal CRUD with URL-bound search & filter

Same pattern as Task 3 plus `#[Url]`-bound `$search` and `$departmentId`, debounced live search, and a department dropdown in the form.

**Files:**
- Create: `app/Livewire/Employees/Index.php`
- Create: `resources/views/livewire/employees/index.blade.php`
- Modify: `routes/web.php`
- Create: `tests/Feature/Livewire/Employees/IndexTest.php`
- Delete: `app/Http/Controllers/Employee/`, `app/Http/Requests/Employee/`, `resources/views/employees/`, `tests/Feature/EmployeeCrudTest.php`

### Step 1: Write failing tests

- [ ] **Step 1.1: Create `tests/Feature/Livewire/Employees/IndexTest.php`:**

```php
<?php

use App\Livewire\Employees\Index;
use App\Models\Department;
use App\Models\Employee;
use App\Models\PayrollRecord;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(fn () => actingAs(User::factory()->create()));

it('lists employees with their department', function () {
    $dept = Department::factory()->create(['name' => 'Engineering']);
    Employee::factory()->create(['name' => 'Alice', 'department_id' => $dept->id]);

    Livewire::test(Index::class)
        ->assertSee('Alice')
        ->assertSee('Engineering');
});

it('filters by search term', function () {
    Employee::factory()->create(['name' => 'Alice']);
    Employee::factory()->create(['name' => 'Bob']);

    Livewire::test(Index::class)
        ->set('search', 'Ali')
        ->assertSee('Alice')
        ->assertDontSee('Bob');
});

it('filters by department', function () {
    $deptA = Department::factory()->create();
    $deptB = Department::factory()->create();
    $a = Employee::factory()->create(['name' => 'AlphaPerson', 'department_id' => $deptA->id]);
    $b = Employee::factory()->create(['name' => 'BetaPerson', 'department_id' => $deptB->id]);

    Livewire::test(Index::class)
        ->set('departmentId', $deptA->id)
        ->assertSee('AlphaPerson')
        ->assertDontSee('BetaPerson');
});

it('opens create modal with empty fields and a department selector', function () {
    Department::factory()->create(['name' => 'Sales']);

    Livewire::test(Index::class)
        ->call('openCreate')
        ->assertSet('showForm', true)
        ->assertSet('editingId', null)
        ->assertSet('name', '')
        ->assertSee('Sales');
});

it('validates required fields', function () {
    Livewire::test(Index::class)
        ->call('openCreate')
        ->call('save')
        ->assertHasErrors([
            'name' => 'required',
            'position' => 'required',
            'departmentId' => 'required',
            'basicSalary' => 'required',
        ]);
});

it('creates an employee', function () {
    $dept = Department::factory()->create();

    Livewire::test(Index::class)
        ->call('openCreate')
        ->set('name', 'New Person')
        ->set('position', 'Engineer')
        ->set('departmentId', $dept->id)
        ->set('basicSalary', '5000.00')
        ->set('allowance', '500.00')
        ->set('overtimeHours', 0)
        ->set('hourlyRate', '0.00')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('showForm', false);

    expect(Employee::where('name', 'New Person')->exists())->toBeTrue();
});

it('hydrates fields when editing', function () {
    $dept = Department::factory()->create();
    $emp = Employee::factory()->create([
        'name' => 'Existing',
        'position' => 'Senior',
        'department_id' => $dept->id,
        'basic_salary' => '6000.00',
    ]);

    Livewire::test(Index::class)
        ->call('openEdit', $emp->id)
        ->assertSet('editingId', $emp->id)
        ->assertSet('name', 'Existing')
        ->assertSet('position', 'Senior')
        ->assertSet('departmentId', $dept->id);
});

it('updates an employee', function () {
    $emp = Employee::factory()->create(['name' => 'Old']);

    Livewire::test(Index::class)
        ->call('openEdit', $emp->id)
        ->set('name', 'Updated')
        ->call('save')
        ->assertHasNoErrors();

    expect($emp->fresh()->name)->toBe('Updated');
});

it('blocks deletion when payroll records exist', function () {
    $emp = Employee::factory()->create();
    PayrollRecord::factory()->create(['employee_id' => $emp->id]);

    Livewire::test(Index::class)->call('delete', $emp->id);

    expect(Employee::find($emp->id))->not->toBeNull();
    expect(session('error'))->toContain('Cannot delete');
});

it('deletes an employee without payroll records', function () {
    $emp = Employee::factory()->create();

    Livewire::test(Index::class)->call('delete', $emp->id);

    expect(Employee::find($emp->id))->toBeNull();
});
```

- [ ] **Step 1.2: Run.** Expected FAIL — class not found.

Run: `php artisan test --compact --filter='Livewire/Employees/IndexTest'`

### Step 2: Create the component

- [ ] **Step 2.1:** `php artisan make:livewire Employees/Index --no-interaction`

- [ ] **Step 2.2: Replace `app/Livewire/Employees/Index.php`** with:

```php
<?php

namespace App\Livewire\Employees;

use App\Models\Department;
use App\Models\Employee;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Employees')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'search')]
    public string $search = '';

    #[Url(as: 'department_id')]
    public ?int $departmentId = null;

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $position = '';

    /** @var int|string|null Form-bound department selection. */
    public int|string|null $formDepartmentId = null;

    public string $basicSalary = '';

    public string $allowance = '0.00';

    public int $overtimeHours = 0;

    public string $hourlyRate = '0.00';

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'position' => ['required', 'string', 'max:255'],
            'formDepartmentId' => ['required', 'integer', 'exists:departments,id'],
            'basicSalary' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'allowance' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'overtimeHours' => ['required', 'integer', 'min:0', 'max:744'],
            'hourlyRate' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'formDepartmentId' => 'department',
            'basicSalary' => 'basic salary',
            'overtimeHours' => 'overtime hours',
            'hourlyRate' => 'hourly rate',
        ];
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingDepartmentId(): void
    {
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $employee = Employee::findOrFail($id);
        $this->editingId = $employee->id;
        $this->name = $employee->name;
        $this->position = $employee->position;
        $this->formDepartmentId = $employee->department_id;
        $this->basicSalary = (string) $employee->basic_salary;
        $this->allowance = (string) $employee->allowance;
        $this->overtimeHours = (int) $employee->overtime_hours;
        $this->hourlyRate = (string) $employee->hourly_rate;
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->resetForm();
    }

    public function save(): void
    {
        $data = $this->validate();

        $payload = [
            'name' => $data['name'],
            'position' => $data['position'],
            'department_id' => (int) $data['formDepartmentId'],
            'basic_salary' => $data['basicSalary'],
            'allowance' => $data['allowance'],
            'overtime_hours' => $data['overtimeHours'],
            'hourly_rate' => $data['hourlyRate'],
        ];

        if ($this->editingId === null) {
            Employee::create($payload);
            session()->flash('status', 'Employee created.');
        } else {
            Employee::findOrFail($this->editingId)->update($payload);
            session()->flash('status', 'Employee updated.');
        }

        $this->resetForm();
        $this->resetPage();
    }

    public function delete(int $id): void
    {
        $employee = Employee::findOrFail($id);

        if ($employee->payrollRecords()->exists()) {
            session()->flash('error', 'Cannot delete an employee with payroll records.');

            return;
        }

        $employee->delete();
        session()->flash('status', 'Employee deleted.');
    }

    private function resetForm(): void
    {
        $this->reset([
            'showForm', 'editingId', 'name', 'position', 'formDepartmentId',
            'basicSalary', 'allowance', 'overtimeHours', 'hourlyRate',
        ]);
        $this->allowance = '0.00';
        $this->hourlyRate = '0.00';
        $this->overtimeHours = 0;
        $this->resetErrorBag();
    }

    #[Computed]
    public function departments()
    {
        return Department::orderBy('name')->get();
    }

    public function render()
    {
        $employees = Employee::query()
            ->with('department')
            ->when($this->departmentId, fn ($q, $id) => $q->where('department_id', $id))
            ->when($this->search !== '', fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->orderBy('name')
            ->paginate(10);

        return view('livewire.employees.index', compact('employees'));
    }
}
```

Note: the test asserts errors against `departmentId` and `basicSalary` (camelCase property names). The form uses a separate `$formDepartmentId` to avoid colliding with the `#[Url]`-bound list filter `$departmentId`. **Update Step 1.1's test rule keys** before this component lands — search the test file and rename `'departmentId' => 'required'` to `'formDepartmentId' => 'required'`, and `'basicSalary' => 'required'` stays the same. Apply the same rename to the `set('departmentId', ...)` lines inside the create/edit tests: in those tests, set `formDepartmentId` instead. Re-run the test after these edits to confirm rules align with the property names.

- [ ] **Step 2.3: Replace `resources/views/livewire/employees/index.blade.php`** with:

```blade
<div>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Employees</h1>
            <p class="mt-1 text-sm text-slate-500">Showing {{ $employees->total() }} employee(s).</p>
        </div>
        <button type="button" wire:click="openCreate"
                class="inline-flex items-center rounded-md bg-slate-900 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-800">
            New employee
        </button>
    </div>

    <div class="mb-4 flex flex-wrap items-end gap-3 rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
        <div class="grow min-w-[14rem]">
            <label for="search" class="block text-sm font-medium text-slate-700">Search employee</label>
            <input id="search" type="search" wire:model.live.debounce.300ms="search"
                   placeholder="Name contains…"
                   class="mt-1 block w-full rounded-md border-0 px-3 py-2 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-slate-900">
        </div>
        <div>
            <label for="department_id" class="block text-sm font-medium text-slate-700">Filter by department</label>
            <select id="department_id" wire:model.live="departmentId"
                    class="mt-1 rounded-md border-0 px-3 py-2 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-slate-900">
                <option value="">All departments</option>
                @foreach ($this->departments as $dept)
                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                @endforeach
            </select>
        </div>
        @if ($search !== '' || $departmentId)
            <button type="button" wire:click="$set('search', ''); $set('departmentId', null)"
                    class="text-sm font-medium text-slate-600 hover:underline">Clear</button>
        @endif
    </div>

    <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Name</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Position</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Department</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Basic</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">OT (h)</th>
                    <th class="px-4 py-3"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 bg-white">
                @forelse ($employees as $employee)
                    <tr wire:key="emp-{{ $employee->id }}">
                        <td class="px-4 py-3 text-sm font-medium text-slate-900">{{ $employee->name }}</td>
                        <td class="px-4 py-3 text-sm text-slate-600">{{ $employee->position }}</td>
                        <td class="px-4 py-3 text-sm text-slate-600">{{ $employee->department->name }}</td>
                        <td class="px-4 py-3 text-right text-sm tabular-nums text-slate-600">RM {{ number_format((float) $employee->basic_salary, 2) }}</td>
                        <td class="px-4 py-3 text-right text-sm tabular-nums text-slate-600">{{ $employee->overtime_hours }}</td>
                        <td class="px-4 py-3 text-right text-sm">
                            <div class="inline-flex items-center gap-1">
                                <button type="button" wire:click="openEdit({{ $employee->id }})"
                                        title="Edit" aria-label="Edit {{ $employee->name }}"
                                        class="inline-flex items-center justify-center rounded-md p-2 text-slate-600 hover:bg-slate-100 hover:text-slate-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-slate-900">
                                    <i class="fa-regular fa-pen-to-square" aria-hidden="true"></i>
                                    <span class="sr-only">Edit</span>
                                </button>
                                <button type="button"
                                        x-data
                                        x-on:click="
                                            Swal.fire({
                                                title: 'Delete {{ $employee->name }}?',
                                                text: 'This cannot be undone.',
                                                icon: 'warning',
                                                showCancelButton: true,
                                                confirmButtonColor: '#dc2626',
                                                confirmButtonText: 'Yes, delete',
                                            }).then(result => { if (result.isConfirmed) $wire.delete({{ $employee->id }}); });
                                        "
                                        title="Delete" aria-label="Delete {{ $employee->name }}"
                                        class="inline-flex items-center justify-center rounded-md p-2 text-rose-600 hover:bg-rose-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-rose-600">
                                    <i class="fa-regular fa-trash-can" aria-hidden="true"></i>
                                    <span class="sr-only">Delete</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-sm text-slate-500">No employees match these filters.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $employees->links() }}</div>

    @if ($showForm)
        <div class="fixed inset-0 z-40 flex items-center justify-center bg-slate-900/40 px-4"
             role="dialog" aria-modal="true" aria-labelledby="emp-form-title"
             wire:key="emp-modal"
             x-data x-trap.noscroll="true"
             wire:keydown.escape="closeForm">
            <div class="w-full max-w-2xl rounded-xl bg-white p-6 shadow-xl">
                <h2 id="emp-form-title" class="mb-4 text-lg font-semibold text-slate-900">
                    {{ $editingId ? 'Edit employee' : 'New employee' }}
                </h2>
                <form wire:submit="save" class="space-y-4" novalidate>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label for="name" class="block text-sm font-medium text-slate-700">Name</label>
                            <input id="name" type="text" wire:model="name"
                                   class="mt-1 block w-full rounded-md border-0 px-3 py-2 text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-slate-900">
                            @error('name')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="position" class="block text-sm font-medium text-slate-700">Position</label>
                            <input id="position" type="text" wire:model="position"
                                   class="mt-1 block w-full rounded-md border-0 px-3 py-2 text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-slate-900">
                            @error('position')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="formDepartmentId" class="block text-sm font-medium text-slate-700">Department</label>
                            <select id="formDepartmentId" wire:model="formDepartmentId"
                                    class="mt-1 block w-full rounded-md border-0 px-3 py-2 text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-slate-900">
                                <option value="">Select a department</option>
                                @foreach ($this->departments as $dept)
                                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                @endforeach
                            </select>
                            @error('formDepartmentId')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="basicSalary" class="block text-sm font-medium text-slate-700">Basic salary (RM)</label>
                            <input id="basicSalary" type="number" step="0.01" min="0" wire:model="basicSalary"
                                   class="mt-1 block w-full rounded-md border-0 px-3 py-2 text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-slate-900">
                            @error('basicSalary')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="allowance" class="block text-sm font-medium text-slate-700">Allowance (RM)</label>
                            <input id="allowance" type="number" step="0.01" min="0" wire:model="allowance"
                                   class="mt-1 block w-full rounded-md border-0 px-3 py-2 text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-slate-900">
                            @error('allowance')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="overtimeHours" class="block text-sm font-medium text-slate-700">Overtime hours</label>
                            <input id="overtimeHours" type="number" step="1" min="0" wire:model="overtimeHours"
                                   class="mt-1 block w-full rounded-md border-0 px-3 py-2 text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-slate-900">
                            @error('overtimeHours')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="hourlyRate" class="block text-sm font-medium text-slate-700">Hourly rate (RM)</label>
                            <input id="hourlyRate" type="number" step="0.01" min="0" wire:model="hourlyRate"
                                   class="mt-1 block w-full rounded-md border-0 px-3 py-2 text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-slate-900">
                            @error('hourlyRate')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" wire:click="closeForm"
                                class="rounded-md bg-white px-3 py-2 text-sm font-medium text-slate-700 ring-1 ring-inset ring-slate-300 hover:bg-slate-50">
                            Cancel
                        </button>
                        <button type="submit"
                                class="rounded-md bg-slate-900 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-800">
                            {{ $editingId ? 'Update' : 'Create' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
```

### Step 3: Update routes & remove dead code

- [ ] **Step 3.1: Edit `routes/web.php`** — replace the six `employees.*` routes with:

```php
    Route::get('/employees', \App\Livewire\Employees\Index::class)->name('employees.index');
```

- [ ] **Step 3.2: Run.**

Run: `vendor/bin/pint --dirty --format agent && php artisan test --compact --filter='Livewire/Employees/IndexTest'`
Expected: PASS.

- [ ] **Step 3.3: Delete dead code.**

Delete:
- `app/Http/Controllers/Employee/`
- `app/Http/Requests/Employee/`
- `resources/views/employees/`
- `tests/Feature/EmployeeCrudTest.php`

- [ ] **Step 3.4: Run the full suite.**

Run: `php artisan test --compact`
Expected: PASS.

### Step 4: Smoke-test via Playwright MCP

- [ ] **Step 4.1:** `/employees` — list renders, search debounces and updates URL (look for `?search=...`), department filter narrows results and updates URL, "Clear" resets both. Modal create with empty form errors on each required field. Create succeeds, edit hydrates, delete blocked when employee has payroll records (create one via tinker beforehand or rely on factories), succeeds otherwise.

### Step 5: Hand off to user for commit

- [ ] **Step 5.1: Suggested commit message:** `refactor: convert employees crud to livewire with modal`.

---

## Task 5: Payroll\Run

Form-style component. Uses the existing `PayrollCalculator` service and `PayrollRecord` chunked-create logic verbatim — only the request layer changes.

**Files:**
- Create: `app/Livewire/Payroll/Run.php`
- Create: `resources/views/livewire/payroll/run.blade.php`
- Modify: `routes/web.php`
- Create: `tests/Feature/Livewire/Payroll/RunTest.php`
- Delete (at end): `app/Http/Controllers/Payroll/PayrollController.php`, `app/Http/Requests/Payroll/RunPayrollRequest.php`, `resources/views/payroll/run.blade.php`

### Step 1: Failing test

- [ ] **Step 1.1: Create `tests/Feature/Livewire/Payroll/RunTest.php`:**

```php
<?php

use App\Livewire\Payroll\Run;
use App\Models\Employee;
use App\Models\PayrollRecord;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(fn () => actingAs(User::factory()->create()));

it('defaults month/year to now', function () {
    $now = now();
    Livewire::test(Run::class)
        ->assertSet('month', $now->month)
        ->assertSet('year', $now->year);
});

it('validates month and year ranges', function () {
    Livewire::test(Run::class)
        ->set('month', 13)
        ->set('year', 1900)
        ->call('process')
        ->assertHasErrors(['month' => 'between', 'year' => 'between']);
});

it('processes payroll for all employees and redirects to history', function () {
    Employee::factory()->count(2)->create();

    Livewire::test(Run::class)
        ->set('month', 6)
        ->set('year', 2026)
        ->call('process')
        ->assertRedirect(route('payroll.history', ['month' => 6, 'year' => 2026]));

    expect(PayrollRecord::where('month', 6)->where('year', 2026)->count())->toBe(2);
});

it('skips employees that already have a record for the period', function () {
    $emp = Employee::factory()->create();
    PayrollRecord::factory()->create([
        'employee_id' => $emp->id,
        'month' => 6,
        'year' => 2026,
    ]);

    Livewire::test(Run::class)
        ->set('month', 6)
        ->set('year', 2026)
        ->call('process');

    expect(PayrollRecord::where('employee_id', $emp->id)->where('month', 6)->where('year', 2026)->count())->toBe(1);
});
```

- [ ] **Step 1.2: Run.** Expected FAIL (class missing).

### Step 2: Component

- [ ] **Step 2.1:** `php artisan make:livewire Payroll/Run --no-interaction`

- [ ] **Step 2.2: Replace `app/Livewire/Payroll/Run.php`** with:

```php
<?php

namespace App\Livewire\Payroll;

use App\Models\Employee;
use App\Models\PayrollRecord;
use App\Services\Payroll\PayrollCalculator;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Run payroll')]
class Run extends Component
{
    public int $month;

    public int $year;

    public function mount(): void
    {
        $now = now();
        $this->month = $now->month;
        $this->year = $now->year;
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'month' => ['required', 'integer', 'between:1,12'],
            'year' => ['required', 'integer', 'between:2000,2100'],
        ];
    }

    public function process(PayrollCalculator $calculator)
    {
        $this->validate();

        $month = $this->month;
        $year = $this->year;

        $processed = 0;
        $skipped = 0;

        DB::transaction(function () use ($calculator, $month, $year, &$processed, &$skipped) {
            $existingEmployeeIds = PayrollRecord::query()
                ->where('month', $month)
                ->where('year', $year)
                ->pluck('employee_id')
                ->all();

            Employee::query()->chunkById(200, function ($employees) use ($calculator, $month, $year, $existingEmployeeIds, &$processed, &$skipped) {
                foreach ($employees as $employee) {
                    if (in_array($employee->id, $existingEmployeeIds, true)) {
                        $skipped++;

                        continue;
                    }

                    $breakdown = $calculator->calculate($employee);

                    PayrollRecord::create([
                        'employee_id' => $employee->id,
                        'month' => $month,
                        'year' => $year,
                        ...$breakdown->toPersistableArray(),
                    ]);
                    $processed++;
                }
            });
        });

        session()->flash('status', "Payroll processed: {$processed} new, {$skipped} skipped (already existed).");

        return $this->redirect(route('payroll.history', ['month' => $month, 'year' => $year]), navigate: true);
    }

    public function render()
    {
        return view('livewire.payroll.run');
    }
}
```

- [ ] **Step 2.3: Replace `resources/views/livewire/payroll/run.blade.php`** with:

```blade
<div>
    <h1 class="mb-6 text-2xl font-semibold tracking-tight text-slate-900">Run payroll</h1>

    <div class="max-w-lg rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        <p class="mb-4 text-sm text-slate-500">Select the period below. The system processes <strong>all employees</strong> for that month and year. Existing records for the same period are skipped.</p>
        <form
            x-data
            x-on:submit.prevent="
                Swal.fire({
                    title: 'Run payroll?',
                    text: 'This will create payroll records for all employees for the selected period.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#0f172a',
                    confirmButtonText: 'Yes',
                }).then(result => { if (result.isConfirmed) $wire.process(); });
            "
            class="space-y-5">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="month" class="block text-sm font-medium text-slate-700">Month</label>
                    <select id="month" wire:model="month"
                            class="mt-1 block w-full rounded-md border-0 px-3 py-2 text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-slate-900">
                        @foreach (range(1, 12) as $m)
                            <option value="{{ $m }}">{{ \Carbon\Carbon::create()->month($m)->format('F') }}</option>
                        @endforeach
                    </select>
                    @error('month')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="year" class="block text-sm font-medium text-slate-700">Year</label>
                    <input id="year" type="number" wire:model="year" min="2000" max="2100"
                           class="mt-1 block w-full rounded-md border-0 px-3 py-2 text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-slate-900">
                    @error('year')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="flex justify-end">
                <button type="submit"
                        class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-800">
                    Process payroll
                </button>
            </div>
        </form>
    </div>
</div>
```

### Step 3: Routes & cleanup

- [ ] **Step 3.1: Edit `routes/web.php`:** replace `payroll.run` and `payroll.process` controller routes with:

```php
    Route::get('/payroll/run', \App\Livewire\Payroll\Run::class)->name('payroll.run');
```

The `payroll.process` route is gone — the component handles processing internally; nothing else references that name.

- [ ] **Step 3.2: Run tests.**

Run: `vendor/bin/pint --dirty --format agent && php artisan test --compact --filter='Livewire/Payroll/RunTest'`
Expected: PASS.

- [ ] **Step 3.3: Delete dead code.**

Delete:
- `app/Http/Controllers/Payroll/PayrollController.php`
- `app/Http/Requests/Payroll/RunPayrollRequest.php`
- `resources/views/payroll/run.blade.php`

Note: the old `tests/Feature/PayrollProcessingTest.php` likely still posts to `payroll.process`. Check it now — if so, port the relevant assertions into `RunTest.php` and delete the old file. If it tests pure service logic, leave it.

- [ ] **Step 3.4:** `php artisan test --compact` — full suite green.

### Step 4: Smoke-test via Playwright MCP

- [ ] **Step 4.1:** `/payroll/run` — defaults to current month/year, change to a future month, click "Process payroll" → SweetAlert2 confirm → on confirm, lands on `/payroll/history?month=...&year=...` with status flash showing "Payroll processed: N new, M skipped".

### Step 5: Hand off

- [ ] **Step 5.1: Suggested commit message:** `refactor: convert payroll run to livewire component`.

---

## Task 6: Payroll\History

`WithPagination`, `#[Url]` for `search`/`month`/`year`/`departmentId`, links to payslip with `wire:navigate`.

**Files:**
- Create: `app/Livewire/Payroll/History.php`
- Create: `resources/views/livewire/payroll/history.blade.php`
- Modify: `routes/web.php`
- Create: `tests/Feature/Livewire/Payroll/HistoryTest.php`
- Delete: `app/Http/Controllers/Payroll/PayrollHistoryController.php`, `resources/views/payroll/history.blade.php`

### Step 1: Failing test

- [ ] **Step 1.1: Create `tests/Feature/Livewire/Payroll/HistoryTest.php`:**

```php
<?php

use App\Livewire\Payroll\History;
use App\Models\Department;
use App\Models\Employee;
use App\Models\PayrollRecord;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(fn () => actingAs(User::factory()->create()));

it('lists records sorted by year/month desc', function () {
    $emp = Employee::factory()->create(['name' => 'Alice']);
    PayrollRecord::factory()->create(['employee_id' => $emp->id, 'month' => 1, 'year' => 2026]);
    PayrollRecord::factory()->create(['employee_id' => $emp->id, 'month' => 6, 'year' => 2026]);

    Livewire::test(History::class)
        ->assertSeeInOrder(['June 2026', 'January 2026']);
});

it('filters by month and year', function () {
    $emp = Employee::factory()->create(['name' => 'Alice']);
    PayrollRecord::factory()->create(['employee_id' => $emp->id, 'month' => 1, 'year' => 2026]);
    PayrollRecord::factory()->create(['employee_id' => $emp->id, 'month' => 6, 'year' => 2026]);

    Livewire::test(History::class)
        ->set('month', 6)
        ->set('year', 2026)
        ->assertSee('June 2026')
        ->assertDontSee('January 2026');
});

it('filters by department', function () {
    $deptA = Department::factory()->create();
    $deptB = Department::factory()->create();
    $a = Employee::factory()->create(['name' => 'AlphaPerson', 'department_id' => $deptA->id]);
    $b = Employee::factory()->create(['name' => 'BetaPerson', 'department_id' => $deptB->id]);
    PayrollRecord::factory()->create(['employee_id' => $a->id]);
    PayrollRecord::factory()->create(['employee_id' => $b->id]);

    Livewire::test(History::class)
        ->set('departmentId', $deptA->id)
        ->assertSee('AlphaPerson')
        ->assertDontSee('BetaPerson');
});

it('searches by employee name', function () {
    $a = Employee::factory()->create(['name' => 'Alice']);
    $b = Employee::factory()->create(['name' => 'Bob']);
    PayrollRecord::factory()->create(['employee_id' => $a->id]);
    PayrollRecord::factory()->create(['employee_id' => $b->id]);

    Livewire::test(History::class)
        ->set('search', 'Ali')
        ->assertSee('Alice')
        ->assertDontSee('Bob');
});
```

- [ ] **Step 1.2:** `php artisan test --compact --filter='Livewire/Payroll/HistoryTest'` → FAIL.

### Step 2: Component

- [ ] **Step 2.1:** `php artisan make:livewire Payroll/History --no-interaction`

- [ ] **Step 2.2: Replace `app/Livewire/Payroll/History.php`** with:

```php
<?php

namespace App\Livewire\Payroll;

use App\Models\Department;
use App\Models\PayrollRecord;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Payroll history')]
class History extends Component
{
    use WithPagination;

    #[Url(as: 'search')]
    public string $search = '';

    #[Url(as: 'month')]
    public ?int $month = null;

    #[Url(as: 'year')]
    public ?int $year = null;

    #[Url(as: 'department_id')]
    public ?int $departmentId = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingMonth(): void
    {
        $this->resetPage();
    }

    public function updatingYear(): void
    {
        $this->resetPage();
    }

    public function updatingDepartmentId(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'month', 'year', 'departmentId']);
        $this->resetPage();
    }

    #[Computed]
    public function departments()
    {
        return Department::orderBy('name')->get();
    }

    public function render()
    {
        $records = PayrollRecord::query()
            ->with(['employee.department'])
            ->when($this->month, fn ($q, $m) => $q->where('month', $m))
            ->when($this->year, fn ($q, $y) => $q->where('year', $y))
            ->when($this->departmentId, function ($q, $id) {
                $q->whereHas('employee', fn ($eq) => $eq->where('department_id', $id));
            })
            ->when($this->search !== '', function ($q) {
                $q->whereHas('employee', fn ($eq) => $eq->where('name', 'like', "%{$this->search}%"));
            })
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->orderBy('id')
            ->paginate(10);

        return view('livewire.payroll.history', compact('records'));
    }
}
```

- [ ] **Step 2.3: Replace `resources/views/livewire/payroll/history.blade.php`** with:

```blade
<div>
    <div class="mb-6">
        <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Payroll history</h1>
        <p class="mt-1 text-sm text-slate-500">Showing {{ $records->total() }} record(s).</p>
    </div>

    <div class="mb-4 flex flex-wrap items-end gap-3 rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
        <div class="grow min-w-[14rem]">
            <label for="search" class="block text-sm font-medium text-slate-700">Search employee</label>
            <input id="search" type="search" wire:model.live.debounce.300ms="search"
                   placeholder="Name contains…"
                   class="mt-1 block w-full rounded-md border-0 px-3 py-2 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-slate-900">
        </div>
        <div>
            <label for="month" class="block text-sm font-medium text-slate-700">Month</label>
            <select id="month" wire:model.live="month"
                    class="mt-1 rounded-md border-0 px-3 py-2 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-slate-900">
                <option value="">Any</option>
                @foreach (range(1, 12) as $m)
                    <option value="{{ $m }}">{{ \Carbon\Carbon::create()->month($m)->format('F') }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="year" class="block text-sm font-medium text-slate-700">Year</label>
            <input id="year" type="number" wire:model.live.debounce.500ms="year" min="2000" max="2100"
                   class="mt-1 block w-28 rounded-md border-0 px-3 py-2 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-slate-900">
        </div>
        <div>
            <label for="department_id" class="block text-sm font-medium text-slate-700">Department</label>
            <select id="department_id" wire:model.live="departmentId"
                    class="mt-1 rounded-md border-0 px-3 py-2 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-slate-900">
                <option value="">All</option>
                @foreach ($this->departments as $dept)
                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                @endforeach
            </select>
        </div>
        @if ($search !== '' || $month || $year || $departmentId)
            <button type="button" wire:click="clearFilters" class="text-sm font-medium text-slate-600 hover:underline">Clear</button>
        @endif
    </div>

    <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Period</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Employee</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Department</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Gross</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Net</th>
                    <th class="px-4 py-3"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 bg-white">
                @forelse ($records as $record)
                    <tr wire:key="rec-{{ $record->id }}">
                        <td class="px-4 py-3 text-sm text-slate-700">{{ \Carbon\Carbon::create($record->year, $record->month)->format('F Y') }}</td>
                        <td class="px-4 py-3 text-sm font-medium text-slate-900">{{ $record->employee->name }}</td>
                        <td class="px-4 py-3 text-sm text-slate-600">{{ $record->employee->department->name }}</td>
                        <td class="px-4 py-3 text-right text-sm tabular-nums text-slate-700">RM {{ number_format((float) $record->gross_pay, 2) }}</td>
                        <td class="px-4 py-3 text-right text-sm tabular-nums font-medium text-slate-900">RM {{ number_format((float) $record->net_pay, 2) }}</td>
                        <td class="px-4 py-3 text-right text-sm">
                            <a href="{{ route('payroll.payslip', $record) }}" wire:navigate
                               title="View payslip"
                               aria-label="View payslip for {{ $record->employee->name }}"
                               class="inline-flex items-center justify-center rounded-md p-2 text-slate-600 hover:bg-slate-100 hover:text-slate-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-slate-900">
                                <i class="fa-regular fa-eye" aria-hidden="true"></i>
                                <span class="sr-only">View payslip</span>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-sm text-slate-500">No payroll records match these filters.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $records->links() }}</div>
</div>
```

### Step 3: Routes, cleanup, full suite

- [ ] **Step 3.1: Edit `routes/web.php`:** replace `payroll.history` controller route with:

```php
    Route::get('/payroll/history', \App\Livewire\Payroll\History::class)->name('payroll.history');
```

- [ ] **Step 3.2:** `vendor/bin/pint --dirty --format agent && php artisan test --compact --filter='Livewire/Payroll/HistoryTest'` → PASS.

- [ ] **Step 3.3: Delete dead code.**

Delete:
- `app/Http/Controllers/Payroll/PayrollHistoryController.php`
- `resources/views/payroll/history.blade.php`

- [ ] **Step 3.4:** `php artisan test --compact` → PASS.

### Step 4: Playwright smoke

- [ ] **Step 4.1:** `/payroll/history` — list renders, search/month/year/department filters narrow results live and update URL, "Clear" wipes them, clicking the eye icon `wire:navigate`s to the payslip page.

### Step 5: Hand off

- [ ] **Step 5.1: Commit message:** `refactor: convert payroll history to livewire`.

---

## Task 7: Payroll\Payslip

Single-record detail view, route-model bound. The `_payslip-card` partial stays untouched.

**Files:**
- Create: `app/Livewire/Payroll/Payslip.php`
- Create: `resources/views/livewire/payroll/payslip.blade.php`
- Modify: `routes/web.php`
- Modify: `app/Http/Controllers/Payroll/PayslipController.php` (drop `show`, keep `pdf`)
- Create: `tests/Feature/Livewire/Payroll/PayslipTest.php`
- Delete: `resources/views/payroll/payslip.blade.php`

### Step 1: Failing test

- [ ] **Step 1.1: Create `tests/Feature/Livewire/Payroll/PayslipTest.php`:**

```php
<?php

use App\Livewire\Payroll\Payslip;
use App\Models\Employee;
use App\Models\PayrollRecord;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

it('renders the payslip card and a PDF download link for the bound record', function () {
    actingAs(User::factory()->create());
    $emp = Employee::factory()->create(['name' => 'Alice']);
    $record = PayrollRecord::factory()->create([
        'employee_id' => $emp->id,
        'month' => 6,
        'year' => 2026,
    ]);

    Livewire::test(Payslip::class, ['record' => $record])
        ->assertSee('Alice')
        ->assertSee(route('payroll.payslip.pdf', $record));
});
```

- [ ] **Step 1.2:** Run → FAIL (class missing).

### Step 2: Component

- [ ] **Step 2.1:** `php artisan make:livewire Payroll/Payslip --no-interaction`

- [ ] **Step 2.2: Replace `app/Livewire/Payroll/Payslip.php`** with:

```php
<?php

namespace App\Livewire\Payroll;

use App\Models\PayrollRecord;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Payslip')]
class Payslip extends Component
{
    public PayrollRecord $record;

    public function mount(PayrollRecord $record): void
    {
        $this->record = $record->load('employee.department');
    }

    public function render()
    {
        return view('livewire.payroll.payslip');
    }
}
```

- [ ] **Step 2.3: Replace `resources/views/livewire/payroll/payslip.blade.php`** with:

```blade
<div>
    <div class="mb-4 flex items-center justify-between">
        <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Payslip</h1>
        <a href="{{ route('payroll.payslip.pdf', $record) }}"
           class="rounded-md bg-slate-900 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-800">
            Download PDF
        </a>
    </div>

    @include('payroll._payslip-card', ['record' => $record])

    <div class="mt-4">
        <a href="{{ route('payroll.history') }}" wire:navigate class="text-sm font-medium text-slate-600 hover:underline">&larr; Back to history</a>
    </div>
</div>
```

The PDF download link does **not** have `wire:navigate` (binary download).

### Step 3: Routes & cleanup

- [ ] **Step 3.1: Edit `routes/web.php`:** replace the `payroll.payslip` controller route with:

```php
    Route::get('/payroll/{record}/payslip', \App\Livewire\Payroll\Payslip::class)->name('payroll.payslip');
```

The `payroll.payslip.pdf` controller route stays.

- [ ] **Step 3.2: Trim `app/Http/Controllers/Payroll/PayslipController.php`** to just the PDF action:

```php
<?php

namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\Controller;
use App\Models\PayrollRecord;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class PayslipController extends Controller
{
    public function pdf(PayrollRecord $record): Response
    {
        $record->load('employee.department');

        $filename = sprintf(
            'payslip-%s-%04d-%02d.pdf',
            str()->slug($record->employee->name),
            $record->year,
            $record->month,
        );

        return Pdf::loadView('payroll.payslip-pdf', ['record' => $record])
            ->setPaper('a4')
            ->download($filename);
    }
}
```

- [ ] **Step 3.3:** `vendor/bin/pint --dirty --format agent && php artisan test --compact --filter='Livewire/Payroll/PayslipTest'` → PASS.

- [ ] **Step 3.4: Delete `resources/views/payroll/payslip.blade.php`.** Keep `_payslip-card.blade.php` and `payslip-pdf.blade.php`.

- [ ] **Step 3.5:** `php artisan test --compact` → PASS.

### Step 4: Playwright smoke

- [ ] **Step 4.1:** From `/payroll/history`, click the eye on a record → lands on `/payroll/{id}/payslip` (URL stays the same as before). Card renders with employee details. "Download PDF" downloads a file. "Back to history" `wire:navigate`s back.

### Step 5: Hand off

- [ ] **Step 5.1: Commit message:** `refactor: convert payslip view to livewire (pdf controller stays)`.

---

## Task 8: Auth\Login

Includes throttling identical to today's controller behavior.

**Files:**
- Create: `app/Livewire/Auth/Login.php`
- Create: `resources/views/livewire/auth/login.blade.php`
- Modify: `routes/web.php`
- Create: `tests/Feature/Livewire/Auth/LoginTest.php`
- Delete: lines from `app/Http/Controllers/Auth/LoginController.php` (entire file at end of Task 9), `resources/views/auth/login.blade.php`

### Step 1: Failing test

- [ ] **Step 1.1: Create `tests/Feature/Livewire/Auth/LoginTest.php`:**

```php
<?php

use App\Livewire\Auth\Login;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

it('logs in with valid credentials and redirects to dashboard', function () {
    $user = User::factory()->create([
        'email' => 'user@example.com',
        'password' => Hash::make('secret123'),
    ]);

    Livewire::test(Login::class)
        ->set('email', 'user@example.com')
        ->set('password', 'secret123')
        ->call('submit')
        ->assertRedirect(route('dashboard'));

    expect(auth()->id())->toBe($user->id);
});

it('rejects invalid credentials with an error', function () {
    User::factory()->create(['email' => 'user@example.com', 'password' => Hash::make('secret123')]);

    Livewire::test(Login::class)
        ->set('email', 'user@example.com')
        ->set('password', 'wrong')
        ->call('submit')
        ->assertHasErrors('email');

    expect(auth()->check())->toBeFalse();
});

it('throttles after 5 failed attempts', function () {
    User::factory()->create(['email' => 'user@example.com', 'password' => Hash::make('secret123')]);
    $key = strtolower('user@example.com').'|127.0.0.1';
    for ($i = 0; $i < 5; $i++) {
        RateLimiter::hit($key);
    }

    Livewire::test(Login::class)
        ->set('email', 'user@example.com')
        ->set('password', 'secret123')
        ->call('submit')
        ->assertHasErrors('email');
});

it('validates required fields', function () {
    Livewire::test(Login::class)
        ->call('submit')
        ->assertHasErrors(['email' => 'required', 'password' => 'required']);
});
```

- [ ] **Step 1.2:** Run → FAIL.

### Step 2: Component

- [ ] **Step 2.1:** `php artisan make:livewire Auth/Login --no-interaction`

- [ ] **Step 2.2: Replace `app/Livewire/Auth/Login.php`** with:

```php
<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.guest')]
#[Title('Log in')]
class Login extends Component
{
    #[Validate('required|email')]
    public string $email = '';

    #[Validate('required')]
    public string $password = '';

    public bool $remember = false;

    public function submit()
    {
        $this->validate();

        $key = $this->throttleKey();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            $this->addError('email', "Too many login attempts. Try again in {$seconds} seconds.");

            return null;
        }

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($key, 60);
            $this->addError('email', 'These credentials do not match our records.');

            return null;
        }

        RateLimiter::clear($key);
        request()->session()->regenerate();

        return $this->redirectIntended(route('dashboard'), navigate: true);
    }

    private function throttleKey(): string
    {
        return strtolower($this->email).'|'.request()->ip();
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}
```

- [ ] **Step 2.3: Replace `resources/views/livewire/auth/login.blade.php`** with:

```blade
<div>
    <h2 class="mb-6 text-xl font-semibold text-slate-900">Log in to your account</h2>

    <form wire:submit="submit" class="space-y-5" novalidate>
        <div>
            <label for="email" class="block text-sm font-medium text-slate-700">Email</label>
            <input id="email" type="email" wire:model="email" required autofocus autocomplete="email"
                   class="mt-1 block w-full rounded-md border-0 px-3 py-2 text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-slate-900">
            @error('email')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-slate-700">Password</label>
            <input id="password" type="password" wire:model="password" required autocomplete="current-password"
                   class="mt-1 block w-full rounded-md border-0 px-3 py-2 text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-slate-900">
            @error('password')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
        </div>

        <div class="flex items-center">
            <input id="remember" type="checkbox" wire:model="remember"
                   class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900">
            <label for="remember" class="ml-2 block text-sm text-slate-700">Remember me</label>
        </div>

        <button type="submit" class="flex w-full justify-center rounded-md bg-slate-900 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-800 focus-visible:outline focus-visible:outline-offset-2 focus-visible:outline-slate-900">
            Log in
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-500">
        Don't have an account?
        <a href="{{ route('register') }}" wire:navigate class="font-medium text-slate-900 hover:underline">Register</a>
    </p>
</div>
```

### Step 3: Routes & verify

- [ ] **Step 3.1: Edit `routes/web.php`:** in the `guest` middleware group, replace the two `login` controller routes with:

```php
    Route::get('/login', \App\Livewire\Auth\Login::class)->name('login');
```

(The old `login.store` POST route is gone — Livewire handles submission. The old `route('login.store')` is no longer referenced because the new view uses `wire:submit`.)

- [ ] **Step 3.2: Run.**

Run: `vendor/bin/pint --dirty --format agent && php artisan test --compact --filter='Livewire/Auth/LoginTest'`
Expected: PASS.

- [ ] **Step 3.3:** Delete `resources/views/auth/login.blade.php`.

- [ ] **Step 3.4: Run the full suite.**

Run: `php artisan test --compact`
Expected: PASS. Note: `tests/Feature/Auth/AuthFlowTest.php` may fail because it posts to `route('login.store')` which no longer exists. If so, port relevant assertions into `LoginTest.php` (or `RegisterTest.php` in Task 9) and delete the orphaned test sections.

### Step 4: Playwright smoke

- [ ] **Step 4.1:** Visit `/login`, submit empty (errors render), submit wrong password (error renders), submit correct credentials → land on `/dashboard`. Verify that intended URL is honored: visit `/employees` while logged out (should redirect to login), submit correct creds, should land on `/employees`.

### Step 5: Hand off

- [ ] **Step 5.1: Commit message:** `refactor: convert login to livewire component with throttling`.

---

## Task 9: Auth\Register

**Files:**
- Create: `app/Livewire/Auth/Register.php`
- Create: `resources/views/livewire/auth/register.blade.php`
- Modify: `routes/web.php`
- Create: `tests/Feature/Livewire/Auth/RegisterTest.php`
- Delete: `resources/views/auth/register.blade.php`

### Step 1: Failing test

- [ ] **Step 1.1: Create `tests/Feature/Livewire/Auth/RegisterTest.php`:**

```php
<?php

use App\Livewire\Auth\Register;
use App\Models\User;
use Livewire\Livewire;

it('creates a user, logs in, and redirects to dashboard', function () {
    Livewire::test(Register::class)
        ->set('name', 'New Person')
        ->set('email', 'new@example.com')
        ->set('password', 'password123')
        ->set('password_confirmation', 'password123')
        ->call('submit')
        ->assertRedirect(route('dashboard'));

    expect(User::where('email', 'new@example.com')->exists())->toBeTrue();
    expect(auth()->check())->toBeTrue();
});

it('rejects mismatched password confirmation', function () {
    Livewire::test(Register::class)
        ->set('name', 'New Person')
        ->set('email', 'new@example.com')
        ->set('password', 'password123')
        ->set('password_confirmation', 'different')
        ->call('submit')
        ->assertHasErrors(['password' => 'confirmed']);
});

it('rejects duplicate email', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    Livewire::test(Register::class)
        ->set('name', 'New Person')
        ->set('email', 'taken@example.com')
        ->set('password', 'password123')
        ->set('password_confirmation', 'password123')
        ->call('submit')
        ->assertHasErrors(['email' => 'unique']);
});

it('rejects short password', function () {
    Livewire::test(Register::class)
        ->set('name', 'New Person')
        ->set('email', 'new@example.com')
        ->set('password', 'short')
        ->set('password_confirmation', 'short')
        ->call('submit')
        ->assertHasErrors(['password' => 'min']);
});
```

- [ ] **Step 1.2:** Run → FAIL.

### Step 2: Component

- [ ] **Step 2.1:** `php artisan make:livewire Auth/Register --no-interaction`

- [ ] **Step 2.2: Replace `app/Livewire/Auth/Register.php`** with:

```php
<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.guest')]
#[Title('Register')]
class Register extends Component
{
    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ];
    }

    public function submit()
    {
        $data = $this->validate();

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);

        Auth::login($user);
        request()->session()->regenerate();

        return $this->redirect(route('dashboard'), navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.register');
    }
}
```

- [ ] **Step 2.3: Replace `resources/views/livewire/auth/register.blade.php`** with:

```blade
<div>
    <h2 class="mb-6 text-xl font-semibold text-slate-900">Create an account</h2>

    <form wire:submit="submit" class="space-y-5" novalidate>
        <div>
            <label for="name" class="block text-sm font-medium text-slate-700">Name</label>
            <input id="name" type="text" wire:model="name" required autofocus autocomplete="name"
                   class="mt-1 block w-full rounded-md border-0 px-3 py-2 text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-slate-900">
            @error('name')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="email" class="block text-sm font-medium text-slate-700">Email</label>
            <input id="email" type="email" wire:model="email" required autocomplete="email"
                   class="mt-1 block w-full rounded-md border-0 px-3 py-2 text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-slate-900">
            @error('email')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-slate-700">Password</label>
            <input id="password" type="password" wire:model="password" required autocomplete="new-password"
                   class="mt-1 block w-full rounded-md border-0 px-3 py-2 text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-slate-900">
            @error('password')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-slate-700">Confirm password</label>
            <input id="password_confirmation" type="password" wire:model="password_confirmation" required autocomplete="new-password"
                   class="mt-1 block w-full rounded-md border-0 px-3 py-2 text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-slate-900">
        </div>

        <button type="submit" class="flex w-full justify-center rounded-md bg-slate-900 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-slate-900">
            Create account
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-500">
        Already registered?
        <a href="{{ route('login') }}" wire:navigate class="font-medium text-slate-900 hover:underline">Log in</a>
    </p>
</div>
```

### Step 3: Routes & verify

- [ ] **Step 3.1: Edit `routes/web.php`:** in the `guest` middleware group, replace the two `register` controller routes with:

```php
    Route::get('/register', \App\Livewire\Auth\Register::class)->name('register');
```

- [ ] **Step 3.2:** `vendor/bin/pint --dirty --format agent && php artisan test --compact --filter='Livewire/Auth/RegisterTest'` → PASS.

- [ ] **Step 3.3: Delete `resources/views/auth/register.blade.php`.**

- [ ] **Step 3.4: Run the full suite.**

Run: `php artisan test --compact`
Expected: PASS. Same caveat as Task 8 about `AuthFlowTest.php`.

### Step 4: Playwright smoke

- [ ] **Step 4.1:** Visit `/register`, submit empty (4 errors), with mismatched confirmation, with short password, with valid input → registers and lands on `/dashboard`.

### Step 5: Hand off

- [ ] **Step 5.1: Commit message:** `refactor: convert register to livewire component`.

---

## Task 10: Cleanup Pass

Removes the layout shim, the now-orphaned `LoginController`, the `auth` views directory, and the orphaned `AuthFlowTest`. Final route-list audit.

**Files:**
- Modify: `resources/views/layouts/app.blade.php` (remove `@yield('content')`)
- Modify: `resources/views/layouts/guest.blade.php` (remove `@yield('content')`)
- Modify: `routes/web.php` (remove `LoginController`/`RegisterController` references)
- Delete: `app/Http/Controllers/Auth/` (entire folder)
- Delete: `app/Http/Requests/Auth/` (entire folder)
- Delete: `resources/views/auth/` (folder, now empty)
- Delete or rewrite: `tests/Feature/Auth/AuthFlowTest.php` if it still references removed routes

### Step 1: Logout — replace controller route with Livewire button only

The `<livewire:auth.logout-button />` component handles logout entirely; it does not use the `route('logout')` POST route. Remove the route.

- [ ] **Step 1.1: Edit `routes/web.php`** — remove these lines from the `auth` middleware group:

```php
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
```

…and remove the `use` imports for `LoginController` and `RegisterController` at the top, since nothing else references them.

- [ ] **Step 1.2: Verify nothing else calls `route('logout')`** by searching the codebase.

Run: `grep -RIn "route('logout')\|route(\"logout\")" app/ resources/ routes/ tests/ || true`
Expected: no matches (the layout no longer uses it; the LogoutButton component calls `Auth::logout()` directly).

### Step 2: Remove the layout shim

The `{{ $slot ?? '' }}` shim added in Task 1 supported the gradual conversion. Every page is now a Livewire component, so `@yield('content')` is dead.

- [ ] **Step 2.1: Edit `resources/views/layouts/app.blade.php`** — find this block:

```blade
            @yield('content')
            {{ $slot ?? '' }}
```

…and replace it with:

```blade
            {{ $slot }}
```

- [ ] **Step 2.2: Edit `resources/views/layouts/guest.blade.php`** — same replacement inside the card div:

Replace:

```blade
                @yield('content')
                {{ $slot ?? '' }}
```

with:

```blade
                {{ $slot }}
```

### Step 3: Delete dead controllers, requests, and views

- [ ] **Step 3.1:** Delete `app/Http/Controllers/Auth/` (whole folder — `LoginController.php` and `RegisterController.php`).

- [ ] **Step 3.2:** Delete `app/Http/Requests/Auth/` (whole folder — `LoginRequest.php` and `RegisterRequest.php`).

- [ ] **Step 3.3:** Verify `app/Http/Controllers/Payroll/` contains only `PayslipController.php`. If `PayrollController.php` or `PayrollHistoryController.php` are still present, delete them now (they should have been deleted in Tasks 5–6).

- [ ] **Step 3.4:** Delete `resources/views/auth/` (should be empty — login/register views were deleted in Tasks 8–9). If anything remains, audit and delete.

- [ ] **Step 3.5:** Verify `app/Http/Requests/` is empty (or contains only directories you explicitly want to keep). Check `app/Http/Requests/Department/`, `app/Http/Requests/Employee/`, `app/Http/Requests/Payroll/` — all should be gone.

Run: `ls app/Http/Requests/ app/Http/Controllers/`
Expected: `Requests/` empty or absent; `Controllers/` contains only `Controller.php` and `Payroll/PayslipController.php`.

### Step 4: Resolve orphaned tests

- [ ] **Step 4.1: Inspect `tests/Feature/Auth/AuthFlowTest.php`.** If it posts to `route('login.store')`, `route('register.store')`, or `route('logout')`, those routes no longer exist. The new `LoginTest.php` and `RegisterTest.php` cover the same flows at the component level. Delete `tests/Feature/Auth/AuthFlowTest.php`.

- [ ] **Step 4.2: Inspect `tests/Feature/PayrollProcessingTest.php`.** If it asserts service-level behavior (`PayrollCalculator` math), keep it. If it posts to `payroll.process`, port assertions into `tests/Feature/Livewire/Payroll/RunTest.php` and delete the orphan.

- [ ] **Step 4.3: Run the full suite.**

Run: `vendor/bin/pint --dirty --format agent && php artisan test --compact`
Expected: all tests pass.

### Step 5: Final route-list audit

- [ ] **Step 5.1: List routes.**

Run: `php artisan route:list --except-vendor`
Expected: only the routes listed below appear (plus framework defaults like `/` and any Boost/dev routes):

- `GET  /` → redirect to dashboard
- `GET  /demo` → counter (existing)
- `GET  /login` → `Auth\Login`
- `GET  /register` → `Auth\Register`
- `GET  /dashboard` → `Dashboard`
- `GET  /departments` → `Departments\Index`
- `GET  /employees` → `Employees\Index`
- `GET  /payroll/run` → `Payroll\Run`
- `GET  /payroll/history` → `Payroll\History`
- `GET  /payroll/{record}/payslip` → `Payroll\Payslip`
- `GET  /payroll/{record}/payslip.pdf` → `PayslipController@pdf`

If any `POST`/`PUT`/`DELETE` routes for `departments`, `employees`, `login`, `register`, `logout`, `payroll.process` remain, something was missed — track it down before declaring the task done.

### Step 6: End-to-end Playwright smoke

- [ ] **Step 6.1: Full path through the app.**

1. Log out (if logged in). On `/login` register a new user via the link → land on `/dashboard`.
2. Log out via the topbar (SweetAlert2 confirm).
3. Log back in.
4. Click each topbar link in turn — verify all are `wire:navigate` (no full reload).
5. Departments: create, edit, attempt delete on one with employees (blocked), delete one without.
6. Employees: search, filter by department, create, edit, delete.
7. Run payroll for a future month (where no records exist yet) — confirm SweetAlert2, watch for redirect to history with status flash.
8. History: filter by month/year/department/search — URL updates live.
9. Click into a payslip → "Download PDF" downloads the file → "Back to history" `wire:navigate`s back.

Capture a screenshot of the dashboard after the loop.

### Step 7: Hand off

- [ ] **Step 7.1: Files for commit:** `resources/views/layouts/app.blade.php`, `resources/views/layouts/guest.blade.php`, `routes/web.php`, deletions of `app/Http/Controllers/Auth/`, `app/Http/Requests/Auth/` (and any leftover requests folders), `resources/views/auth/`, `tests/Feature/Auth/AuthFlowTest.php` (and any other orphaned tests).

Suggested commit message: `refactor: drop legacy controllers, requests, and layout shim`.

---

## Self-review notes (author → executor)

- **Spec coverage:** All nine components in §3 of the spec map to a task (Tasks 1–9), the cleanup pass in §10 maps to Task 10, the SPA navigation in §5 lands in Task 1, and the PDF carve-out in §3 is preserved by Task 7.
- **Naming consistency:** `Departments\Index`, `Employees\Index`, `Payroll\Run/History/Payslip`, `Auth\Login/Register/LogoutButton`, `Dashboard`. Action names: `openCreate`/`openEdit`/`closeForm`/`save`/`delete` on index components, `submit` on auth/payroll-run, `process` on payroll-run, `logout` on LogoutButton.
- **One subtle naming split:** `Employees\Index` has both a list-filter `$departmentId` (URL-bound) and a form-bound `$formDepartmentId`. They cannot share a name — Task 4 Step 2.2 calls this out and instructs the executor to update the test it just wrote in Step 1.1 before declaring the task done.
- **Tests-first:** Each task creates a failing test before any implementation.
- **Git policy:** No `git add`/`commit` steps anywhere — every task ends with a "Hand off to user for commit" listing files. The user will commit manually.
