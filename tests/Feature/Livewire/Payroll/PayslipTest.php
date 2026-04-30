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

it('full-page route shows the payslip view for an authenticated user', function () {
    actingAs(User::factory()->create());
    $emp = Employee::factory()->create(['name' => 'Alice']);
    $record = PayrollRecord::factory()->create(['employee_id' => $emp->id]);

    $this->get(route('payroll.payslip', $record))
        ->assertOk()
        ->assertSee('Alice');
});
