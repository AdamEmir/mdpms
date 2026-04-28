<?php

namespace App\Services\Payroll;

/**
 * Immutable result of a payroll calculation, ready to persist.
 */
final readonly class PayrollBreakdown
{
    public function __construct(
        public float $basicSalary,
        public float $allowance,
        public float $overtimePay,
        public float $grossPay,
        public float $tax,
        public float $epfEmployee,
        public float $epfEmployer,
        public float $netPay,
    ) {}

    /**
     * @return array{
     *   gross_pay: float,
     *   overtime_pay: float,
     *   tax: float,
     *   epf_employee: float,
     *   epf_employer: float,
     *   net_pay: float
     * }
     */
    public function toPersistableArray(): array
    {
        return [
            'gross_pay' => $this->grossPay,
            'overtime_pay' => $this->overtimePay,
            'tax' => $this->tax,
            'epf_employee' => $this->epfEmployee,
            'epf_employer' => $this->epfEmployer,
            'net_pay' => $this->netPay,
        ];
    }
}
