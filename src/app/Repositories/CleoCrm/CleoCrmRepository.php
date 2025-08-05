<?php

declare(strict_types=1);

namespace App\Repositories\CleoCrm;

use App\DTO\CleoCrm\AccountDTO;
use App\Interfaces\Repository\CleoCrmRepository as CleoCrmRepositoryInterface;
use GuzzleHttp\Exception\GuzzleException;

class CleoCrmRepository extends CleoCrmBaseRepository implements CleoCrmRepositoryInterface
{
    public const ACCOUNTS_ENDPOINT = 'api/v1/accounts';

    /**
     * @throws GuzzleException
     * @throws \JsonException
     */
    public function getAccount(
        int $pestRoutesCustomerAccountId
    ): AccountDTO|null {
        /**
         * @param object{
         *     id: string,external_ref_id: int,area_id: int,dealer_id: int,contact_id: string,billing_contact_id: string,service_address_id: string,billing_address_id: string,is_active: bool,source: string,autopay_type: string|null,paid_in_full: bool,balance: int|float,balance_age: int|float,responsible_balance: int|float,responsible_balance_age: int|float,preferred_billing_day_of_month: int,payment_hold_date: string|null,most_recent_credit_card_last_four: string|null,most_recent_credit_card_exp_date: string|null,sms_reminders: bool,phone_reminders: bool,email_reminders: bool,tax_rate: int|float,created_by: string|null,updated_by: string|null,created_at: string,updated_at: string
         * }[] $response
         */
        $response = $this->sendGetRequest(
            self::ACCOUNTS_ENDPOINT,
            [
                'external_ref_id' => $pestRoutesCustomerAccountId,
            ]
        );

        //@phpstan-ignore-next-line
        return 0 !== count($response) ? AccountDTO::fromApiResponse(current($response)) : null;
    }
}
