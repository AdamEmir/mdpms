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
