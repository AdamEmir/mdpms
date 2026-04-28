<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'department_id' => Department::factory(),
            'name' => fake()->name(),
            'position' => fake()->jobTitle(),
            'basic_salary' => fake()->randomFloat(2, 2500, 8000),
            'allowance' => fake()->randomFloat(2, 0, 1000),
            'overtime_hours' => fake()->numberBetween(0, 30),
            'hourly_rate' => fake()->randomFloat(2, 15, 40),
        ];
    }
}
