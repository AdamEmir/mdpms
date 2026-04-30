<?php

namespace App\Livewire\Payroll;

use App\Models\Employee;
use App\Models\PayrollRecord;
use App\Services\Payroll\PayrollCalculator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Run payroll')]
class Run extends Component
{
    public int $month;

    public int $year;

    public function mount(): void
    {
        $now = now();
        $this->month = $now->month;
        $this->year = $now->year;
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'month' => ['required', 'integer', 'between:1,12'],
            'year' => ['required', 'integer', 'between:2000,2100'],
        ];
    }

    public function process(PayrollCalculator $calculator): mixed
    {
        $this->validate();

        $month = $this->month;
        $year = $this->year;

        $processed = 0;
        $skipped = 0;

        DB::transaction(function () use ($calculator, $month, $year, &$processed, &$skipped) {
            $existingEmployeeIds = PayrollRecord::query()
                ->where('month', $month)
                ->where('year', $year)
                ->pluck('employee_id')
                ->all();

            Employee::query()->chunkById(200, function ($employees) use ($calculator, $month, $year, $existingEmployeeIds, &$processed, &$skipped) {
                foreach ($employees as $employee) {
                    if (in_array($employee->id, $existingEmployeeIds, true)) {
                        $skipped++;

                        continue;
                    }

                    $breakdown = $calculator->calculate($employee);

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

        session()->flash('status', "Payroll processed: {$processed} new, {$skipped} skipped (already existed).");

        return $this->redirect(route('payroll.history', ['month' => $month, 'year' => $year]), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.payroll.run');
    }
}
