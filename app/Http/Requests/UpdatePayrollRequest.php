<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePayrollRequest extends FormRequest
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
            'employee_id' => 'required|exists:employees,id',
            'salary_id' => 'required|exists:salaries,id',
            'pay_date' => 'required|date',
            'start_date' => 'required|date|before_or_equal:pay_date',
            'end_date' => 'required|date|after_or_equal:start_date|before_or_equal:pay_date',
            'status' => 'required|in:pending,processed,paid',
        ];
    }
}
