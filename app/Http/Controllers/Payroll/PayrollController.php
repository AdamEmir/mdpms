<?php

namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payroll\RunPayrollRequest;
use App\Models\Employee;
use App\Models\PayrollRecord;
use App\Services\Payroll\PayrollCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PayrollController extends Controller
{
    public function __construct(private readonly PayrollCalculator $calculator) {}

    public function create(): View
    {
        $now = now();

        return view('payroll.run', [
            'defaultMonth' => $now->month,
            'defaultYear' => $now->year,
        ]);
    }

    public function store(RunPayrollRequest $request): RedirectResponse
    {
        $month = $request->integer('month');
        $year = $request->integer('year');

        $processed = 0;
        $skipped = 0;

        DB::transaction(function () use ($month, $year, &$processed, &$skipped) {
            $existingEmployeeIds = PayrollRecord::query()
                ->where('month', $month)
                ->where('year', $year)
                ->pluck('employee_id')
                ->all();

            Employee::query()->chunkById(200, function ($employees) use ($month, $year, $existingEmployeeIds, &$processed, &$skipped) {
                foreach ($employees as $employee) {
                    if (in_array($employee->id, $existingEmployeeIds, true)) {
                        $skipped++;

                        continue;
                    }

                    $breakdown = $this->calculator->calculate($employee);

                    PayrollRecord::create([
                        'employee_id' => $employee->id,
                        'month' => $month,
                        'year' => $year,
                        ...$breakdown->toPersistableArray(),
                    ]);
                    $processed++;
                }
            });
        });

        return redirect()
            ->route('payroll.history', ['month' => $month, 'year' => $year])
            ->with('status', "Payroll processed: {$processed} new, {$skipped} skipped (already existed).");
    }
}
