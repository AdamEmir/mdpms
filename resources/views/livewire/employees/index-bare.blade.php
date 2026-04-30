<div>
    {{-- Flash messages partial (session status/error rendered here after each action) --}}
    @include('partials.flash-messages')

    {{-- ── LIST HEADER ──────────────────────────────────────────────────────────── --}}

    <h1>Employees ({{ $employees->total() }})</h1>

    {{-- wire:click calls openCreate() on the component class --}}
    <button type="button" wire:click="openCreate">New employee</button>

    {{-- ── FILTERS ──────────────────────────────────────────────────────────────── --}}

    {{--
        wire:model.live          → syncs input value to $search in PHP on every keystroke
        .debounce.300ms          → but waits 300ms of silence before sending (avoids spam)
        #[Url(as:'search')]      → Livewire also keeps $search in the URL query string
        updatingSearch() hook    → resets pagination when search changes
    --}}
    <input type="search" wire:model.live.debounce.300ms="search" placeholder="Name…">

    {{--
        wire:model.live          → syncs selected value to $departmentId immediately on change
        #[Url(as:'department_id')] → also reflected in the URL
        $this->departments       → calls the #[Computed] departments() method
    --}}
    <select wire:model.live="departmentId">
        <option value="">All departments</option>
        @foreach ($this->departments as $dept)
            <option value="{{ $dept->id }}">{{ $dept->name }}</option>
        @endforeach
    </select>

    {{-- Only shown when a filter is active; wire:click calls clearFilters() --}}
    @if ($search !== '' || $departmentId)
        <button type="button" wire:click="clearFilters">Clear</button>
    @endif

    {{-- ── TABLE ────────────────────────────────────────────────────────────────── --}}

    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Position</th>
                <th>Department</th>
                <th>Basic salary</th>
                <th>OT hours</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        @forelse ($employees as $index => $employee)
            {{--
                wire:key       → tells Livewire which DOM row maps to which record.
                                 Without this, Livewire may reuse the wrong row on re-render.
            --}}
            <tr wire:key="emp-{{ $employee->id }}">
                <td>{{ ($employees->currentPage() - 1) * $employees->perPage() + $loop->index + 1 }}</td>
                <td>{{ $employee->name }}</td>
                <td>{{ $employee->position }}</td>
                <td>{{ $employee->department->name }}</td>  {{-- eager-loaded via with('department') in render() --}}
                <td>{{ $employee->basic_salary }}</td>
                <td>{{ $employee->overtime_hours }}</td>
                <td>
                    {{-- wire:click passes the ID as an argument to openEdit() --}}
                    <button type="button" wire:click="openEdit({{ $employee->id }})">Edit</button>

                    {{--
                        x-data       → initialises an Alpine component scope on this element
                        x-on:click   → Alpine handles the click in JS (not Livewire)
                        Swal.fire()  → SweetAlert2 confirm dialog
                        $wire        → Alpine's bridge to the Livewire component
                        $wire.delete(id) → calls delete() on the Livewire component after confirm
                    --}}
                    <button type="button"
                            x-data
                            x-on:click="
                                Swal.fire({
                                    title: 'Delete {{ $employee->name }}?',
                                    showCancelButton: true,
                                }).then(result => {
                                    if (result.isConfirmed) $wire.delete({{ $employee->id }});
                                });
                            ">
                        Delete
                    </button>
                </td>
            </tr>
        @empty
            <tr><td colspan="6">No employees found.</td></tr>
        @endforelse
        </tbody>
    </table>

    {{-- Livewire pagination links (WithPagination trait) --}}
    {{ $employees->links() }}

    {{-- ── MODAL ────────────────────────────────────────────────────────────────── --}}

    {{--
        $showForm        → PHP boolean that controls whether the modal renders at all.
                           Livewire re-renders this component when $showForm changes,
                           adding or removing the modal from the DOM.
        wire:key         → stable key so Livewire knows this div is "the modal"
        x-trap.noscroll  → Alpine Focus plugin: traps keyboard focus inside the modal
                           and prevents body scroll while open (accessibility)
        wire:keydown.escape → calls closeForm() when user presses Escape
        role/aria-*      → accessibility attributes for screen readers
    --}}
    @if ($showForm)
        <div role="dialog" aria-modal="true" aria-labelledby="emp-form-title"
             wire:key="emp-modal"
             x-data x-trap.noscroll="true"
             wire:keydown.escape="closeForm">

            {{-- $editingId is null for create, int for edit — same modal, two modes --}}
            <h2 id="emp-form-title">{{ $editingId ? 'Edit employee' : 'New employee' }}</h2>

            {{--
                wire:submit="save"  → intercepts the native form submit, prevents page reload,
                                      calls save() on the Livewire component via AJAX instead
                novalidate          → disables browser's own HTML5 validation so Livewire
                                      validation errors are shown instead
            --}}
            <form wire:submit="save" novalidate>

                {{--
                    wire:model (no modifier) → deferred: syncs value to PHP only on submit,
                    not on every keystroke. Correct for form fields — saves AJAX round-trips.
                --}}
                <div>
                    <label for="name">Name</label>
                    <input id="name" type="text" wire:model="name">
                    @error('name') <p>{{ $message }}</p> @enderror
                    {{-- @error() renders the validation error message for that field --}}
                </div>

                <div>
                    <label for="position">Position</label>
                    <input id="position" type="text" wire:model="position">
                    @error('position') <p>{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="formDepartmentId">Department</label>
                    <select id="formDepartmentId" wire:model="formDepartmentId">
                        <option value="">Select a department</option>
                        @foreach ($this->departments as $dept)
                            <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                        @endforeach
                    </select>
                    @error('formDepartmentId') <p>{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="basicSalary">Basic salary</label>
                    <input id="basicSalary" type="number" step="0.01" wire:model="basicSalary">
                    @error('basicSalary') <p>{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="allowance">Allowance</label>
                    <input id="allowance" type="number" step="0.01" wire:model="allowance">
                    @error('allowance') <p>{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="overtimeHours">Overtime hours</label>
                    <input id="overtimeHours" type="number" wire:model="overtimeHours">
                    @error('overtimeHours') <p>{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="hourlyRate">Hourly rate</label>
                    <input id="hourlyRate" type="number" step="0.01" wire:model="hourlyRate">
                    @error('hourlyRate') <p>{{ $message }}</p> @enderror
                </div>

                {{-- wire:click on Cancel calls closeForm(), which calls resetForm() in PHP --}}
                <button type="button" wire:click="closeForm">Cancel</button>

                {{-- Label changes based on mode; type="submit" triggers wire:submit="save" --}}
                <button type="submit">{{ $editingId ? 'Update' : 'Create' }}</button>

            </form>
        </div>
    @endif
</div>
