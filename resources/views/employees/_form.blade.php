@csrf
<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
    <div class="sm:col-span-2">
        <label for="name" class="block text-sm font-medium text-slate-700">Name</label>
        <input id="name" type="text" name="name" required value="{{ old('name', $employee->name ?? '') }}"
               class="mt-1 block w-full rounded-md border-0 px-3 py-2 text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-slate-900">
        @error('name')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="position" class="block text-sm font-medium text-slate-700">Position</label>
        <input id="position" type="text" name="position" required value="{{ old('position', $employee->position ?? '') }}"
               class="mt-1 block w-full rounded-md border-0 px-3 py-2 text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-slate-900">
        @error('position')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="department_id" class="block text-sm font-medium text-slate-700">Department</label>
        <select id="department_id" name="department_id" required
                class="mt-1 block w-full rounded-md border-0 px-3 py-2 text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-slate-900">
            <option value="">Select a department</option>
            @foreach ($departments as $dept)
                <option value="{{ $dept->id }}" @selected((int) old('department_id', $employee->department_id ?? 0) === $dept->id)>{{ $dept->name }}</option>
            @endforeach
        </select>
        @error('department_id')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="basic_salary" class="block text-sm font-medium text-slate-700">Basic salary (RM)</label>
        <input id="basic_salary" type="number" step="0.01" min="0" name="basic_salary" required
               value="{{ old('basic_salary', $employee->basic_salary ?? '') }}"
               class="mt-1 block w-full rounded-md border-0 px-3 py-2 text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-slate-900">
        @error('basic_salary')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="allowance" class="block text-sm font-medium text-slate-700">Allowance (RM)</label>
        <input id="allowance" type="number" step="0.01" min="0" name="allowance" required
               value="{{ old('allowance', $employee->allowance ?? '0.00') }}"
               class="mt-1 block w-full rounded-md border-0 px-3 py-2 text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-slate-900">
        @error('allowance')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="overtime_hours" class="block text-sm font-medium text-slate-700">Overtime hours</label>
        <input id="overtime_hours" type="number" step="1" min="0" name="overtime_hours" required
               value="{{ old('overtime_hours', $employee->overtime_hours ?? 0) }}"
               class="mt-1 block w-full rounded-md border-0 px-3 py-2 text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-slate-900">
        @error('overtime_hours')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="hourly_rate" class="block text-sm font-medium text-slate-700">Hourly rate (RM)</label>
        <input id="hourly_rate" type="number" step="0.01" min="0" name="hourly_rate" required
               value="{{ old('hourly_rate', $employee->hourly_rate ?? '0.00') }}"
               class="mt-1 block w-full rounded-md border-0 px-3 py-2 text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-slate-900">
        @error('hourly_rate')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
    </div>
</div>

<div class="mt-6 flex justify-end gap-3">
    <a href="{{ route('employees.index') }}" class="rounded-md bg-white px-3 py-2 text-sm font-medium text-slate-700 ring-1 ring-inset ring-slate-300 hover:bg-slate-50">Cancel</a>
    <button type="submit" class="rounded-md bg-slate-900 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-800">{{ $submitLabel }}</button>
</div>
