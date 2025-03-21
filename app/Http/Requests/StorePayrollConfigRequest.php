<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePayrollConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'start_year_month' => ['required', 'string', 'regex:/^\d{4}-\d{2}$/'],
            'first_start_day' => ['required', 'integer', 'min:1', 'max:31'],
            'first_end_day' => ['required', 'integer', 'min:1', 'max:31'],
            'second_start_day' => ['required', 'integer', 'min:1', 'max:31'],
            'second_end_day' => ['required', 'integer', 'min:1', 'max:31'],
            'pay_date_offset' => ['required', 'integer', 'min:0'],
            '*' => [function ($attribute, $value, $fail) {
                $data = $this->validationData();
                if (
                    $data['first_start_day'] > $data['first_end_day'] ||
                    $data['second_start_day'] > $data['second_end_day'] ||
                    $data['first_end_day'] >= $data['second_start_day']
                ) {
                    $fail('Invalid payroll cycle day range: ensure first_start_day <= first_end_day < second_start_day <= second_end_day.');
                }
            }],
        ];
    }

    // public function messages(): array
    // {
    //     return [
    //         'start_year_month.regex' => 'The start year and month must be in YYYY-MM format.',
    //     ];
    // }
}
