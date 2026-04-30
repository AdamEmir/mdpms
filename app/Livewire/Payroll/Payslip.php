<?php

namespace App\Livewire\Payroll;

use App\Models\PayrollRecord;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Payslip')]
class Payslip extends Component
{
    public PayrollRecord $record;

    public function mount(PayrollRecord $record): void
    {
        $this->record = $record->load('employee.department');
    }

    public function render(): View
    {
        return view('livewire.payroll.payslip');
    }
}
