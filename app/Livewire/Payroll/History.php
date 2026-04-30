<?php

namespace App\Livewire\Payroll;

use App\Models\Department;
use App\Models\PayrollRecord;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Payroll history')]
class History extends Component
{
    use WithPagination;

    #[Url(as: 'search')]
    public string $search = '';

    #[Url(as: 'month')]
    public ?int $month = null;

    #[Url(as: 'year')]
    public ?int $year = null;

    #[Url(as: 'department_id')]
    public ?int $departmentId = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingMonth(): void
    {
        $this->resetPage();
    }

    public function updatingYear(): void
    {
        $this->resetPage();
    }

    public function updatingDepartmentId(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'month', 'year', 'departmentId']);
        $this->resetPage();
    }

    #[Computed]
    public function departments(): Collection
    {
        return Department::orderBy('name')->get();
    }

    public function render(): View
    {
        $records = PayrollRecord::query()
            ->with(['employee.department'])
            ->when($this->month, fn ($q, $m) => $q->where('month', $m))
            ->when($this->year, fn ($q, $y) => $q->where('year', $y))
            ->when($this->departmentId, function ($q, $id) {
                $q->whereHas('employee', fn ($eq) => $eq->where('department_id', $id));
            })
            ->when($this->search !== '', function ($q) {
                $q->whereHas('employee', fn ($eq) => $eq->where('name', 'like', "%{$this->search}%"));
            })
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->orderBy('id')
            ->paginate(10);

        return view('livewire.payroll.history', compact('records'));
    }
}
