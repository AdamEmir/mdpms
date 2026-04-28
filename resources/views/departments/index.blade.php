@extends('layouts.app')
@section('title', 'Departments')

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Departments</h1>
            <p class="mt-1 text-sm text-slate-500">Organisational units that group employees.</p>
        </div>
        <a href="{{ route('departments.create') }}"
           class="inline-flex items-center rounded-md bg-slate-900 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-800">
            New department
        </a>
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
                    <tr>
                        <td class="px-4 py-3 text-sm font-medium text-slate-900">{{ $department->name }}</td>
                        <td class="px-4 py-3 text-sm text-slate-600">{{ $department->employees_count }}</td>
                        <td class="px-4 py-3 text-right text-sm">
                            <div class="inline-flex items-center gap-1">
                                <a href="{{ route('departments.edit', $department) }}"
                                   title="Edit"
                                   aria-label="Edit {{ $department->name }}"
                                   class="inline-flex items-center justify-center rounded-md p-2 text-slate-600 hover:bg-slate-100 hover:text-slate-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-slate-900">
                                    <i class="fa-regular fa-pen-to-square" aria-hidden="true"></i>
                                    <span class="sr-only">Edit</span>
                                </a>
                                <form method="POST" action="{{ route('departments.destroy', $department) }}" class="inline"
                                      data-confirm="delete" data-confirm-title="Delete {{ $department->name }}?"
                                      data-confirm-text="This cannot be undone.">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            title="Delete"
                                            aria-label="Delete {{ $department->name }}"
                                            class="inline-flex items-center justify-center rounded-md p-2 text-rose-600 hover:bg-rose-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-rose-600">
                                        <i class="fa-regular fa-trash-can" aria-hidden="true"></i>
                                        <span class="sr-only">Delete</span>
                                    </button>
                                </form>
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
@endsection
