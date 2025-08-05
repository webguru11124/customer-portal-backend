<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransactionSetupCreateCreditCardRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, string|array<int, string|Rule>>
     */
    public function rules(): array
    {
        return [
            'billing_name' => 'required|max:100',
            'billing_address_line_1' => 'required|max:50',
            'billing_address_line_2' => 'sometimes|max:50',
            'billing_city' => 'required|max:40',
            'billing_state' => 'required|size:2|alpha',
            'billing_zip' => 'required|size:5',
        ];
    }
}
