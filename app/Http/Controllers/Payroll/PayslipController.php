<?php

namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\Controller;
use App\Models\PayrollRecord;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class PayslipController extends Controller
{
    public function pdf(PayrollRecord $record): Response
    {
        $record->load('employee.department');

        $filename = sprintf(
            'payslip-%s-%04d-%02d.pdf',
            str()->slug($record->employee->name),
            $record->year,
            $record->month,
        );

        return Pdf::loadView('payroll.payslip-pdf', ['record' => $record])
            ->setPaper('a4')
            ->download($filename);
    }
}
