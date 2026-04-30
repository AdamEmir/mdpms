<?php

namespace App\Livewire;

use App\Models\Department;
use App\Models\Employee;
use App\Models\PayrollRecord;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Dashboard')]
class Dashboard extends Component
{
    public function render(): View
    {
        $now = now();

        return view('livewire.dashboard', [
            'departmentsCount' => Department::count(),
            'employeesCount' => Employee::count(),
            'payrollThisMonth' => PayrollRecord::query()
                ->where('month', $now->month)
                ->where('year', $now->year)
                ->count(),
            'currentMonthLabel' => $now->format('F Y'),
        ]);
    }
}
