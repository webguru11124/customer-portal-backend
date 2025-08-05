<?php

namespace App\Http\Requests;

use App\Enums\Models\PaymentProfile\PaymentMethod;
use App\Enums\Models\PaymentProfile\StatusType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Symfony\Component\HttpFoundation\ParameterBag;

class GetPaymentProfilesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, Enum|string>|string>
     */
    public function rules(): array
    {
        return [
            'statuses' => 'nullable|array',
            'statuses.*' => ['nullable', new Enum(StatusType::class), 'distinct'],
            'paymentMethods' => 'nullable|array',
            'paymentMethods.*' => ['nullable', new Enum(PaymentMethod::class), 'distinct'],
        ];
    }

    /**
     * @inheritdoc
     */
    protected function prepareForValidation()
    {
        $this->prepareArray('statuses');
        $this->prepareArray('paymentMethods');
    }

    /**
     * @param string $propertyName
     */
    protected function prepareArray(string $propertyName): void
    {
        /** @var ParameterBag|null $inputSource */
        $inputSource = $this->getInputSource();

        if (!isset($this->$propertyName) || !is_string($this->$propertyName)) {
            return;
        }

        $inputSource?->set($propertyName, explode(',', $this->$propertyName));
    }

    /**
     * @return StatusType[]
     */
    public function statusesAsEnums(): array
    {
        return array_map(
            fn ($status) => StatusType::from($status),
            $this->input('statuses', [])
        );
    }

    /**
     * @return PaymentMethod[]
     */
    public function paymentMethodsAsEnums(): array
    {
        return array_map(
            fn ($method) => PaymentMethod::from($method),
            $this->input('paymentMethods', [])
        );
    }
}
