@extends('layouts.app')
@section('title', 'Payroll history')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Payroll history</h1>
        <p class="mt-1 text-sm text-slate-500">Showing {{ $records->total() }} record(s).</p>
    </div>

    <form method="GET" action="{{ route('payroll.history') }}" class="mb-4 flex flex-wrap items-end gap-3 rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
        <div class="grow min-w-[14rem]">
            <label for="search" class="block text-sm font-medium text-slate-700">Search employee</label>
            <input id="search" type="search" name="search" value="{{ $filters['search'] }}"
                   placeholder="Name contains…"
                   class="mt-1 block w-full rounded-md border-0 px-3 py-2 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-slate-900">
        </div>
        <div>
            <label for="month" class="block text-sm font-medium text-slate-700">Month</label>
            <select id="month" name="month" class="mt-1 rounded-md border-0 px-3 py-2 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-slate-900">
                <option value="">Any</option>
                @foreach (range(1, 12) as $m)
                    <option value="{{ $m }}" @selected($filters['month'] === $m)>{{ \Carbon\Carbon::create()->month($m)->format('F') }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="year" class="block text-sm font-medium text-slate-700">Year</label>
            <input id="year" type="number" name="year" min="2000" max="2100" value="{{ $filters['year'] }}"
                   class="mt-1 block w-28 rounded-md border-0 px-3 py-2 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-slate-900">
        </div>
        <div>
            <label for="department_id" class="block text-sm font-medium text-slate-700">Department</label>
            <select id="department_id" name="department_id" class="mt-1 rounded-md border-0 px-3 py-2 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-slate-900">
                <option value="">All</option>
                @foreach ($departments as $dept)
                    <option value="{{ $dept->id }}" @selected($filters['department_id'] === $dept->id)>{{ $dept->name }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="rounded-md bg-slate-900 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-800">Apply</button>
        <a href="{{ route('payroll.history') }}" class="text-sm font-medium text-slate-600 hover:underline">Clear</a>
    </form>

    <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Period</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Employee</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Department</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Gross</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Net</th>
                    <th class="px-4 py-3"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 bg-white">
                @forelse ($records as $record)
                    <tr>
                        <td class="px-4 py-3 text-sm text-slate-700">{{ \Carbon\Carbon::create($record->year, $record->month)->format('F Y') }}</td>
                        <td class="px-4 py-3 text-sm font-medium text-slate-900">{{ $record->employee->name }}</td>
                        <td class="px-4 py-3 text-sm text-slate-600">{{ $record->employee->department->name }}</td>
                        <td class="px-4 py-3 text-right text-sm tabular-nums text-slate-700">RM {{ number_format((float) $record->gross_pay, 2) }}</td>
                        <td class="px-4 py-3 text-right text-sm tabular-nums font-medium text-slate-900">RM {{ number_format((float) $record->net_pay, 2) }}</td>
                        <td class="px-4 py-3 text-right text-sm">
                            <a href="{{ route('payroll.payslip', $record) }}"
                               title="View payslip"
                               aria-label="View payslip for {{ $record->employee->name }}"
                               class="inline-flex items-center justify-center rounded-md p-2 text-slate-600 hover:bg-slate-100 hover:text-slate-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-slate-900">
                                <i class="fa-regular fa-eye" aria-hidden="true"></i>
                                <span class="sr-only">View payslip</span>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-sm text-slate-500">No payroll records match these filters.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $records->links() }}</div>
@endsection
