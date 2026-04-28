@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Dashboard</h1>
        <p class="mt-1 text-sm text-slate-500">Overview as of {{ $currentMonthLabel }}.</p>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-500">Departments</p>
            <p class="mt-2 text-3xl font-semibold tracking-tight text-slate-900">{{ $departmentsCount }}</p>
            <a href="{{ route('departments.index') }}" class="mt-3 inline-block text-sm font-medium text-slate-700 hover:underline">Manage &rarr;</a>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-500">Employees</p>
            <p class="mt-2 text-3xl font-semibold tracking-tight text-slate-900">{{ $employeesCount }}</p>
            <a href="{{ route('employees.index') }}" class="mt-3 inline-block text-sm font-medium text-slate-700 hover:underline">Manage &rarr;</a>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-500">Payroll runs this month</p>
            <p class="mt-2 text-3xl font-semibold tracking-tight text-slate-900">{{ $payrollThisMonth }}</p>
            <a href="{{ route('payroll.run') }}" class="mt-3 inline-block text-sm font-medium text-slate-700 hover:underline">Run payroll &rarr;</a>
        </div>
    </div>
@endsection
