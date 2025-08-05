<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories\CleoCrm;

use App\Cache\AbstractCachedWrapper;
use App\DTO\CleoCrm\AccountDTO;
use App\Repositories\CleoCrm\CachedCleoCrmRepository;
use App\Repositories\CleoCrm\CleoCrmRepository;
use App\Repositories\PlanBuilder\PlanBuilderRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Traits\MockTaggedCache;
use Tests\Traits\RandomIntTestData;

class CachedCleoCrmRepositoryTest extends TestCase
{
    use RandomIntTestData;
    use MockTaggedCache;

    private const METHOD_NAME = 'getAccount';
    private const HASH_ALGORITHM = 'md5';
    private const TTL_PATH = 'cache.custom_ttl.repositories.cleo_crm';
    private const CUSTOMER_ID = 2871411;

    protected CachedCleoCrmRepository $subject;
    protected MockInterface|PlanBuilderRepository $repositoryMock;

    public function setUp(): void
    {
        parent::setUp();

        $this->repositoryMock = Mockery::mock(CleoCrmRepository::class);
        $this->subject = new CachedCleoCrmRepository($this->repositoryMock);
    }

    public function test_it_extends_abstract_cached_wrapper_class(): void
    {
        self::assertInstanceOf(AbstractCachedWrapper::class, $this->subject);
    }

    public function test_it_finds_in_cache(): void
    {
        $response = $this->getValidResponse();
        $method = self::METHOD_NAME;
        Config::set($this->getTtlPath(), 10);

        Cache::tags($this->getTags())->forget($this->subject::buildKey($method, []));

        $this->repositoryMock
            ->shouldReceive(self::METHOD_NAME)
            ->andReturn($response)
            ->once();

        for ($i = 1; $i <= random_int(1, 5); $i++) {
            $cachedResult = $this->subject->$method(self::CUSTOMER_ID);
            self::assertEquals($response, $cachedResult);
        }
    }

    public function test_it_stores_call_result_in_cache(): void
    {
        $method = self::METHOD_NAME;
        $response = $this->getValidResponse();

        $taggedCacheMock = $this->mockTaggedCache($this->getTags());
        $taggedCacheMock->shouldReceive('remember')
            ->andReturn($response)
            ->once();

        $result = $this->subject->$method(self::CUSTOMER_ID);

        self::assertEquals($response, $result);
    }

    protected function getTags(): array
    {
        return [
            '{' . hash(self::HASH_ALGORITHM, $this->subject::class . '::' . self::METHOD_NAME) . '}'
        ];
    }

    protected function getValidResponse(): AccountDTO
    {
        $response = '[
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
            ]';

        return AccountDTO::fromApiResponse(current(json_decode($response)));
    }

    protected function getTtlPath(): string
    {
        return self::TTL_PATH;
    }
}
