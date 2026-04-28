<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\PayrollRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PayrollRecord>
 */
class PayrollRecordFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $gross = fake()->randomFloat(2, 3000, 9000);
        $tax = round($gross * 0.08, 2);
        $epfEmployee = round($gross * 0.11, 2);
        $epfEmployer = round($gross * 0.13, 2);

        return [
            'employee_id' => Employee::factory(),
            'month' => fake()->numberBetween(1, 12),
            'year' => (int) date('Y'),
            'gross_pay' => $gross,
            'overtime_pay' => fake()->randomFloat(2, 0, 800),
            'tax' => $tax,
            'epf_employee' => $epfEmployee,
            'epf_employer' => $epfEmployer,
            'net_pay' => round($gross - $tax - $epfEmployee, 2),
        ];
    }
}
