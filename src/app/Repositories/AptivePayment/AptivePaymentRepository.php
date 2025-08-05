<?php

declare(strict_types=1);

namespace App\Repositories\AptivePayment;

use App\DTO\Payment\AuthAndCapture;
use App\DTO\Payment\AuthAndCaptureRequestDTO;
use App\DTO\Payment\AutoPayStatus;
use App\DTO\Payment\AutoPayStatusRequestDTO;
use App\DTO\Payment\BasePaymentMethod;
use App\DTO\Payment\CreatePaymentProfileRequestDTO;
use App\DTO\Payment\Payment;
use App\DTO\Payment\PaymentMethod;
use App\DTO\Payment\PaymentMethodsListRequestDTO;
use App\DTO\Payment\PaymentProfile;
use App\DTO\Payment\PaymentsListRequestDTO;
use App\DTO\Payment\TokenexAuthKeys;
use App\DTO\Payment\TokenexAuthKeysRequestDTO;
use App\DTO\Payment\ValidateCreditCardTokenRequestDTO;
use App\Events\Payment\PaymentMade;
use App\Exceptions\Account\CleoCrmAccountNotFoundException;
use App\Exceptions\Payment\CreditCardTokenNotFoundException;
use App\Helpers\PaymentMethodValidator;
use App\Interfaces\Repository\CleoCrmRepository;
use App\Logging\ApiLogger;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Contracts\Config\Repository;

class AptivePaymentRepository extends AptivePaymentBaseRepository
{
    public const TOKENEX_AUTH_KEYS_ENDPOINT = 'api/v1/gateways/tokenex/authentication-keys';
    public const AUTOPAY_STATUS_ENDPOINT = 'api/v1/accounts/%s/autopay-status';
    public const PAYMENTS_LIST_ENDPOINT = 'api/v1/payments';
    public const AUTH_AND_CAPTURE_ENDPOINT = 'api/v1/payments/authorization-and-capture';
    public const PAYMENT_METHODS_ENDPOINT = 'api/v1/payment-methods';
    public const PAYMENT_METHOD_UPDATE_ENDPOINT = 'api/v1/payment-methods/%s';
    public const CREDIT_CARD_TOKEN_VALIDATION_ENDPOINT = 'api/v1/credit-cards/validation';

    public function __construct(
        Client $guzzleClient,
        Repository $config,
        ApiLogger $logger,
        private readonly PaymentMethodValidator $paymentMethodValidator,
        private readonly CleoCrmRepository $cleoCrmRepository,
    ) {
        parent::__construct(
            guzzleClient: $guzzleClient,
            config: $config,
            logger: $logger,
            cleoCrmRepository: $this->cleoCrmRepository
        );
    }

    /**
     * @throws GuzzleException
     * @throws \JsonException
     * @throws CleoCrmAccountNotFoundException
     */
    public function getTokenexAuthKeys(
        TokenexAuthKeysRequestDTO $tokenexAuthKeysRequestDTO
    ): TokenexAuthKeys {
        /**
         * @var object{
         *     _metadata: object{success: bool, links: object{self: string}},
         *     result: object{message: string, authentication_key: string},
         * } $response
         */
        $response = $this->sendPostRequest(
            self::TOKENEX_AUTH_KEYS_ENDPOINT,
            $tokenexAuthKeysRequestDTO->toArray()
        );

        return TokenexAuthKeys::fromApiResponse($response);
    }

    /**
     * @throws GuzzleException
     * @throws \JsonException
     * @throws CleoCrmAccountNotFoundException
     */
    public function updateAutoPayStatus(
        AutoPayStatusRequestDTO $autoPayStatusRequestDTO
    ): AutoPayStatus {
        $account = $this->cleoCrmRepository->getAccount($autoPayStatusRequestDTO->customerId);

        if (null === $account) {
            throw new CleoCrmAccountNotFoundException();
        }

        /**
         * @var object{
         *     _metadata: object{success: bool},
         *     result: object{message: string},
         * } $response
         */
        $response = $this->sendPatchRequest(
            sprintf(self::AUTOPAY_STATUS_ENDPOINT, $account->id),
            [
                'autopay_method_id' => $autoPayStatusRequestDTO->autopayMethodId
            ]
        );

        return AutoPayStatus::fromApiResponse($response);
    }

    /**
     * @param PaymentsListRequestDTO $paymentsListRequestDTO
     *
     * @return Payment[]
     *
     * @throws \JsonException
     * @throws GuzzleException
     * @throws CleoCrmAccountNotFoundException
     */
    public function getPaymentsList(
        PaymentsListRequestDTO $paymentsListRequestDTO
    ): array {
        /**
         * @var object{
         *     _metadata: object{success: bool, current_page: int, per_page: int, total_pages: int, total_results: int, links: object{self: string, first: string, previous: string|null, next: string|null, last: string}},
         *     result: object{payment_id: string, status: string, amount: float, created_at: string}[],
         * } $response
         */
        $response = $this->sendGetRequest(
            self::PAYMENTS_LIST_ENDPOINT,
            $paymentsListRequestDTO->toArray()
        );

        return array_map(static fn (object $result) => Payment::fromApiResponse($result), $response->result);
    }

    /**
     * @throws GuzzleException
     * @throws \JsonException
     * @throws CleoCrmAccountNotFoundException
     */
    public function authorizeAndCapture(AuthAndCaptureRequestDTO $authAndCaptureRequestDTO): AuthAndCapture
    {
        /**
         * @var object{
         *       _metadata: object{success: bool},
         *       result: object{message: string, status: string, payment_id: string, transaction_id: string},
         * } $response
         */
        $response = $this->sendPostRequest(
            self::AUTH_AND_CAPTURE_ENDPOINT,
            $authAndCaptureRequestDTO->toArray()
        );

        PaymentMade::dispatch(
            $authAndCaptureRequestDTO->customerId,
            (int) round($authAndCaptureRequestDTO->amount / 100)
        );

        return AuthAndCapture::fromApiResponse($response);
    }

    /**
     * @param PaymentMethodsListRequestDTO $paymentMethodsListRequestDTO
     *
     * @return BasePaymentMethod[]
     *
     * @throws \JsonException
     * @throws GuzzleException
     * @throws CleoCrmAccountNotFoundException
     */
    public function getPaymentMethodsList(PaymentMethodsListRequestDTO $paymentMethodsListRequestDTO): array
    {
        /**
         * @var object{
         *     _metadata: object{success: bool, current_page: int, per_page: int, total_pages: int, total_results: int, links: object{self: string, first: string, previous: string|null, next: string|null, last: string}},
         *     result: object{payment_method_id: string, account_id: string, type: string, date_added: string, is_primary: bool, is_autopay: bool, description: string|null, cc_type: string|null, cc_last_four: string|null, cc_expiration_month: int|null, cc_expiration_year: int|null, ach_account_last_four: string|null, ach_routing_number: string|null, ach_account_type: string|null, ach_bank_name: string|null}[],
         * } $response
         */
        $response = $this->sendGetRequest(
            self::PAYMENT_METHODS_ENDPOINT,
            $paymentMethodsListRequestDTO->toArray()
        );

        $paymentMethodValidator = $this->paymentMethodValidator;

        $paymentMethods = array_map(
            static fn (object $result) => PaymentMethod::fromApiResponse($result),
            $response->result
        );

        return array_map(
            static function (PaymentMethod $paymentMethod) use ($paymentMethodValidator) {
                $paymentMethod->setIsExpired($paymentMethodValidator->isPaymentMethodExpired($paymentMethod));

                return $paymentMethod->basePaymentMethod;
            },
            $paymentMethods
        );
    }

    /**
     * @throws \JsonException
     * @throws GuzzleException
     * @throws CleoCrmAccountNotFoundException
     */
    public function createPaymentProfile(
        CreatePaymentProfileRequestDTO $createPaymentProfileRequestDTO
    ): PaymentProfile {
        /**
         * @var object{
         *     _metadata: object{success: bool, links: object{self: string}},
         *     result: object{message: string, payment_method_id: string},
         * } $response
         */
        $response = $this->sendPostRequest(
            self::PAYMENT_METHODS_ENDPOINT,
            $createPaymentProfileRequestDTO->toArray()
        );

        return PaymentProfile::fromApiResponse($response);
    }

    /**
     * @throws GuzzleException
     * @throws \JsonException
     * @throws CleoCrmAccountNotFoundException
     */
    public function setPaymentMethodAsPrimary(string $paymentMethodId): bool
    {
        /**
         * @var object{
         *     _metadata: object{success: bool, links: object{self: string}},
         *     result: object{message: string},
         * } $response
         */
        $response = $this->sendPatchRequest(
            sprintf(self::PAYMENT_METHOD_UPDATE_ENDPOINT, $paymentMethodId),
            [
                'is_primary' => true,
            ],
        );

        return $response->_metadata->success;
    }

    /**
     * @throws GuzzleException
     * @throws \JsonException
     */
    public function deletePaymentMethod(string $paymentMethodId): bool
    {
        /**
         * @var object{
         *     _metadata: object{success: bool, links: object{self: string}},
         *     result: object{message: string},
         * } $response
         */
        $response = $this->sendDeleteRequest(
            sprintf(self::PAYMENT_METHOD_UPDATE_ENDPOINT, $paymentMethodId),
        );

        return $response->_metadata->success;
    }

    /**
     * @throws GuzzleException
     * @throws \JsonException
     * @throws CreditCardTokenNotFoundException
     * @throws CleoCrmAccountNotFoundException
     */
    public function isValidCreditCardToken(ValidateCreditCardTokenRequestDTO $validateCreditCardTokenRequestDTO): bool
    {
        /**
         * @var object{
         *     _metadata: object{success: bool, links: object{self: string}},
         *     result: object{message: string, is_valid: bool},
         * } $response
         */
        $response = $this->sendPostRequest(
            self::CREDIT_CARD_TOKEN_VALIDATION_ENDPOINT,
            $validateCreditCardTokenRequestDTO->toArray()
        );

        if (!$response->result->is_valid) {
            throw new CreditCardTokenNotFoundException($response->result->message);
        }

        return $response->result->is_valid;
    }
}
