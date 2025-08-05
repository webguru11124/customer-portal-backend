<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories\PestRoutes\Cached;

use App\Cache\AbstractCachedWrapper;
use App\Helpers\ConfigHelper;
use App\Repositories\AbstractExternalRepository;
use App\Repositories\PestRoutes\Cached\CachedPestRoutesOfficeRepository;
use App\Repositories\PestRoutes\PestRoutesOfficeRepository;
use App\Repositories\RepositoryContext;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Traits\MockTaggedCache;
use Tests\Traits\RandomIntTestData;

class CachedPestRoutesOfficeRepositoryTest extends TestCase
{
    use RandomIntTestData;
    use ExtendsAbstractCachedExternalRepositoryWrapper;
    use MockTaggedCache;

    public const TTL_PATH = 'cache.custom_ttl.repositories.office';

    protected CachedPestRoutesOfficeRepository $subject;
    protected MockInterface|PestRoutesOfficeRepository $repositoryMock;

    public function setUp(): void
    {
        parent::setUp();

        $this->context = new RepositoryContext();
        $this->repositoryMock = Mockery::mock(PestRoutesOfficeRepository::class);
        $this->subject = new CachedPestRoutesOfficeRepository($this->repositoryMock);
    }

    protected function getSubject(): CachedPestRoutesOfficeRepository
    {
        return $this->subject;
    }

    protected function getWrappedRepositoryMock(): MockInterface|AbstractExternalRepository
    {
        return $this->repositoryMock;
    }

    protected function getContext(): RepositoryContext
    {
        return $this->context;
    }

    public function test_it_extends_abstract_cached_wrapper_class()
    {
        self::assertInstanceOf(AbstractCachedWrapper::class, $this->subject);
    }

    public function test_it_stores_office_ids_in_cache()
    {
        $pestRoutesOfficeIds = [
            $this->getTestOfficeId(),
            $this->getTestOfficeId() + 1,
        ];

        $tags = [$this->subject::getHashTag('getAllOfficeIds')];
        $taggedCacheMock = $this->mockTaggedCache($tags);
        $taggedCacheMock->shouldReceive('remember')
            ->andReturn($pestRoutesOfficeIds)
            ->once();

        $context = new RepositoryContext();
        $context->office(ConfigHelper::getGlobalOfficeId());

        $result = $this->subject->getAllOfficeIds();

        self::assertSame($pestRoutesOfficeIds, $result);
    }

    protected function getTtlPath(): string
    {
        return self::TTL_PATH;
    }
}
