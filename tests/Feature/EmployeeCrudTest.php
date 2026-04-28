<?php

use App\Models\Department;
use App\Models\Employee;
use App\Models\PayrollRecord;
use App\Models\User;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('lists and filters employees by department', function () {
    $deptA = Department::factory()->create(['name' => 'Alpha']);
    $deptB = Department::factory()->create(['name' => 'Bravo']);
    Employee::factory()->create(['department_id' => $deptA->id, 'name' => 'In Alpha']);
    Employee::factory()->create(['department_id' => $deptB->id, 'name' => 'In Bravo']);

    $this->get(route('employees.index'))
        ->assertOk()
        ->assertSeeText('In Alpha')
        ->assertSeeText('In Bravo');

    $this->get(route('employees.index', ['department_id' => $deptA->id]))
        ->assertOk()
        ->assertSeeText('In Alpha')
        ->assertDontSeeText('In Bravo');
});

it('creates an employee', function () {
    $department = Department::factory()->create();

    $this->post(route('employees.store'), [
        'department_id' => $department->id,
        'name' => 'Alex Test',
        'position' => 'Developer',
        'basic_salary' => 4000,
        'allowance' => 600,
        'overtime_hours' => 10,
        'hourly_rate' => 25,
    ])->assertRedirect(route('employees.index'));

    expect(Employee::where('name', 'Alex Test')->exists())->toBeTrue();
});

it('updates an employee', function () {
    $employee = Employee::factory()->create(['name' => 'Old']);

    $this->put(route('employees.update', $employee), [
        'department_id' => $employee->department_id,
        'name' => 'New Name',
        'position' => $employee->position,
        'basic_salary' => $employee->basic_salary,
        'allowance' => $employee->allowance,
        'overtime_hours' => $employee->overtime_hours,
        'hourly_rate' => $employee->hourly_rate,
    ])->assertRedirect(route('employees.index'));

    expect($employee->fresh()->name)->toBe('New Name');
});

it('deletes an employee with no payroll records', function () {
    $employee = Employee::factory()->create();

    $this->delete(route('employees.destroy', $employee))
        ->assertRedirect(route('employees.index'));

    expect(Employee::find($employee->id))->toBeNull();
});

it('blocks deletion of an employee with payroll records', function () {
    $employee = Employee::factory()->create();
    PayrollRecord::factory()->create(['employee_id' => $employee->id]);

    $this->delete(route('employees.destroy', $employee))
        ->assertRedirect(route('employees.index'))
        ->assertSessionHas('error');

    expect(Employee::find($employee->id))->not->toBeNull();
});
