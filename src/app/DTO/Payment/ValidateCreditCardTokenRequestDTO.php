<?php

declare(strict_types=1);

namespace App\DTO\Payment;

use App\DTO\BaseDTO;
use App\Enums\Models\Payment\PaymentGateway;
use Illuminate\Validation\ValidationException;
use Spatie\LaravelData\Attributes\MapOutputName;

final class ValidateCreditCardTokenRequestDTO extends BaseDTO
{
    /**
     * @throws ValidationException
     */
    public function __construct(
        #[MapOutputName('gateway_id')]
        public PaymentGateway $gateway,
        /** @var array<int, string> $origins */
        #[MapOutputName('office_id')]
        public int|null $officeId,
        #[MapOutputName('cc_token')]
        public string $ccToken,
        #[MapOutputName('cc_expiration_month')]
        public int $ccExpirationMonth,
        #[MapOutputName('cc_expiration_year')]
        public int $ccExpirationYear,
    ) {
        $this->validateData();
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function getRules(): array
    {
        return [
            'office_id' => [
                'exclude_if:type,' . PaymentGateway::PAYMENT_GATEWAY_TOKENEX_ID->value,
                'required',
                'integer'
            ],
            'cc_token' => ['required', 'string'],
            'cc_expiration_month' => ['required', 'integer', 'min:1', 'max:12'],
            'cc_expiration_year' => ['required', 'integer', 'min:' . date('Y')],
        ];
    }

    public function toArray(): array
    {
        return [
            'gateway_id' => $this->gateway->value,
            'office_id' => $this->officeId,
            'cc_token' => $this->ccToken,
            'cc_expiration_month' => $this->ccExpirationMonth,
            'cc_expiration_year' => $this->ccExpirationYear,
        ];
    }
}
