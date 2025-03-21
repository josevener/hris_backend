<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePayrollRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'employee_id' => 'nullable|exists:employees,id',
            'salary_id' => 'nullable|exists:salaries,id',
            'payroll_cycles_id' => 'required|exists:payroll_cycles,id',
            'pay_date' => 'nullable|date',
            'total_earnings' => 'nullable|numeric',
            'total_deductions' => 'nullable|numeric',
            'net_salary' => 'nullable|numeric',
            'status' => 'required|in:pending,processed,paid',
        ];
    }
}
