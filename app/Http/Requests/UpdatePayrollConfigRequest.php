<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePayrollConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'start_year_month' => ['sometimes', 'required', 'string', 'regex:/^\d{4}-\d{2}$/'],
            'first_start_day' => ['sometimes', 'required', 'integer', 'min:1', 'max:31'],
            'first_end_day' => ['sometimes', 'required', 'integer', 'min:1', 'max:31'],
            'second_start_day' => ['sometimes', 'required', 'integer', 'min:1', 'max:31'],
            'second_end_day' => ['sometimes', 'required', 'integer', 'min:1', 'max:31'],
            'pay_date_offset' => ['sometimes', 'required', 'integer', 'min:0'],
            '*' => [function ($attribute, $value, $fail) {
                $data = $this->validationData();
                if (
                    (isset($data['first_start_day']) && isset($data['first_end_day']) && $data['first_start_day'] > $data['first_end_day']) ||
                    (isset($data['second_start_day']) && isset($data['second_end_day']) && $data['second_start_day'] > $data['second_end_day']) ||
                    (isset($data['first_end_day']) && isset($data['second_start_day']) && $data['first_end_day'] >= $data['second_start_day'])
                ) {
                    $fail('Invalid payroll cycle day range: ensure first_start_day <= first_end_day < second_start_day <= second_end_day.');
                }
            }],
        ];
    }
}
