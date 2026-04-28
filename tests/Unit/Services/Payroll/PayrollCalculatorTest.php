<?php

use App\Models\Employee;
use App\Services\Payroll\PayrollBreakdown;
use App\Services\Payroll\PayrollCalculator;

beforeEach(function () {
    $this->calculator = new PayrollCalculator;
});

it('matches the assessment spec example exactly', function () {
    $breakdown = $this->calculator->calculateFrom(
        basicSalary: 4000.00,
        allowance: 600.00,
        overtimeHours: 10,
        hourlyRate: 25.00,
    );

    expect($breakdown)->toBeInstanceOf(PayrollBreakdown::class)
        ->and($breakdown->overtimePay)->toBe(250.00)
        ->and($breakdown->grossPay)->toBe(4850.00)
        ->and($breakdown->tax)->toBe(388.00)
        ->and($breakdown->epfEmployee)->toBe(533.50)
        ->and($breakdown->epfEmployer)->toBe(630.50)
        ->and($breakdown->netPay)->toBe(3928.50);
});

it('returns zero overtime when employee logged no overtime hours', function () {
    $breakdown = $this->calculator->calculateFrom(3000, 200, 0, 30);

    expect($breakdown->overtimePay)->toBe(0.0)
        ->and($breakdown->grossPay)->toBe(3200.00);
});

it('handles zero allowance', function () {
    $breakdown = $this->calculator->calculateFrom(2500, 0, 5, 20);

    expect($breakdown->overtimePay)->toBe(100.00)
        ->and($breakdown->grossPay)->toBe(2600.00)
        ->and($breakdown->netPay)->toBe(round(2600 - 208 - 286, 2));
});

it('rounds to two decimal places at each step', function () {
    $breakdown = $this->calculator->calculateFrom(3333.33, 111.11, 7, 12.34);

    expect($breakdown->overtimePay)->toBe(86.38)
        ->and($breakdown->grossPay)->toBe(3530.82)
        ->and($breakdown->tax)->toBe(round(3530.82 * 0.08, 2))
        ->and($breakdown->epfEmployee)->toBe(round(3530.82 * 0.11, 2))
        ->and($breakdown->epfEmployer)->toBe(round(3530.82 * 0.13, 2));
});

it('preserves the gross = basic + allowance + overtime invariant', function (float $basic, float $allowance, int $hours, float $rate) {
    $breakdown = $this->calculator->calculateFrom($basic, $allowance, $hours, $rate);

    expect($breakdown->grossPay)->toBe(round($basic + $allowance + ($hours * $rate), 2));
})->with([
    [4000, 600, 10, 25],
    [5500, 0, 0, 0],
    [2800, 350, 8, 18.5],
    [7200, 1200, 25, 42],
]);

it('uses Employee model attributes when calculating from a model', function () {
    $employee = new Employee([
        'basic_salary' => '4000.00',
        'allowance' => '600.00',
        'overtime_hours' => 10,
        'hourly_rate' => '25.00',
    ]);

    $breakdown = $this->calculator->calculate($employee);

    expect($breakdown->netPay)->toBe(3928.50);
});
