@php
    $employee = $record->employee;
    $period = \Carbon\Carbon::create($record->year, $record->month)->format('F Y');
    $basic = (float) $employee->basic_salary;
    $allowance = (float) $employee->allowance;
@endphp

<div class="max-w-2xl rounded-lg border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-200 p-6">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ config('app.name') }}</p>
        <h2 class="mt-1 text-xl font-semibold tracking-tight text-slate-900">Payslip — {{ $period }}</h2>
        <dl class="mt-4 grid grid-cols-2 gap-4 text-sm">
            <div>
                <dt class="text-slate-500">Employee</dt>
                <dd class="font-medium text-slate-900">{{ $employee->name }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">Position</dt>
                <dd class="font-medium text-slate-900">{{ $employee->position }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">Department</dt>
                <dd class="font-medium text-slate-900">{{ $employee->department->name }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">Period</dt>
                <dd class="font-medium text-slate-900">{{ $period }}</dd>
            </div>
        </dl>
    </div>

    <div class="divide-y divide-slate-200 px-6 text-sm">
        <div class="flex justify-between py-3">
            <span class="text-slate-600">Basic salary</span>
            <span class="tabular-nums font-medium text-slate-900">RM {{ number_format($basic, 2) }}</span>
        </div>
        <div class="flex justify-between py-3">
            <span class="text-slate-600">Allowance</span>
            <span class="tabular-nums font-medium text-slate-900">RM {{ number_format($allowance, 2) }}</span>
        </div>
        <div class="flex justify-between py-3">
            <span class="text-slate-600">Overtime pay</span>
            <span class="tabular-nums font-medium text-slate-900">RM {{ number_format((float) $record->overtime_pay, 2) }}</span>
        </div>
        <div class="flex justify-between py-3 font-semibold">
            <span>Gross pay</span>
            <span class="tabular-nums">RM {{ number_format((float) $record->gross_pay, 2) }}</span>
        </div>
        <div class="flex justify-between py-3">
            <span class="text-slate-600">Tax (8%)</span>
            <span class="tabular-nums text-rose-700">- RM {{ number_format((float) $record->tax, 2) }}</span>
        </div>
        <div class="flex justify-between py-3">
            <span class="text-slate-600">EPF Employee (11%)</span>
            <span class="tabular-nums text-rose-700">- RM {{ number_format((float) $record->epf_employee, 2) }}</span>
        </div>
        <div class="flex justify-between py-3 text-slate-500">
            <span>EPF Employer (13%) <span class="text-xs">— info only</span></span>
            <span class="tabular-nums">RM {{ number_format((float) $record->epf_employer, 2) }}</span>
        </div>
    </div>

    <div class="flex items-center justify-between border-t border-slate-200 bg-slate-50 px-6 py-4">
        <span class="text-sm font-semibold uppercase tracking-wide text-slate-700">Net pay</span>
        <span class="text-xl font-semibold tabular-nums text-slate-900">RM {{ number_format((float) $record->net_pay, 2) }}</span>
    </div>
</div>
