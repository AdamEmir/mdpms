<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payslip</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #0f172a; font-size: 12px; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        .muted { color: #64748b; font-size: 11px; }
        .meta { width: 100%; margin: 16px 0 24px; border-collapse: collapse; }
        .meta td { padding: 4px 0; }
        .meta .label { color: #64748b; width: 30%; }
        table.lines { width: 100%; border-collapse: collapse; }
        table.lines td { padding: 6px 0; border-bottom: 1px solid #e2e8f0; }
        td.right { text-align: right; }
        .total { background: #f1f5f9; font-weight: bold; font-size: 14px; }
        .total td { padding: 10px 0; border-bottom: 0; }
        .neg { color: #b91c1c; }
        .info { color: #64748b; font-size: 10px; }
    </style>
</head>
<body>
    @php
        $employee = $record->employee;
        $period = \Carbon\Carbon::create($record->year, $record->month)->format('F Y');
        $basic = (float) $employee->basic_salary;
        $allowance = (float) $employee->allowance;
    @endphp

    <div class="muted">{{ config('app.name') }}</div>
    <h1>Payslip — {{ $period }}</h1>

    <table class="meta">
        <tr><td class="label">Employee</td><td><strong>{{ $employee->name }}</strong></td></tr>
        <tr><td class="label">Position</td><td>{{ $employee->position }}</td></tr>
        <tr><td class="label">Department</td><td>{{ $employee->department->name }}</td></tr>
        <tr><td class="label">Period</td><td>{{ $period }}</td></tr>
    </table>

    <table class="lines">
        <tr><td>Basic salary</td><td class="right">RM {{ number_format($basic, 2) }}</td></tr>
        <tr><td>Allowance</td><td class="right">RM {{ number_format($allowance, 2) }}</td></tr>
        <tr><td>Overtime pay</td><td class="right">RM {{ number_format((float) $record->overtime_pay, 2) }}</td></tr>
        <tr><td><strong>Gross pay</strong></td><td class="right"><strong>RM {{ number_format((float) $record->gross_pay, 2) }}</strong></td></tr>
        <tr><td>Tax (8%)</td><td class="right neg">- RM {{ number_format((float) $record->tax, 2) }}</td></tr>
        <tr><td>EPF Employee (11%)</td><td class="right neg">- RM {{ number_format((float) $record->epf_employee, 2) }}</td></tr>
        <tr><td>EPF Employer (13%) <span class="info">info only</span></td><td class="right info">RM {{ number_format((float) $record->epf_employer, 2) }}</td></tr>
        <tr class="total"><td>NET PAY</td><td class="right">RM {{ number_format((float) $record->net_pay, 2) }}</td></tr>
    </table>
</body>
</html>
