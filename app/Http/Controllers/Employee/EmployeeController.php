<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\StoreEmployeeRequest;
use App\Http\Requests\Employee\UpdateEmployeeRequest;
use App\Models\Department;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function index(Request $request): View
    {
        $departmentId = $request->integer('department_id') ?: null;
        $search = trim((string) $request->string('search'));

        $employees = Employee::query()
            ->with('department')
            ->when($departmentId, fn ($q, $id) => $q->where('department_id', $id))
            ->when($search !== '', fn ($q) => $q->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('employees.index', [
            'employees' => $employees,
            'departments' => Department::orderBy('name')->get(),
            'selectedDepartmentId' => $departmentId,
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        return view('employees.create', [
            'departments' => Department::orderBy('name')->get(),
        ]);
    }

    public function store(StoreEmployeeRequest $request): RedirectResponse
    {
        Employee::create($request->validated());

        return redirect()
            ->route('employees.index')
            ->with('status', 'Employee created.');
    }

    public function edit(Employee $employee): View
    {
        return view('employees.edit', [
            'employee' => $employee,
            'departments' => Department::orderBy('name')->get(),
        ]);
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee): RedirectResponse
    {
        $employee->update($request->validated());

        return redirect()
            ->route('employees.index')
            ->with('status', 'Employee updated.');
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        if ($employee->payrollRecords()->exists()) {
            return redirect()
                ->route('employees.index')
                ->with('error', 'Cannot delete an employee with payroll records.');
        }

        $employee->delete();

        return redirect()
            ->route('employees.index')
            ->with('status', 'Employee deleted.');
    }
}
