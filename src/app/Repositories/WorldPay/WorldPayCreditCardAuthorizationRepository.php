<?php

namespace App\Repositories\WorldPay;

use App\DTO\CreditCardAuthorizationDTO;
use App\Exceptions\PaymentProfile\CreditCardAuthorizationException;
use App\Interfaces\Repository\CreditCardAuthorizationRepository;
use App\Models\External\CustomerModel;
use App\Services\LogService;
use Aptive\Worldpay\CredentialsRepository\CredentialsRepository;
use Illuminate\Contracts\Config\Repository;

/**
 * Handle WordPay's credit card related API calls.
 */
class WorldPayCreditCardAuthorizationRepository extends WorldPayBaseRepository implements CreditCardAuthorizationRepository
{
    public function __construct(
        CredentialsRepository $credentialsRepository,
        Repository $config,
        private readonly LogService $logService
    ) {
        parent::__construct($credentialsRepository, $config);
    }

    /**
     * Process an authorization against the given Payment Account.
     *
     * @param CreditCardAuthorizationDTO $dto
     * @param CustomerModel $customer
     *
     * @return string
     */
    public function authorize(CreditCardAuthorizationDTO $dto, CustomerModel $customer): string
    {
        $data = $this->createXMLData($dto);

        $payload = $this->preparePayload(
            $customer->officeId,
            static::TYPE_TRANSACTION,
            'CreditCardAuthorization',
            $data
        );

        $timeStart = $this->logService->logInfo(LogService::CREDIT_CARD_AUTHORIZATION_PAYLOAD, [
            'xml' => $payload,
        ]);

        $response = $this->post(static::TYPE_TRANSACTION, '', $payload);

        $this->logService->logInfo(LogService::CREDIT_CARD_AUTHORIZATION_RESPONSE, [
            'xml' => $response,
        ], $timeStart);

        return $this->handleResponse($response);
    }

    /**
     * Handles API Response.
     *
     * @param string $response
     *
     * @return string
     *
     * @throws CreditCardAuthorizationException
     * @throws \JsonException if encoding or decoding JSON fails
     */
    private function handleResponse(string $response): string
    {
        $xml = simplexml_load_string($response, 'SimpleXMLElement', LIBXML_NOCDATA);
        $json = json_encode($xml, JSON_THROW_ON_ERROR);
        $array = json_decode($json, true, flags: JSON_THROW_ON_ERROR);

        if ($array['Response']['ExpressResponseCode'] !== '0') {
            throw new CreditCardAuthorizationException(
                $array['Response']['ExpressResponseMessage'],
                $array['Response']['ExpressResponseCode']
            );
        }

        return $array['Response']['Transaction']['TransactionID'];
    }

    /**
     * Create XML data (array).
     *
     * @param CreditCardAuthorizationDTO $dto
     *
     * @return array<string, array<string, mixed>>
     */
    private function createXMLData(CreditCardAuthorizationDTO $dto): array
    {
        return [
            'Transaction' => [
                'TransactionAmount' => $this->formatAmount($dto->transactionAmount),
            ],
            'ExtendedParameters' => [
                'PaymentAccount' => [
                    'PaymentAccountID' => $dto->paymentAccountID,
                ],
            ],
            'Terminal' => [
                'TerminalID' => 01,
                'CVVPresenceCode' => 0,
                'CardPresentCode' => 0,
                'CardholderPresentCode' => 0,
                'CardInputCode' => 0,
                'TerminalCapabilityCode' => 0,
                'TerminalEnvironmentCode' => 0,
                'MotoECICode' => 0,
            ],
        ];
    }

    private function formatAmount(float $amount): string
    {
        return number_format($amount, 2, thousands_separator: '');
    }
}
