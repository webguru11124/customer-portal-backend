<?php

declare(strict_types=1);

namespace App\DTO\Payment;

use App\Enums\Models\Payment\PaymentMethod as PaymentMethodEnum;
use Spatie\LaravelData\Data;

final class PaymentMethod extends Data
{
    /**
     * @param BasePaymentMethod|CreditCardPaymentMethod|AchPaymentMethod $basePaymentMethod
     */
    private function __construct(
        public BasePaymentMethod $basePaymentMethod
    ) {
    }

    /**
     * @param object{
     *     payment_method_id: string,
     *     account_id: string,
     *     type: string,
     *     date_added: string,
     *     is_primary: bool,
     *     is_autopay: bool,
     *     description: string|null,
     *     cc_type: string|null,
     *     cc_last_four: string|null,
     *     cc_expiration_month: int|null,
     *     cc_expiration_year: int|null,
     *     ach_account_last_four: string|null,
     *     ach_routing_number: string|null,
     *     ach_account_type: string|null,
     *     ach_bank_name: string|null
     * } $data
     *
     * @return self
     */
    public static function fromApiResponse(object $data): self
    {
        $type = PaymentMethodEnum::from(strtoupper($data->type));

        if(PaymentMethodEnum::ACH === $type) {
            $achPaymentMethod = new AchPaymentMethod(
                paymentMethodId: $data->payment_method_id,
                crmAccountId: $data->account_id,
                type: $data->type,
                dateAdded: $data->date_added,
                isPrimary: $data->is_primary,
                description: $data->description,
                isAutoPay: $data->is_autopay ?? false,
                achAccountLastFour: $data->ach_account_last_four ?? null,
                achRoutingNumber: $data->ach_routing_number ?? null,
                achAccountType: $data->ach_account_type ?? null,
                achBankName: $data->ach_bank_name ?? null,
            );

            return new self(
                basePaymentMethod: $achPaymentMethod
            );
        }

        $creditCardPaymentMethod = new CreditCardPaymentMethod(
            paymentMethodId: $data->payment_method_id,
            crmAccountId: $data->account_id,
            type: $data->type,
            dateAdded: $data->date_added,
            isPrimary: $data->is_primary,
            description: $data->description,
            isAutoPay: $data->is_autopay ?? false,
            ccType: $data->cc_type ?? null,
            ccLastFour: $data->cc_last_four ?? null,
            ccExpirationMonth: $data->cc_expiration_month ?? null,
            ccExpirationYear: $data->cc_expiration_year ?? null,
        );

        return new self(
            basePaymentMethod: $creditCardPaymentMethod
        );
    }

    public function setIsExpired(bool $isExpired): void
    {
        $this->basePaymentMethod->isExpired = $isExpired;
    }
}
