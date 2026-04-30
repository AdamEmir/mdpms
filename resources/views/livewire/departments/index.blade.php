<div>
    @include('partials.flash-messages')

    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Departments</h1>
            <p class="mt-1 text-sm text-slate-500">Organisational units that group employees.</p>
        </div>
        <button type="button" wire:click="openCreate"
                class="inline-flex items-center rounded-md bg-slate-900 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-800">
            New department
        </button>
    </div>

    <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Name</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Employees</th>
                    <th class="px-4 py-3"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 bg-white">
                @forelse ($departments as $department)
                    <tr wire:key="dept-{{ $department->id }}">
                        <td class="px-4 py-3 text-sm font-medium text-slate-900">{{ $department->name }}</td>
                        <td class="px-4 py-3 text-sm text-slate-600">{{ $department->employees_count }}</td>
                        <td class="px-4 py-3 text-right text-sm">
                            <div class="inline-flex items-center gap-1">
                                <button type="button" wire:click="openEdit({{ $department->id }})"
                                        title="Edit"
                                        aria-label="Edit {{ $department->name }}"
                                        class="inline-flex items-center justify-center rounded-md p-2 text-slate-600 hover:bg-slate-100 hover:text-slate-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-slate-900">
                                    <i class="fa-regular fa-pen-to-square" aria-hidden="true"></i>
                                    <span class="sr-only">Edit</span>
                                </button>
                                <button type="button"
                                        x-data
                                        x-on:click="
                                            Swal.fire({
                                                title: 'Delete {{ $department->name }}?',
                                                text: 'This cannot be undone.',
                                                icon: 'warning',
                                                showCancelButton: true,
                                                confirmButtonColor: '#dc2626',
                                                confirmButtonText: 'Yes, delete',
                                            }).then(result => { if (result.isConfirmed) $wire.delete({{ $department->id }}); });
                                        "
                                        title="Delete"
                                        aria-label="Delete {{ $department->name }}"
                                        class="inline-flex items-center justify-center rounded-md p-2 text-rose-600 hover:bg-rose-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-rose-600">
                                    <i class="fa-regular fa-trash-can" aria-hidden="true"></i>
                                    <span class="sr-only">Delete</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-4 py-8 text-center text-sm text-slate-500">No departments yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $departments->links() }}</div>

    @if ($showForm)
        <div class="fixed inset-0 z-40 flex items-center justify-center bg-slate-900/40 px-4"
             role="dialog" aria-modal="true" aria-labelledby="dept-form-title"
             wire:key="dept-modal"
             x-data x-trap.noscroll="true"
             wire:keydown.escape="closeForm">
            <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
                <h2 id="dept-form-title" class="mb-4 text-lg font-semibold text-slate-900">
                    {{ $editingId ? 'Edit department' : 'New department' }}
                </h2>
                <form wire:submit="save" class="space-y-5" novalidate>
                    <div>
                        <label for="name" class="block text-sm font-medium text-slate-700">Department name</label>
                        <input id="name" type="text" wire:model="name" required
                               class="mt-1 block w-full rounded-md border-0 px-3 py-2 text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-slate-900">
                        @error('name')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="flex justify-end gap-3">
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
