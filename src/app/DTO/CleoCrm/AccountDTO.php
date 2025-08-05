<?php

declare(strict_types=1);

namespace App\DTO\CleoCrm;

use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;

final class AccountDTO extends Data
{
    public function __construct(
        #[MapOutputName('account_id')]
        public string $id,
        #[MapOutputName('pest_routes_customer_id')]
        public int $externalRefId,
        #[MapOutputName('office_id')]
        public int $areaId,
        public int $dealerId,
        public string $contactId,
        public string $billingContactId,
        public string $serviceAddressId,
        public string $billingAddressId,
        public bool $isActive,
        public bool $paidInFull,
        public int|float $balanceAge,
        public int|float $responsibleBalanceAge,
        public int $preferredBillingDayOfMonth,
        public bool $smsReminders,
        public bool $phoneReminders,
        public bool $emailReminders,
        public string $createdAt,
        public string $updatedAt,
        public string|null $source = null,
        public int|float|null $balance = null,
        public int|float|null $responsibleBalance = null,
        public string|null $autopayType = null,
        public string|null $paymentHoldDate = null,
        public string|null $mostRecentCreditCardLastFour = null,
        public string|null $mostRecentCreditCardExpDate = null,
        public int|float|null $taxRate = null,
        public string|null $createdBy = null,
        public string|null $updatedBy = null,
    ) {
    }

    /**
     * @param object{
     *     id: string,
     *     external_ref_id: int,
     *     area_id: int,
     *     dealer_id: int,
     *     contact_id: string,
     *     billing_contact_id: string,
     *     service_address_id: string,
     *     billing_address_id: string,
     *     is_active: bool,
     *     source: string|null,
     *     autopay_type: string|null,
     *     paid_in_full: bool,
     *     balance: int|float,
     *     balance_age: int|float,
     *     responsible_balance: int|float,
     *     responsible_balance_age: int|float,
     *     preferred_billing_day_of_month: int,
     *     payment_hold_date: string|null,
     *     most_recent_credit_card_last_four: string|null,
     *     most_recent_credit_card_exp_date: string|null,
     *     sms_reminders: bool,
     *     phone_reminders: bool,
     *     email_reminders: bool,
     *     tax_rate: int|float,
     *     created_by: string|null,
     *     updated_by: string|null,
     *     created_at: string,
     *     updated_at: string,
     * } $data
     *
     * @return self
     */
    public static function fromApiResponse(object $data): self
    {
        return new self(
            id: $data->id,
            externalRefId: $data->external_ref_id,
            areaId: $data->area_id,
            dealerId: $data->dealer_id,
            contactId: $data->contact_id,
            billingContactId: $data->billing_contact_id,
            serviceAddressId: $data->service_address_id,
            billingAddressId: $data->billing_address_id,
            isActive: $data->is_active,
            paidInFull: $data->paid_in_full,
            balanceAge: $data->balance_age,
            responsibleBalanceAge: $data->responsible_balance_age,
            preferredBillingDayOfMonth: $data->preferred_billing_day_of_month,
            smsReminders: $data->sms_reminders,
            phoneReminders: $data->phone_reminders,
            emailReminders: $data->email_reminders,
            createdAt: $data->created_at,
            updatedAt: $data->updated_at,
            source: $data->source,
            balance: $data->balance,
            responsibleBalance: $data->responsible_balance,
            autopayType: $data->autopay_type,
            paymentHoldDate: $data->payment_hold_date,
            mostRecentCreditCardLastFour: $data->most_recent_credit_card_last_four,
            mostRecentCreditCardExpDate: $data->most_recent_credit_card_exp_date,
            taxRate: $data->tax_rate,
            createdBy: $data->created_by,
            updatedBy: $data->updated_by
        );
    }
}
