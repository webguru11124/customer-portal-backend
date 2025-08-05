<?php

namespace App\Repositories\WorldPay;

use App\DTO\CreateTransactionSetupDTO;
use App\Exceptions\TransactionSetup\TransactionSetupException;
use App\Interfaces\Repository\TransactionSetupRepository;
use App\Services\LogService;
use Aptive\Worldpay\CredentialsRepository\CredentialsRepository;
use Illuminate\Contracts\Config\Repository;

/**
 * Handle WordPay's transaction setup related API calls.
 */
class WorldPayTransactionSetupRepository extends WorldPayBaseRepository implements TransactionSetupRepository
{
    public function __construct(
        CredentialsRepository $credentialsRepository,
        Repository $config,
        private readonly LogService $logService
    ) {
        parent::__construct($credentialsRepository, $config);
    }

    /**
     * Create a new Transaction Setup.
     *
     * @param CreateTransactionSetupDTO $dto
     *
     * @return string
     */
    public function create(CreateTransactionSetupDTO $dto): string
    {
        $data = $this->createXMLData($dto);

        $payload = $this->preparePayload(
            $dto->officeId,
            static::TYPE_TRANSACTION,
            'TransactionSetup',
            $data
        );

        $timeStart = $this->logService->logInfo(LogService::CREATE_TRANSACTION_SETUP_PAYLOAD, [
            'xml' => $payload,
        ]);

        $response = $this->post(static::TYPE_TRANSACTION, '', $payload);

        $this->logService->logInfo(LogService::CREATE_TRANSACTION_SETUP_RESPONSE, [
            'xml' => $response,
        ], $timeStart);

        return $this->handleResponse($response);
    }

    /**
     * Handles API response.
     *
     * @param string $response
     *
     * @return string
     *
     * @throws TransactionSetupException
     */
    private function handleResponse(string $response): string
    {
        $xml = simplexml_load_string($response, 'SimpleXMLElement', LIBXML_NOCDATA);
        $json = json_encode($xml, JSON_THROW_ON_ERROR);
        $array = json_decode($json, true, flags: JSON_THROW_ON_ERROR);

        if ($array['Response']['ExpressResponseCode'] !== '0') {
            throw new TransactionSetupException(
                $array['Response']['ExpressResponseMessage'],
                $array['Response']['ExpressResponseCode']
            );
        }

        return $array['Response']['Transaction']['TransactionSetupID'];
    }

    /**
     * Creates XML data (array).
     *
     * @param CreateTransactionSetupDTO $dto
     *
     * @return array<string, array<string, mixed>>
     */
    private function createXMLData(CreateTransactionSetupDTO $dto): array
    {
        return [
            'TransactionSetup' => [
                'TransactionSetupID' => '',
                'TransactionSetupMethod' => 7,
                'DeviceInputCode' => 0,
                'Device' => 0,
                'Embedded' => 1,
                'CVVRequired' => 1,
                'CompanyName' => $this->config->get('worldpay.company_name'),
                'LogoURL' => '',
                'Tagline' => '',
                'AutoReturn' => 1,
                'WelcomeMessage' => '',
                'ProcessTransactionTitle' => 'Submit',
                'ReturnURL' => $this->config->get('worldpay.transaction_setup.callback_url'),
                'CustomCss' => 'body {background-color: #f3f4f6;}.tableStandard {border: none !important;}#tdCardInformation {border: none !important;font-size: 30px;padding: 25px;background-color: #f3f4f6;padding-left: 0;}#divRequiredLegend {font-size: 13px;margin-top: 18px;}#tableCardInformation {border: none !important;}#tableManualEntry {width: 100%;font-size: 13px;}#trManualEntryCardNumber td {padding-bottom: 8px;}.tdField {padding-left: 10px;}#cardNumber {width: 240px;border-radius: 6px;padding: 8px;border: 1px solid rgb(209, 213, 219);box-shadow: rgba(0, 0, 0, 0) 0px 0px 0px 0px, rgba(0, 0, 0, 0) 0px 0px 0px 0px, rgba(0, 0, 0, 0.05) 0px 1px 2px 0px;letter-spacing: 1px;}.selectOption {width: 80px;border-radius: 6px;padding: 8px;border: 1px solid rgb(209, 213, 219);box-shadow: rgba(0, 0, 0, 0) 0px 0px 0px 0px, rgba(0, 0, 0, 0) 0px 0px 0px 0px, rgba(0, 0, 0, 0.05) 0px 1px 2px 0px }#tdManualEntry {padding: 18px;padding-top: 30px;padding-bottom: 30px;background-color: #FFF;border: none !important;border-radius: 10px;box-shadow: rgba(0, 0, 0, 0) 0px 0px 0px 0px, rgba(0, 0, 0, 0) 0px 0px 0px 0px, rgba(0, 0, 0, 0.1) 0px 1px 3px 0px, rgba(0, 0, 0, 0.1) 0px 1px 2px -1px;}.tdTransactionButtons {padding-top: 20px;border: none;}.buttonEmbedded {background-color: #344c38 !important;padding-left: 24px !important;padding-right: 24px !important;padding-top: 12px !important;padding-bottom: 12px !important;text-transform: capitalize !important;border-radius: 5px;}.buttonCancel {background-color: none !important;border: 2px solid #344c38;font-size: 15px;color: #344c38 !important;text-decoration: none;font-weight: bold;padding-left: 24px !important;padding-right: 24px !important;padding-top: 12px !important;padding-bottom: 12px !important;text-transform: capitalize !important;border-radius: 5px;}',
                'ReturnURLTitle' => '',
                'OrderDetails' => '',
            ],
            'PaymentAccount' => [
                'PaymentAccountID' => '',
                'PaymentAccountType' => $this->config->get('worldpay.payment_account.payment_account_type'),
                'PaymentAccountReferenceNumber' => $this->config->get('worldpay.payment_account.payment_account_reference_number'),
            ],
            'Address' => [
                'BillingName' => $dto->billing_name,
                'BillingAddress1' => $dto->billing_address_line_1,
                'BillingAddress2' => $dto->billing_address_line_2,
                'BillingCity' => $dto->billing_city,
                'BillingState' => $dto->billing_state,
                'BillingZipcode' => $dto->billing_zip,
                'BillingEmail' => $dto->email,
                'BillingPhone' => $dto->phone_number,
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
}
