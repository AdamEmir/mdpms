<?php

use App\Models\Department;
use App\Models\Employee;
use App\Models\PayrollRecord;
use App\Models\User;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('processes payroll for all employees and matches the spec example', function () {
    $department = Department::factory()->create(['name' => 'Engineering']);
    $employee = Employee::factory()->create([
        'department_id' => $department->id,
        'basic_salary' => 4000,
        'allowance' => 600,
        'overtime_hours' => 10,
        'hourly_rate' => 25,
    ]);

    $this->post(route('payroll.process'), ['month' => 4, 'year' => 2026])
        ->assertRedirect();

    $record = PayrollRecord::where('employee_id', $employee->id)->firstOrFail();

    expect((float) $record->gross_pay)->toBe(4850.00)
        ->and((float) $record->tax)->toBe(388.00)
        ->and((float) $record->epf_employee)->toBe(533.50)
        ->and((float) $record->epf_employer)->toBe(630.50)
        ->and((float) $record->net_pay)->toBe(3928.50);
});

it('skips employees that already have a record for the same period', function () {
    $employee = Employee::factory()->create();
    PayrollRecord::factory()->create([
        'employee_id' => $employee->id,
        'month' => 4,
        'year' => 2026,
    ]);

    $this->post(route('payroll.process'), ['month' => 4, 'year' => 2026])
        ->assertRedirect();

    expect(PayrollRecord::where('employee_id', $employee->id)
        ->where('month', 4)
        ->where('year', 2026)
        ->count()
    )->toBe(1);
});

it('validates month and year', function () {
    $this->post(route('payroll.process'), ['month' => 13, 'year' => 1999])
        ->assertSessionHasErrors(['month', 'year']);
});

it('shows the payslip view', function () {
    $record = PayrollRecord::factory()->create();

    $this->get(route('payroll.payslip', $record))
        ->assertOk()
        ->assertSeeText('Payslip')
        ->assertSeeText($record->employee->name);
});
