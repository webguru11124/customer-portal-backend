<?php

declare(strict_types=1);

namespace App\Http\Requests\V2;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @property string $payment_method_id
 * @property integer $amount_cents
 */
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
     * @return array<string, string|array<int, string>>
     */
    public function rules(): array
    {
        return [
            'payment_method_id' => 'required|string|uuid',
            'amount_cents' => 'required|integer|gt:0',
        ];
    }
}
