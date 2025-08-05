<?php

declare(strict_types=1);

namespace App\DTO\Payment;

use App\DTO\BaseDTO;
use App\Enums\Models\Payment\PaymentGateway;
use App\Enums\Models\PaymentProfile\CardType;
use App\Enums\Models\PaymentProfile\PaymentMethod;
use App\Enums\PaymentService\PaymentProfile\AccountType;
use Illuminate\Validation\Rule;

final class CreatePaymentProfileRequestDTO extends BaseDTO
{
    public function __construct(
        public int $customerId,
        public PaymentGateway $gatewayId,
        public PaymentMethod $type,
        public string $firstName,
        public string $lastName,
        public string $addressLine1,
        public string $email,
        public string $city,
        public string $province,
        public string $postalCode,
        public string $countryCode,
        public bool $isPrimary = false,
        public bool $isAutoPay = false,
        public string|null $addressLine2 = null,
        public string|null $addressLine3 = null,
        public string|null $achAccountNumber = null,
        public string|null $achRoutingNumber = null,
        public string|null $ccToken = null,
        public CardType|null $ccType = null,
        public int|null $ccExpirationMonth = null,
        public int|null $ccExpirationYear = null,
        public string|null $ccLastFour = null,
        public string|null $description = null,
        public string|null $achAccountLastFour = null,
        public bool $shouldSkipGatewayValidation = true,
        public string|null $achBankName = null,
        public AccountType|null $achAccountTypeId = null,
    ) {
        $this->validateData();
    }

    /**
     * @return array<string, array<int, \Illuminate\Validation\Rules\In|string>>
     */
    public function getRules(): array
    {
        return [
            'customer_id' => ['required', 'integer', 'gt:0'],
            'gateway_id' => [
                'required',
                'integer',
                Rule::in([
                    PaymentGateway::PAYMENT_GATEWAY_WORLDPAY_ID->value,
                    PaymentGateway::PAYMENT_GATEWAY_WORLDPAY_TOKENEX_TRANSPARENT_ID->value,
                    PaymentGateway::PAYMENT_GATEWAY_TOKENEX_ID->value,
                ]),
            ],
            'type' => [
                'required',
                'string',
                Rule::in([
                    PaymentMethod::ACH->value,
                    PaymentMethod::CREDIT_CARD->value,
                ]),
            ],
            'first_name' => ['required', 'string'],
            'last_name' => ['required', 'string'],
            'description' => ['nullable', 'string'],
            'ach_account_number' => [
                'exclude_unless:type,' . PaymentMethod::ACH->value,
                'required',
                'string'
            ],
            'ach_routing_number' => [
                'exclude_unless:type,' . PaymentMethod::ACH->value,
                'required',
                'string'
            ],
            'cc_token' => [
                'exclude_if:type,' . PaymentMethod::ACH->value,
                'required',
                'string'
            ],
            'cc_type' => [
                'exclude_if:type,' . PaymentMethod::ACH->value,
                'required',
                'string'
            ],
            'cc_expiration_month' => [
                'exclude_if:type,' . PaymentMethod::ACH->value,
                'required',
                'integer',
                'min:1',
                'max:12'
            ],
            'cc_expiration_year' => [
                'exclude_if:type,' . PaymentMethod::ACH->value,
                'required',
                'integer',
                'min:' . date('Y'),
            ],
            'address_line1' => ['required', 'string'],
            'address_line2' => ['nullable', 'string'],
            'address_line3' => ['nullable', 'string'],
            'email' => ['required', 'string'],
            'city' => ['required', 'string'],
            'province' => ['required', 'string', 'min:2', 'max:2'],
            'postal_code' => ['required', 'string'],
            'country_code' => ['required', 'string', 'min:2', 'max:2'],
            'is_primary' => ['required', 'boolean'],
            'ach_account_last_four' => ['nullable', 'string'],
            'should_skip_gateway_validation' => ['boolean'],
            'ach_bank_name' => ['nullable', 'string'],
            'ach_account_type_id' => [
                'nullable',
                'string',
                Rule::in([
                    AccountType::PERSONAL_CHECKING->value,
                    AccountType::PERSONAL_SAVINGS->value,
                    AccountType::BUSINESS_CHECKING->value,
                    AccountType::BUSINESS_SAVINGS->value
                ])
            ],
        ];
    }

    public function toArray(): array
    {
        $request = [
            'customer_id' => $this->customerId,
            'gateway_id' => $this->gatewayId->value,
            'type' => $this->type->value,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'description' => $this->description,
            'ach_account_number' => (string) $this->achAccountNumber,
            'ach_routing_number' => (string) $this->achRoutingNumber,
            'cc_type' => $this->ccType?->value,
            'cc_token' => $this->ccToken,
            'cc_expiration_month' => $this->ccExpirationMonth,
            'cc_expiration_year' => $this->ccExpirationYear,
            'address_line1' => $this->addressLine1,
            'address_line2' => $this->addressLine2,
            'address_line3' => $this->addressLine3,
            'email' => $this->email,
            'city' => $this->city,
            'province' => $this->province,
            'postal_code' => $this->postalCode,
            'country_code' => $this->countryCode,
            'is_primary' => $this->isPrimary,
            'ach_account_last_four' => $this->achAccountLastFour,
            'should_skip_gateway_validation' => $this->shouldSkipGatewayValidation,
            'ach_bank_name' => $this->achBankName,
            'ach_account_type_id' => $this->achAccountTypeId?->value,
            'cc_last_four' => $this->ccLastFour,
        ];

        return array_filter($request, static fn ($item) => !(is_null($item)));
    }
}
