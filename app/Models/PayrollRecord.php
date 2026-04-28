<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $employee_id
 * @property int $month
 * @property int $year
 * @property string $gross_pay
 * @property string $overtime_pay
 * @property string $tax
 * @property string $epf_employee
 * @property string $epf_employer
 * @property string $net_pay
 * @property-read Employee $employee
 */
class PayrollRecord extends Model
{
    /** @use HasFactory<\Database\Factories\PayrollRecordFactory> */
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'month',
        'year',
        'gross_pay',
        'overtime_pay',
        'tax',
        'epf_employee',
        'epf_employer',
        'net_pay',
    ];

    protected function casts(): array
    {
        return [
            'month' => 'integer',
            'year' => 'integer',
            'gross_pay' => 'decimal:2',
            'overtime_pay' => 'decimal:2',
            'tax' => 'decimal:2',
            'epf_employee' => 'decimal:2',
            'epf_employer' => 'decimal:2',
            'net_pay' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
