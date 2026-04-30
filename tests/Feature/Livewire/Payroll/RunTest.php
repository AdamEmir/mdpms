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
