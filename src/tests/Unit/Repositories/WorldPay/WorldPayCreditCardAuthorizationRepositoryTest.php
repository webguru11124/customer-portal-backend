<?php

namespace Tests\Unit\Repositories\WorldPay;

use App\DTO\CreditCardAuthorizationDTO;
use App\Exceptions\PaymentProfile\CreditCardAuthorizationException;
use App\Repositories\WorldPay\WorldPayCreditCardAuthorizationRepository;
use App\Services\LogService;
use Illuminate\Support\Facades\Http;
use Tests\Data\CustomerData;

final class WorldPayCreditCardAuthorizationRepositoryTest extends WorldPayRepositoryTest
{
    private const TRANSACTION_ID = '140396055';
    private const PAYMENT_ACCOUNT_ID = '63C49095-E5A8-4F60-A639-C753D8E709AD';
    private const TRANSACTION_AMOUNT = 249.95;

    protected WorldPayCreditCardAuthorizationRepository $worldPayCreditCardAuthorizationRepository;

    protected string $creditCardAuthorizationPayload;
    protected string $creditCardAuthorizationResponse;
    protected string $creditCardAuthorizationErrorResponse;

    public function setUp(): void
    {
        parent::setUp();

        $this->worldPayCreditCardAuthorizationRepository = new WorldPayCreditCardAuthorizationRepository(
            $this->credentialsRepositoryMock,
            $this->getConfigurationRepositoryMock(),
            $this->logServiceMock
        );

        $this->creditCardAuthorizationPayload = sprintf(
            '<CreditCardAuthorization xmlns="https://transaction.elementexpress.com"><Credentials><AccountID>%s</AccountID><AccountToken>%s</AccountToken><AcceptorID>%s</AcceptorID></Credentials><Application><ApplicationID>%s</ApplicationID><ApplicationName>%s</ApplicationName><ApplicationVersion>%s</ApplicationVersion></Application><Transaction><TransactionAmount>%s</TransactionAmount></Transaction><ExtendedParameters><PaymentAccount><PaymentAccountID>%s</PaymentAccountID></PaymentAccount></ExtendedParameters><Terminal><TerminalID>1</TerminalID><CVVPresenceCode>0</CVVPresenceCode><CardPresentCode>0</CardPresentCode><CardholderPresentCode>0</CardholderPresentCode><CardInputCode>0</CardInputCode><TerminalCapabilityCode>0</TerminalCapabilityCode><TerminalEnvironmentCode>0</TerminalEnvironmentCode><MotoECICode>0</MotoECICode></Terminal></CreditCardAuthorization>',
            self::WORLDPAY_ACCOUNT_ID,
            self::WORLDPAY_ACCOUNT_TOKEN,
            self::WORLDPAY_ACCEPTOR_ID,
            self::WORLDPAY_APPLICATION_ID,
            self::WORLDPAY_APPLICATION_NAME,
            self::WORLDPAY_APPLICATION_VERSION,
            self::TRANSACTION_AMOUNT,
            self::PAYMENT_ACCOUNT_ID,
        );
        $this->creditCardAuthorizationResponse = sprintf(
            "<CreditCardAuthorizationResponse xmlns='https://transaction.elementexpress.com'><Response><ExpressResponseCode>0</ExpressResponseCode><ExpressResponseMessage>Approved</ExpressResponseMessage><HostResponseCode>000</HostResponseCode><HostResponseMessage>AP</HostResponseMessage><ExpressTransactionDate>20220331</ExpressTransactionDate><ExpressTransactionTime>113433</ExpressTransactionTime><ExpressTransactionTimezone>UTC-05:00:00</ExpressTransactionTimezone><Batch><HostBatchID>1</HostBatchID></Batch><Card><AVSResponseCode>N</AVSResponseCode><ExpirationMonth>03</ExpirationMonth><ExpirationYear>29</ExpirationYear><CardLogo>Visa</CardLogo><CardNumberMasked>xxxx-xxxx-xxxx-4242</CardNumberMasked><BIN>424242</BIN></Card><Transaction><TransactionID>%s</TransactionID><ApprovalNumber>000034</ApprovalNumber><AcquirerData>aVb001234567810425c0425d5e00</AcquirerData><ProcessorName>NULL_PROCESSOR_TEST</ProcessorName><TransactionStatus>Authorized</TransactionStatus><TransactionStatusCode>5</TransactionStatusCode><ApprovedAmount>0.00</ApprovedAmount></Transaction><PaymentAccount><PaymentAccountReferenceNumber>767344</PaymentAccountReferenceNumber></PaymentAccount><Address><BillingAddress1>9797</BillingAddress1><BillingZipcode>32832</BillingZipcode></Address><Terminal><MotoECICode>0</MotoECICode></Terminal></Response></CreditCardAuthorizationResponse>",
            self::TRANSACTION_ID
        );

        $this->creditCardAuthorizationErrorResponse = "<CreditCardAuthorizationResponse xmlns='https://transaction.elementexpress.com'><Response><ExpressResponseCode>101</ExpressResponseCode><ExpressResponseMessage>INVALID CARD INFO</ExpressResponseMessage><HostResponseCode>14</HostResponseCode><ExpressTransactionDate>20220330</ExpressTransactionDate><ExpressTransactionTime>154225</ExpressTransactionTime><ExpressTransactionTimezone>UTC-05:00:00</ExpressTransactionTimezone><Batch><HostBatchID>1</HostBatchID></Batch><Card><ExpirationMonth>03</ExpirationMonth><ExpirationYear>26</ExpirationYear><CardLogo>Visa</CardLogo><CardNumberMasked>xxxx-xxxx-xxxx-4242</CardNumberMasked><BIN>424242</BIN></Card><Transaction><TransactionID>62491205</TransactionID><AcquirerData>491205|208915491205|0330204225|1042000314|||||||||||||||||||51|0100|154225|||||||019F003|0330||||||||</AcquirerData><ProcessorName>VANTIV_PROD</ProcessorName><TransactionStatus>Error</TransactionStatus><TransactionStatusCode>13</TransactionStatusCode><HostTransactionID>08C000</HostTransactionID><RetrievalReferenceNumber>208915491205</RetrievalReferenceNumber><SystemTraceAuditNumber>491205</SystemTraceAuditNumber></Transaction><PaymentAccount><PaymentAccountReferenceNumber>767344</PaymentAccountReferenceNumber></PaymentAccount><Address><BillingAddress1>9797</BillingAddress1><BillingZipcode>32832</BillingZipcode></Address><Terminal><MotoECICode>0</MotoECICode></Terminal></Response></CreditCardAuthorizationResponse>";
    }

    public function test_authorization_sends_request_and_logs_request_response(): void
    {
        $this->assertSame(
            self::TRANSACTION_ID,
            $this->runAuthorizationTest($this->creditCardAuthorizationResponse)
        );
    }

    public function test_authorization_throws_exception_on_processing_error(): void
    {
        $this->expectException(CreditCardAuthorizationException::class);

        $this->runAuthorizationTest($this->creditCardAuthorizationErrorResponse);
    }

    private function runAuthorizationTest(string $responseXml): string
    {
        $customer = CustomerData::getTestEntityData(1)->first();
        $dto = new CreditCardAuthorizationDTO(self::PAYMENT_ACCOUNT_ID, self::TRANSACTION_AMOUNT);

        $credentials = $this->getCredentials();

        $this->credentialsRepositoryMock
            ->expects(self::once())
            ->method('get')
            ->with($customer->officeId)
            ->willReturn($credentials);

        $this->logServiceMock
            ->expects(self::exactly(2))
            ->method('logInfo')
            ->withConsecutive(
                [LogService::CREDIT_CARD_AUTHORIZATION_PAYLOAD],
                [LogService::CREDIT_CARD_AUTHORIZATION_RESPONSE],
            );

        Http::expects('withBody')
            ->withArgs(function (string $xml, string $contentType): bool {
                $this->assertXmlStringEqualsXmlString($this->creditCardAuthorizationPayload, $xml);

                return $contentType === 'text/xml; charset=utf-8';
            })
            ->andReturn($this->getRequestMock($responseXml));

        return $this->worldPayCreditCardAuthorizationRepository->authorize($dto, $customer);
    }
}
