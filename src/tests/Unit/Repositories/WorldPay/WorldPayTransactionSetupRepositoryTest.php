<?php

namespace Tests\Unit\Repositories\WorldPay;

use App\DTO\CreateTransactionSetupDTO;
use App\Exceptions\TransactionSetup\TransactionSetupException;
use App\Repositories\WorldPay\WorldPayTransactionSetupRepository;
use App\Services\LogService;
use Illuminate\Support\Facades\Http;
use Tests\Traits\RandomIntTestData;

class WorldPayTransactionSetupRepositoryTest extends WorldPayRepositoryTest
{
    use RandomIntTestData;
    private const WORLDPAY_COMPANY_NAME = 'Aptive';
    private const WORLDPAY_CALLBACK_URL = 'https://example.com/callback';
    private const WORLDPAY_PAYMENT_ACCOUNT_TYPE = '0';
    private const WORLDPAY_PAYMENT_ACCOUNT_REF_NUMBER = '777777';
    private const TRANSACTION_SETUP_ID = 'B2CD9C84-546C-4158-8F7B-5D159C7EA1C2';
    protected string $transactionSetupPayload;
    protected string $transactionSetupResponse;
    protected string $transactionSetupErrorResponse;
    protected CreateTransactionSetupDTO $dto;
    protected WorldPayTransactionSetupRepository $worldPayTransactionSetupRepository;

    public function setUp(): void
    {
        parent::setUp();

        $additionalConfigParameters = [
            'worldpay.company_name' => self::WORLDPAY_COMPANY_NAME,
            'worldpay.transaction_setup.callback_url' => self::WORLDPAY_CALLBACK_URL,
            'worldpay.payment_account.payment_account_type' => self::WORLDPAY_PAYMENT_ACCOUNT_TYPE,
            'worldpay.payment_account.payment_account_reference_number' => self::WORLDPAY_PAYMENT_ACCOUNT_REF_NUMBER,
        ];

        $this->worldPayTransactionSetupRepository = new WorldPayTransactionSetupRepository(
            $this->credentialsRepositoryMock,
            $this->getConfigurationRepositoryMock($additionalConfigParameters),
            $this->logServiceMock
        );

        $this->transactionSetupPayload = sprintf(
            '<TransactionSetup xmlns="https://transaction.elementexpress.com"><Credentials><AccountID>%s</AccountID><AccountToken>%s</AccountToken><AcceptorID>%s</AcceptorID></Credentials><Application><ApplicationID>%s</ApplicationID><ApplicationName>%s</ApplicationName><ApplicationVersion>%s</ApplicationVersion></Application><TransactionSetup><TransactionSetupID/><TransactionSetupMethod>7</TransactionSetupMethod><DeviceInputCode>0</DeviceInputCode><Device>0</Device><Embedded>1</Embedded><CVVRequired>1</CVVRequired><CompanyName>Aptive</CompanyName><LogoURL/><Tagline/><AutoReturn>1</AutoReturn><WelcomeMessage/><ProcessTransactionTitle>Submit</ProcessTransactionTitle><ReturnURL>%s</ReturnURL><CustomCss>body {background-color: #f3f4f6;}.tableStandard {border: none !important;}#tdCardInformation {border: none !important;font-size: 30px;padding: 25px;background-color: #f3f4f6;padding-left: 0;}#divRequiredLegend {font-size: 13px;margin-top: 18px;}#tableCardInformation {border: none !important;}#tableManualEntry {width: 100%%;font-size: 13px;}#trManualEntryCardNumber td {padding-bottom: 8px;}.tdField {padding-left: 10px;}#cardNumber {width: 240px;border-radius: 6px;padding: 8px;border: 1px solid rgb(209, 213, 219);box-shadow: rgba(0, 0, 0, 0) 0px 0px 0px 0px, rgba(0, 0, 0, 0) 0px 0px 0px 0px, rgba(0, 0, 0, 0.05) 0px 1px 2px 0px;letter-spacing: 1px;}.selectOption {width: 80px;border-radius: 6px;padding: 8px;border: 1px solid rgb(209, 213, 219);box-shadow: rgba(0, 0, 0, 0) 0px 0px 0px 0px, rgba(0, 0, 0, 0) 0px 0px 0px 0px, rgba(0, 0, 0, 0.05) 0px 1px 2px 0px }#tdManualEntry {padding: 18px;padding-top: 30px;padding-bottom: 30px;background-color: #FFF;border: none !important;border-radius: 10px;box-shadow: rgba(0, 0, 0, 0) 0px 0px 0px 0px, rgba(0, 0, 0, 0) 0px 0px 0px 0px, rgba(0, 0, 0, 0.1) 0px 1px 3px 0px, rgba(0, 0, 0, 0.1) 0px 1px 2px -1px;}.tdTransactionButtons {padding-top: 20px;border: none;}.buttonEmbedded {background-color: #344c38 !important;padding-left: 24px !important;padding-right: 24px !important;padding-top: 12px !important;padding-bottom: 12px !important;text-transform: capitalize !important;border-radius: 5px;}.buttonCancel {background-color: none !important;border: 2px solid #344c38;font-size: 15px;color: #344c38 !important;text-decoration: none;font-weight: bold;padding-left: 24px !important;padding-right: 24px !important;padding-top: 12px !important;padding-bottom: 12px !important;text-transform: capitalize !important;border-radius: 5px;}</CustomCss><ReturnURLTitle/><OrderDetails/></TransactionSetup><PaymentAccount><PaymentAccountID/><PaymentAccountType>%s</PaymentAccountType><PaymentAccountReferenceNumber>%s</PaymentAccountReferenceNumber></PaymentAccount><Address><BillingName>John &amp; Jane Doe</BillingName><BillingAddress1>Aptive Street</BillingAddress1><BillingAddress2>Unit 105c</BillingAddress2><BillingCity>Orlando</BillingCity><BillingState>FL</BillingState><BillingZipcode>32832</BillingZipcode><BillingEmail>JohnDoe@goaptive.com</BillingEmail><BillingPhone>4423221221</BillingPhone></Address><Terminal><TerminalID>1</TerminalID><CVVPresenceCode>0</CVVPresenceCode><CardPresentCode>0</CardPresentCode><CardholderPresentCode>0</CardholderPresentCode><CardInputCode>0</CardInputCode><TerminalCapabilityCode>0</TerminalCapabilityCode><TerminalEnvironmentCode>0</TerminalEnvironmentCode><MotoECICode>0</MotoECICode></Terminal></TransactionSetup>',
            self::WORLDPAY_ACCOUNT_ID,
            self::WORLDPAY_ACCOUNT_TOKEN,
            self::WORLDPAY_ACCEPTOR_ID,
            self::WORLDPAY_APPLICATION_ID,
            self::WORLDPAY_APPLICATION_NAME,
            self::WORLDPAY_APPLICATION_VERSION,
            self::WORLDPAY_CALLBACK_URL,
            self::WORLDPAY_PAYMENT_ACCOUNT_TYPE,
            self::WORLDPAY_PAYMENT_ACCOUNT_REF_NUMBER,
        );
        $this->transactionSetupResponse = sprintf(
            "<TransactionSetupResponse xmlns='https://transaction.elementexpress.com'><Response><ExpressResponseCode>0</ExpressResponseCode><ExpressResponseMessage>Success</ExpressResponseMessage><ExpressTransactionDate>20220222</ExpressTransactionDate><ExpressTransactionTime>175644</ExpressTransactionTime><ExpressTransactionTimezone>UTC-06:00:00</ExpressTransactionTimezone><Transaction><TransactionSetupID>%s</TransactionSetupID></Transaction><PaymentAccount><TransactionSetupID>%s</TransactionSetupID></PaymentAccount><TransactionSetup><TransactionSetupID>%s</TransactionSetupID><ValidationCode>C76C8C0E66F04265</ValidationCode></TransactionSetup></Response></TransactionSetupResponse>",
            self::TRANSACTION_SETUP_ID,
            self::TRANSACTION_SETUP_ID,
            self::TRANSACTION_SETUP_ID,
        );
        $this->transactionSetupErrorResponse = "<PaymentAccountCreateResponse xmlns='https://services.elementexpress.commm'><Response><ExpressResponseCode>12</ExpressResponseCode><ExpressResponseMessage>Any Message</ExpressResponseMessage></Response></PaymentAccountCreateResponse>";
        $this->dto = new CreateTransactionSetupDTO(
            slug: '128312',
            officeId: $this->getTestOfficeId(),
            email: 'JohnDoe@goaptive.com',
            phone_number: '4423221221',
            billing_name: 'John & Jane Doe',
            billing_address_line_1: 'Aptive Street',
            billing_address_line_2: 'Unit 105c',
            billing_city: 'Orlando',
            billing_state: 'FL',
            billing_zip: '32832',
            auto_pay: null,
        );
    }

    public function test_repository_creates_transaction_setup(): void
    {
        $this->assertSame(
            self::TRANSACTION_SETUP_ID,
            $this->runTransactionSetupTest($this->transactionSetupResponse)
        );
    }

    public function test_repository_throws_exception_on_transaction_setup_error(): void
    {
        $this->expectException(TransactionSetupException::class);
        $this->expectExceptionMessage('Any Message');
        $this->expectExceptionCode('12');

        $this->runTransactionSetupTest($this->transactionSetupErrorResponse);
    }

    public function runTransactionSetupTest(string $responseXml): string
    {
        $credentials = $this->getCredentials();

        $this->credentialsRepositoryMock
            ->expects(self::once())
            ->method('get')
            ->with($this->dto->officeId)
            ->willReturn($credentials);

        $this->logServiceMock
            ->expects(self::exactly(2))
            ->method('logInfo')
            ->withConsecutive(
                [LogService::CREATE_TRANSACTION_SETUP_PAYLOAD],
                [LogService::CREATE_TRANSACTION_SETUP_RESPONSE],
            );

        Http::expects('withBody')
            ->withArgs(function (string $xml, string $contentType): bool {
                $this->assertXmlStringEqualsXmlString($this->transactionSetupPayload, $xml);

                return $contentType === 'text/xml; charset=utf-8';
            })
            ->andReturn($this->getRequestMock($responseXml));

        return $this->worldPayTransactionSetupRepository->create($this->dto);
    }
}
