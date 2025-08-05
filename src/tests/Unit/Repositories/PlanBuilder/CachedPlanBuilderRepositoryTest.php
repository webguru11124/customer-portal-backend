<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories\PlanBuilder;

use App\Cache\AbstractCachedWrapper;
use App\DTO\PlanBuilder\PlanPricingLevel;
use App\Repositories\PlanBuilder\CachedPlanBuilderRepository;
use App\Repositories\PlanBuilder\PlanBuilderRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Traits\MockTaggedCache;
use Tests\Traits\RandomIntTestData;

class CachedPlanBuilderRepositoryTest extends TestCase
{
    use RandomIntTestData;
    use MockTaggedCache;

    public const CACHE_STORE = 'array';
    private const METHOD_NAME = 'getPlanPricingLevels';
    private const HASH_ALGORITHM = 'md5';
    private const TTL_PATH = 'cache.custom_ttl.repositories.plan_builder';

    protected CachedPlanBuilderRepository $subject;
    protected MockInterface|PlanBuilderRepository $repositoryMock;

    public function setUp(): void
    {
        parent::setUp();

        $this->repositoryMock = Mockery::mock(PlanBuilderRepository::class);
        $this->subject = new CachedPlanBuilderRepository($this->repositoryMock);
    }

    public function test_it_extends_abstract_cached_wrapper_class()
    {
        self::assertInstanceOf(AbstractCachedWrapper::class, $this->subject);
    }

    public function test_it_finds_in_cache(): void
    {
        $iterations = random_int(3, 5);
        $response = $this->getValidResponse();
        $method = self::METHOD_NAME;
        Config::set($this->getTtlPath(), 10);

        $tags = $this->getTags();
        Cache::tags($tags)->forget($this->subject::buildKey($method, []));

        $this->repositoryMock
            ->shouldReceive(self::METHOD_NAME)
            ->andReturn($response)
            ->once();

        for ($i = 1; $i <= $iterations; $i++) {
            $cachedResult = $this->subject->$method();
            self::assertEquals($response, $cachedResult);
        }
    }

    public function test_it_stores_call_result_in_cache()
    {
        $method = self::METHOD_NAME;
        $tags = $this->getTags();
        $response = $this->getValidResponse();

        $taggedCacheMock = $this->mockTaggedCache($tags);
        $taggedCacheMock->shouldReceive('remember')
            ->andReturn($response)
            ->once();

        $result = $this->subject->$method();

        self::assertEquals($response, $result);
    }

    protected function getTags(): array
    {
        return [
            '{' . hash(self::HASH_ALGORITHM, $this->subject::class . '::' . self::METHOD_NAME) . '}'
        ];
    }

    protected function getValidResponse(): array
    {
        $response = '[{"id": 3,"name": "High","order": 1,"created_at": "2023-01-19T15:05:44.000000Z", "updated_at": "2023-01-19T15:05:44.000000Z" }, {"id": 8,"name": "Low","order": 3,"created_at": "2023-03-16T11:35:00.000000Z","updated_at": "2023-03-16T11:35:39.000000Z"}]';
        $responseData = json_decode($response);
        return array_map(static fn (object $path) =>PlanPricingLevel::fromApiResponse($path), $responseData);
    }

    protected function getTtlPath(): string
    {
        return self::TTL_PATH;
    }
}
