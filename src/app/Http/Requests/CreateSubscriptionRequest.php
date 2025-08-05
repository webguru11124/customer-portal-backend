<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @property int $plan_id
 * @property int $plan_price_initial
 * @property int $plan_price_per_treatment
 * @property int $agreement_length
 * @property array|null $initial_addons
 * @property array|null $recurring_addons
 */
class CreateSubscriptionRequest extends FormRequest
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
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'plan_id' => 'required|int',
            'plan_price_initial' => 'required|int|gte:0',
            'plan_price_per_treatment' => 'required|int|gte:0',
            'agreement_length' => 'required|int|max:999|gt:0',
            'initial_addons' => 'nullable|array',
            'initial_addons.*.product_id' => 'required|int|gt:0',
            'initial_addons.*.amount' => 'nullable|numeric|gte:0',
            'initial_addons.*.name' => 'nullable|string',
            'initial_addons.*.quantity' => 'nullable|int',
            'initial_addons.*.taxable' => 'nullable|boolean',
            'recurring_addons' => 'nullable|array',
            'recurring_addons.*.product_id' => 'required|int|gt:0',
            'recurring_addons.*.amount' => 'nullable|numeric|gte:0',
            'recurring_addons.*.name' => 'nullable|string',
            'recurring_addons.*.quantity' => 'nullable|int',
            'recurring_addons.*.taxable' => 'nullable|boolean',
        ];
    }
}
