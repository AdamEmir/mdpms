<div>
    @include('partials.flash-messages')

    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Employees</h1>
            <p class="mt-1 text-sm text-slate-500">Showing {{ $employees->total() }} employee(s).</p>
        </div>
        <button type="button" wire:click="openCreate"
                class="inline-flex items-center rounded-md bg-slate-900 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-800">
            New employee
        </button>
    </div>

    <div class="mb-4 flex flex-wrap items-end gap-3 rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
        <div class="grow min-w-56">
            <label for="search" class="block text-sm font-medium text-slate-700">Search employee</label>
            <input id="search" type="search" wire:model.live.debounce.300ms="search"
                   placeholder="Name contains…"
                   class="mt-1 block w-full rounded-md border-0 px-3 py-2 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-slate-900">
        </div>
        <div>
            <label for="department_id" class="block text-sm font-medium text-slate-700">Filter by department</label>
            <select id="department_id" wire:model.live="departmentId"
                    class="mt-1 rounded-md border-0 px-3 py-2 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-slate-900">
                <option value="">All departments</option>
                @foreach ($this->departments as $dept)
                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                @endforeach
            </select>
        </div>
        @if ($search !== '' || $departmentId)
            <button type="button" wire:click="clearFilters"
                    class="text-sm font-medium text-slate-600 hover:underline">Clear
            </button>
        @endif
    </div>

    <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">No</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Name</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Position
                </th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    Department
                </th>
                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Basic</th>
                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">OT (h)
                </th>
                <th class="px-4 py-3"><span class="sr-only">Actions</span></th>
            </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 bg-white">
            @forelse ($employees as $index => $employee)
                <tr wire:key="emp-{{ $employee->id }}">
                    <td class="px-4 py-3 text-sm font-medium text-slate-900">{{ ($employees->currentPage() - 1) * $employees->perPage() + $loop->index + 1 }}</td>
                    <td class="px-4 py-3 text-sm font-medium text-slate-900">{{ $employee->name }}</td>
                    <td class="px-4 py-3 text-sm text-slate-600">{{ $employee->position }}</td>
                    <td class="px-4 py-3 text-sm text-slate-600">{{ $employee->department->name }}</td>
                    <td class="px-4 py-3 text-right text-sm tabular-nums text-slate-600">RM {{ number_format((float) $employee->basic_salary, 2) }}</td>
                    <td class="px-4 py-3 text-right text-sm tabular-nums text-slate-600">{{ $employee->overtime_hours }}</td>
                    <td class="px-4 py-3 text-right text-sm">
                        <div class="inline-flex items-center gap-1">
                            <button type="button" wire:click="openEdit({{ $employee->id }})"
                                    title="Edit" aria-label="Edit {{ $employee->name }}"
                                    class="inline-flex items-center justify-center rounded-md p-2 text-slate-600 hover:bg-slate-100 hover:text-slate-900 focus-visible:outline focus-visible:outline-offset-2 focus-visible:outline-slate-900">
                                <i class="fa-regular fa-pen-to-square" aria-hidden="true"></i>
                                <span class="sr-only">Edit</span>
                            </button>
                            <button type="button"
                                    x-data
                                    x-on:click="
                                            Swal.fire({
                                                title: 'Delete {{ $employee->name }}?',
                                                text: 'This cannot be undone.',
                                                icon: 'warning',
                                                showCancelButton: true,
                                                confirmButtonColor: '#dc2626',
                                                confirmButtonText: 'Yes, delete',
                                            }).then(result => { if (result.isConfirmed) $wire.delete({{ $employee->id }}); });
                                        "
                                    title="Delete" aria-label="Delete {{ $employee->name }}"
                                    class="inline-flex items-center justify-center rounded-md p-2 text-rose-600 hover:bg-rose-50 focus-visible:outline focus-visible:outline-offset-2 focus-visible:outline-rose-600">
                                <i class="fa-regular fa-trash-can" aria-hidden="true"></i>
                                <span class="sr-only">Delete</span>
                            </button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-4 py-8 text-center text-sm text-slate-500">No employees match these
                        filters.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $employees->links() }}</div>

    @if ($showForm)
        <div class="fixed inset-0 z-40 flex items-center justify-center bg-slate-900/40 px-4"
             role="dialog" aria-modal="true" aria-labelledby="emp-form-title"
             wire:key="emp-modal"
             x-data x-trap.noscroll="true"
             wire:keydown.escape="closeForm">
            <div class="w-full max-w-2xl rounded-xl bg-white p-6 shadow-xl">
                <h2 id="emp-form-title" class="mb-4 text-lg font-semibold text-slate-900">
                    {{ $editingId ? 'Edit employee' : 'New employee' }}
                </h2>
                <form wire:submit="save" class="space-y-4" novalidate>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label for="name" class="block text-sm font-medium text-slate-700">Name</label>
                            <input id="name" type="text" wire:model="name"
                                   class="mt-1 block w-full rounded-md border-0 px-3 py-2 text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-slate-900">
                            @error('name')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="position" class="block text-sm font-medium text-slate-700">Position</label>
                            <input id="position" type="text" wire:model="position"
                                   class="mt-1 block w-full rounded-md border-0 px-3 py-2 text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-slate-900">
                            @error('position')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="formDepartmentId"
                                   class="block text-sm font-medium text-slate-700">Department</label>
                            <select id="formDepartmentId" wire:model="formDepartmentId"
                                    class="mt-1 block w-full rounded-md border-0 px-3 py-2 text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-slate-900">
                                <option value="">Select a department</option>
                                @foreach ($this->departments as $dept)
                                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                @endforeach
                            </select>
                            @error('formDepartmentId')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="basicSalary" class="block text-sm font-medium text-slate-700">Basic salary
                                (RM)</label>
                            <input id="basicSalary" type="number" step="0.01" min="0" wire:model="basicSalary"
                                   class="mt-1 block w-full rounded-md border-0 px-3 py-2 text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-slate-900">
                            @error('basicSalary')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="allowance" class="block text-sm font-medium text-slate-700">Allowance
                                (RM)</label>
                            <input id="allowance" type="number" step="0.01" min="0" wire:model="allowance"
                                   class="mt-1 block w-full rounded-md border-0 px-3 py-2 text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-slate-900">
                            @error('allowance')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="overtimeHours" class="block text-sm font-medium text-slate-700">Overtime
                                hours</label>
                            <input id="overtimeHours" type="number" step="1" min="0" wire:model="overtimeHours"
                                   class="mt-1 block w-full rounded-md border-0 px-3 py-2 text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-slate-900">
                            @error('overtimeHours')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="hourlyRate" class="block text-sm font-medium text-slate-700">Hourly rate
                                (RM)</label>
                            <input id="hourlyRate" type="number" step="0.01" min="0" wire:model="hourlyRate"
                                   class="mt-1 block w-full rounded-md border-0 px-3 py-2 text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-slate-900">
                            @error('hourlyRate')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" wire:click="closeForm"
                                class="rounded-md bg-white px-3 py-2 text-sm font-medium text-slate-700 ring-1 ring-inset ring-slate-300 hover:bg-slate-50">
                            Cancel
                        </button>
                        <button type="submit"
                                class="rounded-md bg-slate-900 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-800">
                            {{ $editingId ? 'Update' : 'Create' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
