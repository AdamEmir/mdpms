<?php

namespace App\Services\Payroll;

use App\Models\Employee;

/**
 * Computes a single employee's monthly payroll using the fixed assessment formula:
 *
 *   Overtime Pay = overtime_hours * hourly_rate
 *   Gross Pay    = basic_salary + allowance + overtime_pay
 *   Tax          = Gross Pay * 0.08
 *   EPF Employee = Gross Pay * 0.11
 *   EPF Employer = Gross Pay * 0.13
 *   Net Pay      = Gross Pay - Tax - EPF Employee
 *
 * All monetary values are rounded to 2 decimal places at each step.
 */
final class PayrollCalculator
{
    public const float TAX_RATE = 0.08;

    public const float EPF_EMPLOYEE_RATE = 0.11;

    public const float EPF_EMPLOYER_RATE = 0.13;

    public function calculate(Employee $employee): PayrollBreakdown
    {
        return $this->calculateFrom(
            basicSalary: (float) $employee->basic_salary,
            allowance: (float) $employee->allowance,
            overtimeHours: (int) $employee->overtime_hours,
            hourlyRate: (float) $employee->hourly_rate,
        );
    }

    /**
     * Pure variant for testing — operates on raw inputs without an Employee.
     */
    public function calculateFrom(
        float $basicSalary,
        float $allowance,
        int $overtimeHours,
        float $hourlyRate,
    ): PayrollBreakdown {
        $overtimePay = round($overtimeHours * $hourlyRate, 2);
        $grossPay = round($basicSalary + $allowance + $overtimePay, 2);
        $tax = round($grossPay * self::TAX_RATE, 2);
        $epfEmployee = round($grossPay * self::EPF_EMPLOYEE_RATE, 2);
        $epfEmployer = round($grossPay * self::EPF_EMPLOYER_RATE, 2);
        $netPay = round($grossPay - $tax - $epfEmployee, 2);

        return new PayrollBreakdown(
            basicSalary: round($basicSalary, 2),
            allowance: round($allowance, 2),
            overtimePay: $overtimePay,
            grossPay: $grossPay,
            tax: $tax,
            epfEmployee: $epfEmployee,
            epfEmployer: $epfEmployer,
            netPay: $netPay,
        );
    }
}
