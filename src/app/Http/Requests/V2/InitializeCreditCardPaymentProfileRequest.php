<?php

declare(strict_types=1);

namespace App\Http\Requests\V2;

use App\Enums\Models\PaymentProfile\CardType;
use App\Enums\Models\PaymentProfile\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\NotIn;

/**
 * @property string $billing_name
 * @property string $billing_address_line_1
 * @property string $billing_address_line_2
 * @property string $billing_city
 * @property string $billing_state
 * @property string|int $billing_zip
 * @property string $card_type
 * @property string $description
 * @property string $cc_token
 * @property string $cc_expiration_month
 * @property string $cc_expiration_year
 * @property string $cc_last_four
 * @property bool $auto_pay
 */
final class InitializeCreditCardPaymentProfileRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, Enum|NotIn|string>>
     */
    public function rules(): array
    {
        return [
            'billing_name' => ['required', 'max:100'],
            'billing_address_line_1' => ['required', 'max:50'],
            'billing_address_line_2' => ['nullable', 'max:50'],
            'billing_city' => ['required', 'max:40'],
            'billing_state' => ['required', 'size:2', 'alpha'],
            'billing_zip' => ['required', 'size:5'],
            'card_type' => [
                'required',
                'string',
                Rule::notIn([
                    PaymentMethod::ACH->value,
                ]),
            ],
            'description' => ['nullable', 'string'],
            'cc_token' => ['required', 'string'],
            'cc_type' => [
                'required',
                new Enum(CardType::class),
            ],
            'cc_expiration_month' => ['required', 'integer', 'min:1', 'max:12'],
            'cc_expiration_year' => ['required', 'integer', 'min:' . date('Y')],
            'cc_last_four' => ['required', 'digits:4'],
            'auto_pay' => ['required', 'boolean']
        ];
    }
}
