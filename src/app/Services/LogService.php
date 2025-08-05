<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

/**
 * Handles Transaction Setup communication with repositories.
 */
class LogService
{
    public const TOKEN_TO_CRM_PAYLOAD = 'token_to_crm_payload';
    public const TOKEN_TO_CRM_RESPONSE = 'token_to_crm_reponse';

    public const EMAIL_LINK_PAYLOAD = 'email_link_payload';
    public const EMAIL_LINK_RESPONSE = 'email_link_response';
    public const EMAIL_LINK_EXCEPTION = 'email_link_exception';

    public const SMS_LINK_PAYLOAD = 'sms_link_payload';
    public const SMS_LINK_RESPONSE = 'sms_link_response';
    public const SMS_LINK_EXCEPTION = 'sms_link_exception';

    public const CREATE_TRANSACTION_SETUP_PAYLOAD = 'create_transaction_setup_payload';
    public const CREATE_TRANSACTION_SETUP_RESPONSE = 'create_transaction_setup_response';

    public const ADD_CREDIT_CARD_PAYLOAD = 'add_credit_card_payload';
    public const ADD_CREDIT_CARD_RESPONSE = 'add_credit_card_response';

    public const CREDIT_CARD_AUTHORIZATION_PAYLOAD = 'credit_card_authorization_payload';
    public const CREDIT_CARD_AUTHORIZATION_RESPONSE = 'credit_card_authorization_response';

    public const CUSTOMER_SEARCH_PAYLOAD = 'customer_search_payload';
    public const CUSTOMER_SEARCH_RESPONSE = 'customer_search_response';

    public const CUSTOMER_WORLDPAY_REDIRECT = 'customer_worldpay_redirect';
    public const CUSTOMER_ADD_ACH_INFO = 'customer_add_ach_info';

    public const GET_PAYMENT_PROFILE = 'get_payment_profile';
    public const GET_PAYMENT_PROFILE_RESPONSE = 'get_payment_profile_response';

    public const GET_PAYMENT_PROFILES = 'get_payment_profiles';
    public const GET_PAYMENT_PROFILES_RESPONSE = 'get_payment_profiles_response';

    public const UPDATE_PAYMENT_PROFILE = 'update_payment_profile';
    public const UPDATE_PAYMENT_PROFILE_RESPONSE = 'update_payment_profile_response';
    public const UPDATE_PAYMENT_PROFILE_ERROR = 'update_payment_profile_error';

    public const GET_PAYMENT = 'get_payment';
    public const GET_PAYMENT_RESPONSE = 'get_payment_response';
    public const GET_PAYMENT_RESPONSE_ERROR = 'get_payment_response_error';

    public const GET_PAYMENT_IDS = 'get_payment_ids';
    public const GET_PAYMENT_IDS_RESPONSE = 'get_payment_response_ids';
    public const GET_PAYMENT_IDS_RESPONSE_ERROR = 'get_payment_ids_error';

    public const ADD_PAYMENT = 'add_payment';
    public const ADD_PAYMENT_RESPONSE = 'add_payment_response';
    public const ADD_PAYMENT_RESPONSE_ERROR = 'add_payment_response_error';

    public const REQUEST_RECEIVED = 'request_received';
    public const REQUEST_PROCESSED = 'request_processed';

    public const DATABASE_QUERY_EXECUTED = 'database_query_executed';

    public const AUTH0_USERS_BY_EMAIL_RESPONSE = 'auth0_users_by_email_response';

    public const APP_METRICS_REQUEST = 'app_metrics_request';
    public const APP_METRICS_RESPONSE = 'app_metrics_response';

    /**
     * Log Information.
     *
     * @param string $key
     * @param array<string, mixed>|null $payload
     * @param string|null $timeStart
     *
     * @return string
     */
    public function logInfo(string $key, array|null $payload = [], string|null $timeStart = null): string
    {
        $timeInfo = $this->getTimeInfo($timeStart);

        Log::info($key, [
            'session_id' => Session::getId(),
            'payload' => $payload,
            ...$timeInfo,
        ]);

        return (string) ($timeStart ? $timeInfo['end'] : $timeInfo['start']);
    }

    /**
     * Log Exception.
     *
     * @param string $key
     * @param \Throwable $th
     * @param string|null $timeStart
     * @return string
     */
    public function logThrowable(string $key, \Throwable $th, string $timeStart = null): string
    {
        $timeInfo = $this->getTimeInfo($timeStart);

        Log::error($key, [
            'session_id' => Session::getId(),
            'error' => [
                'message' => $th->getMessage(),
                'code' => $th->getCode(),
            ],
            ...$timeInfo,
        ]);

        return (string) $timeInfo['start'];
    }

    /**
     * @param string|null $timeStart
     *
     * @return array{start: string|null, end: string|null, elapsed: int|null}
     */
    protected function getTimeInfo(string|null $timeStart = null): array
    {
        $end = null;
        $elapsed = null;

        if ($timeStart !== null) {
            $end = $this->getCurrentFormattedDateTime();
            $elapsed = Carbon::parse($timeStart)->diffInMilliseconds();
        }

        return [
            'start' => $timeStart ?? $this->getCurrentFormattedDateTime(),
            'end' => $end,
            'elapsed' => $elapsed,
        ];
    }

    protected function getCurrentFormattedDateTime(): string
    {
        return Carbon::now()->toDateTimeString('millisecond');
    }
}
