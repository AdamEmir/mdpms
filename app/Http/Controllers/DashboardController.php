<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Employee;
use App\Models\PayrollRecord;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $now = now();

        return view('dashboard', [
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
