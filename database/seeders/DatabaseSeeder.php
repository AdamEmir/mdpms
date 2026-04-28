<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@mdpms.test'],
            ['name' => 'Admin', 'password' => 'password'],
        );

        $coreDepartments = [
            'Engineering', 'Finance', 'Human Resources', 'Sales',
            'Marketing', 'Operations', 'Product', 'Customer Support',
            'Legal', 'Research',
        ];

        foreach ($coreDepartments as $name) {
            Department::firstOrCreate(['name' => $name]);
        }

        if (Employee::count() === 0) {
            $engineering = Department::where('name', 'Engineering')->firstOrFail();

            // Spec example — verify the formula reproduces 3928.50 net pay.
            Employee::create([
                'department_id' => $engineering->id,
                'name' => 'Siti Aminah',
                'position' => 'Software Engineer',
                'basic_salary' => 4000.00,
                'allowance' => 600.00,
                'overtime_hours' => 10,
                'hourly_rate' => 25.00,
            ]);

            Department::all()->each(function (Department $department) {
                Employee::factory()
                    ->count(fake()->numberBetween(4, 7))
                    ->create(['department_id' => $department->id]);
            });
        }
    }
}
