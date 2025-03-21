<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEmployeeRequest extends FormRequest
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
            'company_id_number' => 'required|string',
            'birthdate' => 'nullable|date',
            'reports_to' => 'nullable|string',
            'gender' => 'nullable|string',
            'user_id' => 'required|numeric',
            'department_id' => 'required|numeric',
            'designation_id' => 'required|numeric',
        ];
    }
}
