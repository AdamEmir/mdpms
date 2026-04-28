<?php

use App\Http\Controllers\Payroll\PayrollController;
use App\Http\Controllers\Payroll\PayrollHistoryController;
use App\Http\Controllers\Payroll\PayslipController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('/payroll/run', [PayrollController::class, 'create'])->name('payroll.run');
    Route::post('/payroll/run', [PayrollController::class, 'store'])->name('payroll.process');
    Route::get('/payroll/history', [PayrollHistoryController::class, 'index'])->name('payroll.history');
    Route::get('/payroll/{record}/payslip', [PayslipController::class, 'show'])->name('payroll.payslip');
    Route::get('/payroll/{record}/payslip.pdf', [PayslipController::class, 'pdf'])->name('payroll.payslip.pdf');
});
