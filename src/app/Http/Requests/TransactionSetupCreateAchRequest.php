<?php

namespace App\Http\Requests;

use App\Enums\PestRoutes\PaymentProfile\AccountType;
use App\Enums\PestRoutes\PaymentProfile\CheckType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransactionSetupCreateAchRequest extends FormRequest
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
            'customer_id' => 'required|int|gt:0',
            'billing_name' => 'required|max:128',
            'billing_address_line_1' => 'required|max:128',
            'billing_address_line_2' => 'sometimes|max:128',
            'billing_city' => 'required|max:66',
            'billing_state' => 'required|size:2|alpha',
            'billing_zip' => 'required|size:5',
            'bank_name' => 'required|max:128',
            'account_number' => 'required|max:64',
            'account_number_confirmation' => 'required|max:64|same:account_number',
            'routing_number' => 'required|max:64',
            'check_type' => ['required', Rule::in([CheckType::BUSINESS->value, CheckType::PERSONAL->value])],
            'account_type' => ['nullable', Rule::in([AccountType::CHECKING->value, AccountType::SAVINGS->value])],
        ];
    }
}
