<?php

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'department_id' => ['required', 'integer', 'exists:departments,id'],
            'name' => ['required', 'string', 'max:255'],
            'position' => ['required', 'string', 'max:255'],
            'basic_salary' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'allowance' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'overtime_hours' => ['required', 'integer', 'min:0', 'max:744'],
            'hourly_rate' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
        ];
    }
}
