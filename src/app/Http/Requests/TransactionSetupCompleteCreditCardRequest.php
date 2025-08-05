<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransactionSetupCompleteCreditCardRequest extends FormRequest
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
            'HostedPaymentStatus' => 'required|max:128',
            'ValidationCode' => 'required|max:128',
            'PaymentAccountID' => 'nullable|max:128',
        ];
    }
}
