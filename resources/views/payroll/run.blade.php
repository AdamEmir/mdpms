@extends('layouts.app')
@section('title', 'Run payroll')

@section('content')
    <h1 class="mb-6 text-2xl font-semibold tracking-tight text-slate-900">Run payroll</h1>

    <div class="max-w-lg rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        <p class="mb-4 text-sm text-slate-500">Select the period below. The system processes <strong>all employees</strong> for that month and year. Existing records for the same period are skipped.</p>
        <form method="POST" action="{{ route('payroll.process') }}" data-confirm="run"
              data-confirm-title="Run payroll?"
              data-confirm-text="This will create payroll records for all employees for the selected period."
              data-confirm-button="Yes">
            @csrf
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="month" class="block text-sm font-medium text-slate-700">Month</label>
                    <select id="month" name="month" class="mt-1 block w-full rounded-md border-0 px-3 py-2 text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-slate-900">
                        @foreach (range(1, 12) as $m)
                            <option value="{{ $m }}" @selected((int) old('month', $defaultMonth) === $m)>{{ \Carbon\Carbon::create()->month($m)->format('F') }}</option>
                        @endforeach
                    </select>
                    @error('month')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="year" class="block text-sm font-medium text-slate-700">Year</label>
                    <input id="year" type="number" name="year" min="2000" max="2100" required value="{{ old('year', $defaultYear) }}"
                           class="mt-1 block w-full rounded-md border-0 px-3 py-2 text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-slate-900">
                    @error('year')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="mt-6 flex justify-end">
                <button type="submit" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-800">Process payroll</button>
            </div>
        </form>
    </div>
@endsection
