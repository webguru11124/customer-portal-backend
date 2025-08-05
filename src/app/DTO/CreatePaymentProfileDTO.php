<?php

namespace App\DTO;

use App\Enums\PestRoutes\PaymentProfile\AccountType;
use App\Enums\PestRoutes\PaymentProfile\CheckType;
use Aptive\PestRoutesSDK\Resources\PaymentProfiles\PaymentProfilePaymentMethod;
use Illuminate\Validation\Rule;

/**
 * DTO for Creating Payment Profile.
 */
class CreatePaymentProfileDTO extends BaseDTO
{
    public function __construct(
        public int $customerId,
        public PaymentProfilePaymentMethod $paymentMethod,
        public string|null $token,
        public string|null $billingName,
        public string|null $billingAddressLine1,
        public string|null $billingAddressLine2,
        public string|null $billingCity,
        public string|null $billingState,
        public string|null $billingZip,
        public string|null $bankName,
        public string|null $accountNumber,
        public string|null $routingNumber,
        public CheckType|null $checkType,
        public AccountType|null $accountType,
        public bool|null $auto_pay
    ) {
        $this->validateData();
    }

    /**
     * @inheritdoc
     */
    public function getRules(): array
    {
        return [
            'customerId' => 'required|int|gt:0',
            'token' =>  [Rule::requiredIf($this->paymentMethod === PaymentProfilePaymentMethod::AutoPayCC)],
            'billingName' => [Rule::requiredIf($this->paymentMethod === PaymentProfilePaymentMethod::AutoPayACH), 'max:128'],
            'billingAddressLine1' => [Rule::requiredIf($this->paymentMethod === PaymentProfilePaymentMethod::AutoPayACH), 'max:128'],
            'billingAddressLine2' => ['sometimes', 'max:128'],
            'billingCity' => [Rule::requiredIf($this->paymentMethod === PaymentProfilePaymentMethod::AutoPayACH), 'max:64'],
            'billingState' => [Rule::requiredIf($this->paymentMethod === PaymentProfilePaymentMethod::AutoPayACH), 'nullable', 'size:2', 'alpha'],
            'billingZip' => [Rule::requiredIf($this->paymentMethod === PaymentProfilePaymentMethod::AutoPayACH), 'nullable', 'size:5'],
            'bankName' => [Rule::requiredIf($this->paymentMethod === PaymentProfilePaymentMethod::AutoPayACH), 'max:128'],
            'accountNumber' => ['nullable', Rule::requiredIf($this->paymentMethod === PaymentProfilePaymentMethod::AutoPayACH), 'numeric'],
            'routingNumber' => ['nullable', Rule::requiredIf($this->paymentMethod === PaymentProfilePaymentMethod::AutoPayACH), 'numeric'],
            'checkType' => ['nullable', Rule::requiredIf($this->paymentMethod === PaymentProfilePaymentMethod::AutoPayACH)],
            'accountType' => ['nullable'],
            'auto_pay' => 'required',
        ];
    }
}
