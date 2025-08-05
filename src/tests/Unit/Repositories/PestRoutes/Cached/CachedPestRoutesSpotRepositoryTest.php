<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories\PestRoutes\Cached;

use App\Cache\AbstractCachedWrapper;
use App\DTO\Spot\SearchSpotsDTO;
use App\Interfaces\Repository\SpotRepository;
use App\Models\External\CustomerModel;
use App\Repositories\AbstractExternalRepository;
use App\Repositories\PestRoutes\Cached\CachedPestRoutesSpotRepository;
use App\Repositories\PestRoutes\PestRoutesSpotRepository;
use App\Repositories\RepositoryContext;
use Mockery;
use Mockery\MockInterface;
use Tests\Data\SpotData;
use Tests\TestCase;
use Tests\Traits\MockTaggedCache;
use Tests\Traits\RandomIntTestData;

class CachedPestRoutesSpotRepositoryTest extends TestCase
{
    use RandomIntTestData;
    use MockTaggedCache;

    private const TTL_DEFAULT = 300;
    private const SEARCH_METHOD_NAME = 'search';
    private const HASH_ALGORITHM = 'md5';

    protected CachedPestRoutesSpotRepository $subject;
    protected MockInterface|PestRoutesSpotRepository $pestRoutesSpotRepositoryMock;

    public function setUp(): void
    {
        parent::setUp();

        $this->context = new RepositoryContext();
        $this->pestRoutesSpotRepositoryMock = Mockery::mock(PestRoutesSpotRepository::class);

        $this->subject = Mockery::mock(CachedPestRoutesSpotRepository::class, [
            $this->pestRoutesSpotRepositoryMock,
        ])->shouldAllowMockingProtectedMethods()->makePartial();
    }

    protected function getSubject(): AbstractCachedWrapper|SpotRepository
    {
        return $this->subject;
    }

    protected function getWrappedRepositoryMock(): MockInterface|AbstractExternalRepository
    {
        return $this->pestRoutesSpotRepositoryMock;
    }

    public function test_it_extends_abstract_cached_wrapper_class()
    {
        self::assertInstanceOf(AbstractCachedWrapper::class, $this->subject);
    }

    public function test_it_stores_search_spots_result_in_cache()
    {
        /** @var CustomerModel $serviceType */
        $spotsCollection = SpotData::getTestEntityData();
        $dto = new SearchSpotsDTO(
            officeId: $this->getTestOfficeId(),
            latitude: (float) random_int(10, 50),
            longitude: (float) random_int(10, 50),
            maxDistance: random_int(3, 10),
            dateStart: '2022-02-24',
            dateEnd: '2023-03-08'
        );

        $hashTag = hash(
            self::HASH_ALGORITHM,
            $this->subject::class . '::' . self::SEARCH_METHOD_NAME
        );

        $tags = [
            '{' . $hashTag . '}',
            CachedPestRoutesSpotRepository::buildSearchTag((float) $dto->latitude, (float) $dto->longitude),
        ];

        $taggedCacheMock = $this->mockTaggedCache($tags);
        $taggedCacheMock->shouldReceive('remember')
            ->andReturn($spotsCollection)
            ->once();

        $result = $this->subject->search($dto);

        self::assertSame($spotsCollection, $result);
    }

    /**
     * @dataProvider ttlDataProvider
     */
    public function test_it_provides_proper_ttl(string $methodName, int $ttl)
    {
        $instance = new class ($this->pestRoutesSpotRepositoryMock) extends CachedPestRoutesSpotRepository {
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
        yield ['searchSpots', self::TTL_DEFAULT];
    }
}
