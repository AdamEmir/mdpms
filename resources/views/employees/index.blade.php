@extends('layouts.app')
@section('title', 'Employees')

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Employees</h1>
            <p class="mt-1 text-sm text-slate-500">Showing {{ $employees->total() }} employee(s).</p>
        </div>
        <a href="{{ route('employees.create') }}" class="inline-flex items-center rounded-md bg-slate-900 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-800">
            New employee
        </a>
    </div>

    <form method="GET" action="{{ route('employees.index') }}" class="mb-4 flex flex-wrap items-end gap-3 rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
        <div class="grow min-w-[14rem]">
            <label for="search" class="block text-sm font-medium text-slate-700">Search employee</label>
            <input id="search" type="search" name="search" value="{{ $search }}"
                   placeholder="Name contains…"
                   class="mt-1 block w-full rounded-md border-0 px-3 py-2 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-slate-900">
        </div>
        <div>
            <label for="department_id" class="block text-sm font-medium text-slate-700">Filter by department</label>
            <select id="department_id" name="department_id"
                    class="mt-1 rounded-md border-0 px-3 py-2 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-slate-900">
                <option value="">All departments</option>
                @foreach ($departments as $dept)
                    <option value="{{ $dept->id }}" @selected($selectedDepartmentId === $dept->id)>{{ $dept->name }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="rounded-md bg-slate-900 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-800">Apply</button>
        @if ($selectedDepartmentId || $search !== '')
            <a href="{{ route('employees.index') }}" class="text-sm font-medium text-slate-600 hover:underline">Clear</a>
        @endif
    </form>

    <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Name</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Position</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Department</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Basic</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">OT (h)</th>
                    <th class="px-4 py-3"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 bg-white">
                @forelse ($employees as $employee)
                    <tr>
                        <td class="px-4 py-3 text-sm font-medium text-slate-900">{{ $employee->name }}</td>
                        <td class="px-4 py-3 text-sm text-slate-600">{{ $employee->position }}</td>
                        <td class="px-4 py-3 text-sm text-slate-600">{{ $employee->department->name }}</td>
                        <td class="px-4 py-3 text-right text-sm tabular-nums text-slate-600">RM {{ number_format((float) $employee->basic_salary, 2) }}</td>
                        <td class="px-4 py-3 text-right text-sm tabular-nums text-slate-600">{{ $employee->overtime_hours }}</td>
                        <td class="px-4 py-3 text-right text-sm">
                            <div class="inline-flex items-center gap-1">
                                <a href="{{ route('employees.edit', $employee) }}"
                                   title="Edit"
                                   aria-label="Edit {{ $employee->name }}"
                                   class="inline-flex items-center justify-center rounded-md p-2 text-slate-600 hover:bg-slate-100 hover:text-slate-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-slate-900">
                                    <i class="fa-regular fa-pen-to-square" aria-hidden="true"></i>
                                    <span class="sr-only">Edit</span>
                                </a>
                                <form method="POST" action="{{ route('employees.destroy', $employee) }}" class="inline"
                                      data-confirm="delete" data-confirm-title="Delete {{ $employee->name }}?">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            title="Delete"
                                            aria-label="Delete {{ $employee->name }}"
                                            class="inline-flex items-center justify-center rounded-md p-2 text-rose-600 hover:bg-rose-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-rose-600">
                                        <i class="fa-regular fa-trash-can" aria-hidden="true"></i>
                                        <span class="sr-only">Delete</span>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-sm text-slate-500">No employees match these filters.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $employees->links() }}</div>
@endsection
