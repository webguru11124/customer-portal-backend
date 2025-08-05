<?php

namespace App\Http\Requests;

use App\Enums\Models\Payment\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreatePaymentRequest extends FormRequest
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
            'payment_profile_id' => 'required|gt:0',
            'amount_cents' => 'required|gt:0',
            'payment_method' => ['required', Rule::in([PaymentMethod::CREDIT_CARD->value, PaymentMethod::ACH->value])],
        ];
    }
}
