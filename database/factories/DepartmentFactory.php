<?php

namespace Database\Factories;

use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Department>
 */
class DepartmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement([
                'Engineering', 'Finance', 'Human Resources', 'Sales', 'Marketing',
                'Operations', 'Product', 'Customer Support', 'Legal', 'Research',
            ]).' '.fake()->unique()->numberBetween(1, 999999),
        ];
    }
}
