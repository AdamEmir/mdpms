# Payroll Formula

The formula is **fixed by the assessment**. It is implemented in `app/Services/Payroll/PayrollCalculator.php` exactly as specified.

## Formula

```
overtime_pay = overtime_hours * hourly_rate
gross_pay    = basic_salary + allowance + overtime_pay
tax          = gross_pay * 0.08          (8%)
epf_employee = gross_pay * 0.11          (11%)
epf_employer = gross_pay * 0.13          (13%)   ← informational only
net_pay      = gross_pay - tax - epf_employee
```

All seven values are rounded to **two decimal places at each step** (`round($value, 2)`).

## Constants

The percentages live as `const float` declarations on `PayrollCalculator`:

```php
public const float TAX_RATE          = 0.08;
public const float EPF_EMPLOYEE_RATE = 0.11;
public const float EPF_EMPLOYER_RATE = 0.13;
```

## Worked example — Siti Aminah

This is the example from the assignment document, also seeded into the database for visual verification.

| Input            | Value     |
|------------------|-----------|
| `basic_salary`   | 4 000.00  |
| `allowance`      |   600.00  |
| `overtime_hours` |       10  |
| `hourly_rate`    |    25.00  |

| Output          | Calculation              | Value     |
|-----------------|--------------------------|-----------|
| `overtime_pay`  | 10 × 25                  |   250.00  |
| `gross_pay`     | 4 000 + 600 + 250        | 4 850.00  |
| `tax`           | 4 850 × 0.08             |   388.00  |
| `epf_employee`  | 4 850 × 0.11             |   533.50  |
| `epf_employer`  | 4 850 × 0.13             |   630.50  |
| **`net_pay`**   | **4 850 − 388 − 533.50** | **3 928.50** |

Verified live in the browser via Playwright MCP and asserted in `tests/Unit/Services/Payroll/PayrollCalculatorTest.php`.

## Service surface

```php
final class PayrollCalculator
{
    /** Convenience wrapper for production code. */
    public function calculate(Employee $employee): PayrollBreakdown;

    /** Pure variant — operates on raw scalars. Used by unit tests. */
    public function calculateFrom(
        float $basicSalary,
        float $allowance,
        int   $overtimeHours,
        float $hourlyRate,
    ): PayrollBreakdown;
}
```

Both methods return `App\Services\Payroll\PayrollBreakdown`, a `final readonly` DTO with seven public typed properties (`basicSalary`, `allowance`, `overtimePay`, `grossPay`, `tax`, `epfEmployee`, `epfEmployer`, `netPay`) plus a `toPersistableArray()` method that returns the six fields actually written to `payroll_records`.

## How payroll runs

`App\Http\Controllers\Payroll\PayrollController@store`:

1. Validates `month` (1–12) and `year` (2000–2100) via `RunPayrollRequest`.
2. Inside `DB::transaction(...)` it pre-loads the set of `employee_id`s that already have a record for the given period.
3. Iterates all employees in 200-row chunks via `Employee::query()->chunkById(200, ...)`.
4. For each employee:
   - if their id is in the pre-loaded set, increment `$skipped` and continue;
   - otherwise compute via `PayrollCalculator::calculate($employee)` and `PayrollRecord::create([...])`.
5. Redirects to `payroll.history?month=…&year=…` with a flash message: `"Payroll processed: X new, Y skipped (already existed)."`

Two layers protect against duplicate records:

- **Application** — the in-memory `existingEmployeeIds` set short-circuits before the calculator is even called.
- **Database** — the `(employee_id, month, year)` unique index rejects duplicates at the storage layer.

## Edge cases the tests cover

| Case | Expectation |
|------|-------------|
| Spec example | Net pay 3 928.50 (exact match) |
| Zero overtime hours | `overtime_pay` is 0; `gross_pay` is `basic + allowance` |
| Zero allowance | `gross_pay` is `basic + overtime_pay` |
| Decimal inputs (e.g. 3333.33 / 12.34) | Each step rounds to 2 dp; gross/tax/EPF match the manual computation |
| Eloquent attribute parsing | A new `Employee` populated from an array yields net pay 3 928.50 |
| Invariant property | `gross == round(basic + allowance + (hours * rate), 2)` for many sample inputs |
