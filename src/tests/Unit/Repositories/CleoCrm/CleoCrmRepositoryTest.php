<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories\CleoCrm;

use App\DTO\CleoCrm\AccountDTO;
use App\Logging\ApiLogger;
use App\Repositories\CleoCrm\CleoCrmRepository;
use GuzzleHttp\Client;
use Tests\Traits\RandomIntTestData;
use Tests\Traits\RandomStringTestData;

class CleoCrmRepositoryTest extends CleoCrmBaseRepository
{
    use RandomIntTestData;
    use RandomStringTestData;

    public function test_it_returns_account(): void
    {
        $clientMock = $this->mockHttpGetRequest(
            url: self::API_URL . '/' . CleoCrmRepository::ACCOUNTS_ENDPOINT,
            query: [
                'external_ref_id' => $this->getTestAccountNumber()
            ],
            responseContent: '[
                {
                    "id": "9283d55c-06f8-43e9-b723-498fc39ae04a",
                    "external_ref_id": 2871411,
                    "area_id": 24,
                    "dealer_id": 1,
                    "contact_id": "d02031c7-b0e0-409a-8f9e-d54aeb6729bb",
                    "billing_contact_id": "24a5e011-8877-41e4-875a-c088138d2d50",
                    "service_address_id": "4d413481-da67-48ab-bea0-0c3523a1f3ef",
                    "billing_address_id": "e39681df-f50d-48f4-99e9-7b36c598213b",
                    "is_active": true,
                    "source": null,
                    "autopay_type": "ACH",
                    "paid_in_full": false,
                    "balance": null,
                    "balance_age": 0,
                    "responsible_balance": null,
                    "responsible_balance_age": 0,
                    "preferred_billing_day_of_month": 0,
                    "payment_hold_date": null,
                    "most_recent_credit_card_last_four": null,
                    "most_recent_credit_card_exp_date": null,
                    "sms_reminders": false,
                    "phone_reminders": false,
                    "email_reminders": false,
                    "tax_rate": null,
                    "created_by": null,
                    "updated_by": null,
                    "deleted_by": null,
                    "created_at": "2023-12-24T04:44:21.693635Z",
                    "updated_at": "2024-01-22T18:20:47.743832Z"
                }
            ]',
        );

        $result = $this
            ->setupCleoCrmRepository($clientMock, $this->getLoggerMockLoggingRequestAndResponse())
            ->getAccount($this->getTestAccountNumber());

        $this->assertInstanceOf(AccountDTO::class, $result);
        $this->assertEquals('9283d55c-06f8-43e9-b723-498fc39ae04a', $result->id);
        $this->assertEquals(2871411, $result->externalRefId);
        $this->assertEquals(24, $result->areaId);
        $this->assertEquals(1, $result->dealerId);
        $this->assertEquals('d02031c7-b0e0-409a-8f9e-d54aeb6729bb', $result->contactId);
        $this->assertEquals('24a5e011-8877-41e4-875a-c088138d2d50', $result->billingContactId);
        $this->assertEquals('4d413481-da67-48ab-bea0-0c3523a1f3ef', $result->serviceAddressId);
        $this->assertEquals('e39681df-f50d-48f4-99e9-7b36c598213b', $result->billingAddressId);
        $this->assertTrue($result->isActive);
        $this->assertNull($result->source);
        $this->assertEquals('ACH', $result->autopayType);
        $this->assertFalse($result->paidInFull);
        $this->assertNull($result->balance);
        $this->assertEquals(0, $result->balanceAge);
        $this->assertNull($result->responsibleBalance);
        $this->assertEquals(0, $result->responsibleBalanceAge);
        $this->assertEquals(0, $result->preferredBillingDayOfMonth);
        $this->assertNull($result->paymentHoldDate);
        $this->assertNull($result->mostRecentCreditCardExpDate);
        $this->assertNull($result->mostRecentCreditCardLastFour);
        $this->assertFalse($result->smsReminders);
        $this->assertFalse($result->phoneReminders);
        $this->assertFalse($result->emailReminders);
        $this->assertNull($result->taxRate);
        $this->assertNull($result->createdBy);
        $this->assertNull($result->updatedBy);
        $this->assertEquals('2023-12-24T04:44:21.693635Z', $result->createdAt);
        $this->assertEquals('2024-01-22T18:20:47.743832Z', $result->updatedAt);
    }

    public function test_it_returns_empty_accounts(): void
    {
        $clientMock = $this->mockHttpGetRequest(
            url: self::API_URL . '/' . CleoCrmRepository::ACCOUNTS_ENDPOINT,
            query: [
                'external_ref_id' => $this->getTestAccountNumber()
            ],
            responseContent: '[]',
        );

        $result = $this
            ->setupCleoCrmRepository($clientMock, $this->getLoggerMockLoggingRequestAndResponse())
            ->getAccount($this->getTestAccountNumber());

        $this->assertNull($result);
    }

    public function test_it_throws_an_exception_on_requesting_accounts(): void
    {
        $clientMock = $this->mockHttpGetRequestToThrowException(
            url: self::API_URL . '/' . CleoCrmRepository::ACCOUNTS_ENDPOINT,
            query: [
                'external_ref_id' => $this->getTestAccountNumber()
            ],
        );

        $this->expectException(\Exception::class);

        $result = $this
            ->setupCleoCrmRepository($clientMock, $this->getLoggerMockLoggingRequestOnly())
            ->getAccount($this->getTestAccountNumber());
    }

    protected function setupCleoCrmRepository(Client $clientMock, ApiLogger $loggerMock): CleoCrmRepository
    {
        return new CleoCrmRepository(
            guzzleClient: $clientMock,
            config: $this->getConfigMock(),
            logger: $loggerMock,
        );
    }
}
