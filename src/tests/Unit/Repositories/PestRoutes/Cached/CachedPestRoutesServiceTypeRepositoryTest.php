<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories\PestRoutes\Cached;

use App\Cache\AbstractCachedExternalRepositoryWrapper;
use App\Cache\AbstractCachedWrapper;
use App\Repositories\AbstractExternalRepository;
use App\Repositories\PestRoutes\Cached\CachedPestRoutesServiceTypeRepository;
use App\Repositories\PestRoutes\PestRoutesServiceTypeRepository;
use App\Repositories\RepositoryContext;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Traits\RandomIntTestData;

class CachedPestRoutesServiceTypeRepositoryTest extends TestCase
{
    use RandomIntTestData;
    use ExtendsAbstractCachedExternalRepositoryWrapper;

    public const CACHE_STORE = 'array';
    public const TTL_PATH = 'cache.custom_ttl.repositories.service_type';

    protected CachedPestRoutesServiceTypeRepository $subject;
    protected MockInterface|PestRoutesServiceTypeRepository $pestRoutesServiceTypeRepositoryMock;
    protected RepositoryContext $context;

    public function setUp(): void
    {
        parent::setUp();

        $this->context = new RepositoryContext();
        $this->pestRoutesServiceTypeRepositoryMock = Mockery::mock(PestRoutesServiceTypeRepository::class);

        $this->subject = Mockery::mock(CachedPestRoutesServiceTypeRepository::class, [
            $this->pestRoutesServiceTypeRepositoryMock,
        ])->shouldAllowMockingProtectedMethods()->makePartial();
    }

    protected function getSubject(): AbstractCachedExternalRepositoryWrapper
    {
        return $this->subject;
    }

    protected function getWrappedRepositoryMock(): MockInterface|AbstractExternalRepository
    {
        return $this->pestRoutesServiceTypeRepositoryMock;
    }

    protected function getContext(): RepositoryContext
    {
        return $this->context;
    }

    public function tearDown(): void
    {
        Cache::store(self::CACHE_STORE)->clear();

        parent::tearDown();
    }

    public function test_it_extends_abstract_cached_wrapper_class()
    {
        self::assertInstanceOf(AbstractCachedWrapper::class, $this->subject);
    }

    public function test_it_provides_30_days_ttl()
    {
        $secondsIn30Days = 30 * 24 * 60 * 60;

        $instance = new class ($this->pestRoutesServiceTypeRepositoryMock) extends CachedPestRoutesServiceTypeRepository {
            public function getCacheTtlTest(): int
            {
                return parent::getCacheTtl('getServiceType');
            }
        };

        self::assertSame($secondsIn30Days, $instance->getCacheTtlTest());
    }

    protected function getTtlPath(): string
    {
        return self::TTL_PATH;
    }
}
