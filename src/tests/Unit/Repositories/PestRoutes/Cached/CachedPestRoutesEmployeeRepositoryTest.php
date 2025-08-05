<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories\PestRoutes\Cached;

use App\Cache\AbstractCachedWrapper;
use App\Models\External\EmployeeModel;
use App\Repositories\AbstractExternalRepository;
use App\Repositories\PestRoutes\Cached\CachedPestRoutesEmployeeRepository;
use App\Repositories\PestRoutes\PestRoutesEmployeeRepository;
use App\Repositories\RepositoryContext;
use Mockery;
use Mockery\MockInterface;
use Tests\Data\EmployeeData;
use Tests\TestCase;
use Tests\Traits\MockTaggedCache;
use Tests\Traits\RandomIntTestData;

class CachedPestRoutesEmployeeRepositoryTest extends TestCase
{
    use RandomIntTestData;
    use ExtendsAbstractCachedExternalRepositoryWrapper;
    use MockTaggedCache;

    private const TTL_DEFAULT = 2592000;

    public const CACHE_STORE = 'array';
    public const TTL_PATH = 'cache.custom_ttl.repositories.employee';

    protected CachedPestRoutesEmployeeRepository $subject;
    protected MockInterface|PestRoutesEmployeeRepository $pestRoutesEmployeeRepositoryMock;

    public function setUp(): void
    {
        parent::setUp();

        $this->context = new RepositoryContext();
        $this->pestRoutesEmployeeRepositoryMock = Mockery::mock(PestRoutesEmployeeRepository::class);

        $this->subject = Mockery::mock(CachedPestRoutesEmployeeRepository::class, [
            $this->pestRoutesEmployeeRepositoryMock,
        ])->shouldAllowMockingProtectedMethods()->makePartial();
    }

    protected function getSubject(): CachedPestRoutesEmployeeRepository
    {
        return $this->subject;
    }

    protected function getWrappedRepositoryMock(): MockInterface|AbstractExternalRepository
    {
        return $this->pestRoutesEmployeeRepositoryMock;
    }

    protected function getContext(): RepositoryContext
    {
        return $this->context;
    }

    public function test_it_extends_abstract_cached_wrapper_class()
    {
        self::assertInstanceOf(AbstractCachedWrapper::class, $this->subject);
    }

    public function test_it_stores_find_cxp_scheduler_result_in_cache()
    {
        /** @var EmployeeModel $employee */
        $employee = EmployeeData::getTestEntityData()->first();

        $tags = [$this->subject::getHashTag('findCxpScheduler')];
        $taggedCacheMock = $this->mockTaggedCache($tags);
        $taggedCacheMock->shouldReceive('remember')
            ->andReturn($employee)
            ->once();

        $context = new RepositoryContext();
        $context->office($this->getTestOfficeId());
        $this->pestRoutesEmployeeRepositoryMock
            ->shouldReceive('getContext')
            ->andReturn($context)
            ->once();

        $result = $this->subject->findCxpScheduler();

        self::assertSame($employee, $result);
    }

    /**
     * @dataProvider ttlDataProvider
     */
    public function test_it_provides_proper_ttl(string $methodName, int $ttl)
    {
        $instance = new class ($this->pestRoutesEmployeeRepositoryMock) extends CachedPestRoutesEmployeeRepository {
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
        yield ['findCxpScheduler', self::TTL_DEFAULT];
    }

    protected function getTtlPath(): string
    {
        return self::TTL_PATH;
    }
}
