<?php

namespace App\Livewire\Employees;

use App\Models\Department;
use App\Models\Employee;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Employees')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'search')]
    public string $search = '';

    #[Url(as: 'department_id')]
    public ?int $departmentId = null;

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $position = '';

    /** Form-bound department selection (separate from list filter $departmentId). */
    public int|string|null $formDepartmentId = null;

    public string $basicSalary = '';

    public string $allowance = '0.00';

    public int $overtimeHours = 0;

    public string $hourlyRate = '0.00';

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'position' => ['required', 'string', 'max:255'],
            'formDepartmentId' => ['required', 'integer', 'exists:departments,id'],
            'basicSalary' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'allowance' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'overtimeHours' => ['required', 'integer', 'min:0', 'max:744'],
            'hourlyRate' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'formDepartmentId' => 'department',
            'basicSalary' => 'basic salary',
            'overtimeHours' => 'overtime hours',
            'hourlyRate' => 'hourly rate',
        ];
    }

    public function store(): void
    {
        dd(1);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingDepartmentId(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'departmentId']);
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $employee = Employee::findOrFail($id);
        $this->editingId = $employee->id;
        $this->name = $employee->name;
        $this->position = $employee->position;
        $this->formDepartmentId = $employee->department_id;
        $this->basicSalary = (string)$employee->basic_salary;
        $this->allowance = (string)$employee->allowance;
        $this->overtimeHours = (int)$employee->overtime_hours;
        $this->hourlyRate = (string)$employee->hourly_rate;
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->resetForm();
    }

    public function save(): void
    {
        $data = $this->validate();

        $payload = [
            'name' => $data['name'],
            'position' => $data['position'],
            'department_id' => (int)$data['formDepartmentId'],
            'basic_salary' => $data['basicSalary'],
            'allowance' => $data['allowance'],
            'overtime_hours' => $data['overtimeHours'],
            'hourly_rate' => $data['hourlyRate'],
        ];

        if ($this->editingId === null) {
            Employee::create($payload);
            session()->flash('status', 'Employee created.');
            $this->resetPage();
        } else {
            Employee::findOrFail($this->editingId)->update($payload);
            session()->flash('status', 'Employee updated.');
        }

        $this->resetForm();
    }

    public function delete(int $id): void
    {
        $employee = Employee::findOrFail($id);

        if ($employee->payrollRecords()->exists()) {
            session()->flash('error', 'Cannot delete an employee with payroll records.');

            return;
        }

        $employee->delete();
        session()->flash('status', 'Employee deleted.');
    }

    private function resetForm(): void
    {
        $this->reset([
            'showForm', 'editingId', 'name', 'position', 'formDepartmentId',
            'basicSalary', 'allowance', 'overtimeHours', 'hourlyRate',
        ]);
        $this->allowance = '0.00';
        $this->hourlyRate = '0.00';
        $this->overtimeHours = 0;
        $this->resetErrorBag();
    }

    #[Computed]
    public function departments(): Collection
    {
        return Department::orderBy('name')->get();
    }

    public function render(): View
    {
        $employees = Employee::query()
            ->with('department')
            ->when($this->departmentId, fn($q, $id) => $q->where('department_id', $id))
            ->when($this->search !== '', fn($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->orderBy('name')
            ->paginate(10);

        return view('livewire.employees.index', compact('employees'));
    }
}
