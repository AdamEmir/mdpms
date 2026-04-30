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
    Employee::factory()->create(['name' => 'AlphaPerson', 'department_id' => $deptA->id]);
    Employee::factory()->create(['name' => 'BetaPerson', 'department_id' => $deptB->id]);

    Livewire::test(Index::class)
        ->set('departmentId', $deptA->id)
        ->assertSee('AlphaPerson')
        ->assertDontSee('BetaPerson');
});

it('opens create modal with empty fields', function () {
    Department::factory()->create(['name' => 'Sales']);

    Livewire::test(Index::class)
        ->call('openCreate')
        ->assertSet('showForm', true)
        ->assertSet('editingId', null)
        ->assertSet('name', '')
        ->assertSee('Sales');
});

it('validates required fields on save', function () {
    Livewire::test(Index::class)
        ->call('openCreate')
        ->call('save')
        ->assertHasErrors([
            'name' => 'required',
            'position' => 'required',
            'formDepartmentId' => 'required',
            'basicSalary' => 'required',
        ]);
});

it('creates an employee', function () {
    $dept = Department::factory()->create();

    Livewire::test(Index::class)
        ->call('openCreate')
        ->set('name', 'New Person')
        ->set('position', 'Engineer')
        ->set('formDepartmentId', $dept->id)
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
        ->assertSet('formDepartmentId', $dept->id);
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
});

it('deletes an employee without payroll records', function () {
    $emp = Employee::factory()->create();

    Livewire::test(Index::class)->call('delete', $emp->id);

    expect(Employee::find($emp->id))->toBeNull();
});
