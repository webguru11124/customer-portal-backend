<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories\PestRoutes\Cached;

use App\Cache\AbstractCachedWrapper;
use App\Repositories\AbstractExternalRepository;
use App\Repositories\PestRoutes\Cached\CachedPestRoutesRouteRepository;
use App\Repositories\PestRoutes\PestRoutesRouteRepository;
use App\Repositories\RepositoryContext;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Traits\RandomIntTestData;

class CachedPestRoutesRouteRepositoryTest extends TestCase
{
    use RandomIntTestData;
    use ExtendsAbstractCachedExternalRepositoryWrapper;

    private const TTL_DEFAULT = 300;

    public const CACHE_STORE = 'array';
    public const TTL_PATH = 'cache.custom_ttl.repositories.route';

    protected CachedPestRoutesRouteRepository $subject;
    protected MockInterface|PestRoutesRouteRepository $pestRoutesRouteRepositoryMock;

    public function setUp(): void
    {
        parent::setUp();

        $this->context = new RepositoryContext();
        $this->pestRoutesRouteRepositoryMock = Mockery::mock(PestRoutesRouteRepository::class);

        $this->subject = Mockery::mock(CachedPestRoutesRouteRepository::class, [
            $this->pestRoutesRouteRepositoryMock,
        ])->shouldAllowMockingProtectedMethods()->makePartial();
    }

    protected function getSubject(): CachedPestRoutesRouteRepository
    {
        return $this->subject;
    }

    protected function getWrappedRepositoryMock(): MockInterface|AbstractExternalRepository
    {
        return $this->pestRoutesRouteRepositoryMock;
    }

    protected function getContext(): RepositoryContext
    {
        return $this->context;
    }

    public function test_it_extends_abstract_cached_wrapper_class()
    {
        self::assertInstanceOf(AbstractCachedWrapper::class, $this->subject);
    }

    /**
     * @dataProvider ttlDataProvider
     */
    public function test_it_provides_proper_ttl(string $methodName, int $ttl)
    {
        $instance = new class ($this->pestRoutesRouteRepositoryMock) extends CachedPestRoutesRouteRepository {
            public function getCacheTtlTest(string $methodName): int
            {
                return parent::getCacheTtl($methodName);
            }
        };

        self::assertSame($ttl, $instance->getCacheTtlTest($methodName));
    }

    /**
     * @return iterable<int, array<int, string|int>>
     */
    public function ttlDataProvider(): iterable
    {
        yield ['find', self::TTL_DEFAULT];
        yield ['search', self::TTL_DEFAULT];
    }

    protected function getTtlPath(): string
    {
        return self::TTL_PATH;
    }
}
