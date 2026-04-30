<?php

use App\Http\Controllers\Payroll\PayslipController;
use App\Livewire\Payroll\History;
use App\Livewire\Payroll\Payslip;
use App\Livewire\Payroll\Run;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('/payroll/run', Run::class)->name('payroll.run');
    Route::get('/payroll/history', History::class)->name('payroll.history');
    Route::get('/payroll/{record}/payslip', Payslip::class)->name('payroll.payslip');
    Route::get('/payroll/{record}/payslip.pdf', [PayslipController::class, 'pdf'])->name('payroll.payslip.pdf');
});
