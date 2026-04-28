<?php

namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\PayrollRecord;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PayrollHistoryController extends Controller
{
    public function index(Request $request): View
    {
        $month = $request->integer('month') ?: null;
        $year = $request->integer('year') ?: null;
        $departmentId = $request->integer('department_id') ?: null;
        $search = trim((string) $request->string('search'));

        $records = PayrollRecord::query()
            ->with(['employee.department'])
            ->when($month, fn ($q, $m) => $q->where('month', $m))
            ->when($year, fn ($q, $y) => $q->where('year', $y))
            ->when($departmentId, function ($q, $id) {
                $q->whereHas('employee', fn ($eq) => $eq->where('department_id', $id));
            })
            ->when($search !== '', function ($q) use ($search) {
                $q->whereHas('employee', fn ($eq) => $eq->where('name', 'like', "%{$search}%"));
            })
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->orderBy('id')
            ->paginate(10)
            ->withQueryString();

        return view('payroll.history', [
            'records' => $records,
            'departments' => Department::orderBy('name')->get(),
            'filters' => [
                'month' => $month,
                'year' => $year,
                'department_id' => $departmentId,
                'search' => $search,
            ],
        ]);
    }
}