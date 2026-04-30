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

it('clearFilters resets every filter and page', function () {
    Livewire::test(History::class)
        ->set('search', 'Ali')
        ->set('month', 6)
        ->set('year', 2026)
        ->set('departmentId', 1)
        ->call('gotoPage', 2)
        ->call('clearFilters')
        ->assertSet('search', '')
        ->assertSet('month', null)
        ->assertSet('year', null)
        ->assertSet('departmentId', null)
        ->assertSet('paginators.page', 1);
});
