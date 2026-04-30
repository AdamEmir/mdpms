<?php

namespace App\Livewire\Departments;

use App\Models\Department;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Departments')]
class Index extends Component
{
    use WithPagination;

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $name = '';

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('departments', 'name')->ignore($this->editingId),
            ],
        ];
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $department = Department::findOrFail($id);
        $this->editingId = $department->id;
        $this->name = $department->name;
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->resetForm();
    }

    public function save(): void
    {
        $data = $this->validate();

        if ($this->editingId === null) {
            Department::create($data);
            session()->flash('status', 'Department created.');
        } else {
            Department::findOrFail($this->editingId)->update($data);
            session()->flash('status', 'Department updated.');
        }

        $this->resetForm();
        $this->resetPage();
    }

    public function delete(int $id): void
    {
        $department = Department::findOrFail($id);

        if ($department->employees()->exists()) {
            session()->flash('error', 'Cannot delete a department with employees. Reassign or remove them first.');

            return;
        }

        $department->delete();
        session()->flash('status', 'Department deleted.');
    }

    private function resetForm(): void
    {
        $this->reset(['showForm', 'editingId', 'name']);
        $this->resetErrorBag();
    }

    public function render(): View
    {
        return view('livewire.departments.index', [
            'departments' => Department::query()
                ->withCount('employees')
                ->orderBy('name')
                ->paginate(10),
        ]);
    }
}
