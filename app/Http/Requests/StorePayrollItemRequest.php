<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePayrollItemRequest extends FormRequest
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
            'employee_id' => 'nullable|exists:employees,id|required_if:scope,specific',
            'payroll_cycles_id' => 'required|numeric',
            'scope' => 'required|in:specific,global',
            'type' => 'required|in:earning,deduction,contribution',
            'category' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
        ];
    }
}
