<?php

namespace Tests\Unit\Repositories\PestRoutes\Cached;

use App\Cache\AbstractCachedExternalRepositoryWrapper;
use App\Models\External\AbstractExternalModel;
use App\Repositories\AbstractExternalRepository;
use App\Repositories\RepositoryContext;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Mockery;
use Mockery\MockInterface;
use stdClass;
use Tests\Traits\RandomIntTestData;

trait ExtendsAbstractCachedExternalRepositoryWrapper
{
    use RandomIntTestData;

    public function test_it_sets_context(): void
    {
        $this->getWrappedRepositoryMock()
            ->shouldReceive('setContext')
            ->withArgs([$this->getContext()])
            ->andReturn($this->getWrappedRepositoryMock())
            ->once();

        $result = $this->getSubject()->setContext($this->getContext());

        self::assertSame($this->getSubject(), $result);
    }

    public function test_it_gets_context(): void
    {
        $this->getWrappedRepositoryMock()
            ->shouldReceive('getContext')
            ->withNoArgs()
            ->andReturn($this->getContext())
            ->once();

        $result = $this->getSubject()->getContext();

        self::assertSame($this->getContext(), $result);
    }

    public function test_it_sets_office(): void
    {
        $officeId = $this->getTestOfficeId();

        $this->getWrappedRepositoryMock()
            ->shouldReceive('office')
            ->withArgs([$officeId])
            ->andReturn($this->getWrappedRepositoryMock());

        $result = $this->getSubject()->office($officeId);

        self::assertSame($this->getSubject(), $result);
    }

    public function test_it_sets_pagination(): void
    {
        $page = random_int(1, 10);
        $pageSize = random_int(20, 50);

        $this->getWrappedRepositoryMock()
            ->shouldReceive('paginate')
            ->withArgs([$page, $pageSize])
            ->andReturn($this->getWrappedRepositoryMock());

        $result = $this->getSubject()->paginate($page, $pageSize);

        self::assertSame($this->getSubject(), $result);
    }

    public function test_it_sets_relations(): void
    {
        $relations = ['testRelation'];

        $this->getWrappedRepositoryMock()
            ->shouldReceive('withRelated')
            ->withArgs([$relations])
            ->andReturn($this->getWrappedRepositoryMock());

        $result = $this->getSubject()->withRelated($relations);

        self::assertSame($this->getSubject(), $result);
    }

    public function test_it_finds_in_cache(): void
    {
        $id = $this->getTestServiceTypeId();
        $externalModel = Mockery::mock(AbstractExternalModel::class);

        $this->getWrappedRepositoryMock()
            ->shouldReceive('find')
            ->withArgs([$id])
            ->andReturn($externalModel)
            ->once();

        $this->mockHoldRelations($externalModel);

        $iterations = random_int(3, 5);
        Config::set($this->getTtlPath(), 1000);

        for ($i = 1; $i <= $iterations; $i++) {
            $result = $this->getSubject()->find($id);
            self::assertSame($externalModel, $result);
        }
    }

    public function test_it_finds_many_in_cache(): void
    {
        $id1 = $this->getTestServiceTypeId();
        $id2 = $id1 + 1;

        $collection = new Collection([
            Mockery::mock(AbstractExternalModel::class),
            Mockery::mock(AbstractExternalModel::class),
        ]);

        $this->getWrappedRepositoryMock()
            ->shouldReceive('findMany')
            ->withArgs([$id1, $id2])
            ->andReturn($collection)
            ->once();

        $this->mockHoldRelations($collection);

        $iterations = random_int(3, 5);
        Config::set($this->getTtlPath(), 1000);

        for ($i = 1; $i <= $iterations; $i++) {
            $result = $this->getSubject()->findMany($id1, $id2);
            self::assertSame($collection, $result);
        }
    }

    public function test_it_searches_in_cache(): void
    {
        $dto = new stdClass();
        $dto->param = 'fakeParam';

        $collection = new Collection([
            Mockery::mock(AbstractExternalModel::class),
        ]);

        $this->getWrappedRepositoryMock()
            ->shouldReceive('search')
            ->withArgs([$dto])
            ->andReturn($collection)
            ->once();

        $this->mockHoldRelations($collection);

        $iterations = random_int(3, 5);
        Config::set($this->getTtlPath(), 1000);

        for ($i = 1; $i <= $iterations; $i++) {
            $result = $this->getSubject()->search($dto);
            self::assertSame($collection, $result);
        }
    }

    public function test_it_searches_by_attribute_in_cache(): void
    {
        $attributeName = 'fakeAttribute';
        $attributeValue = random_int(1, 100);

        $collection = new Collection([
            Mockery::mock(AbstractExternalModel::class),
        ]);

        $this->getWrappedRepositoryMock()
            ->shouldReceive('searchBy')
            ->withArgs([$attributeName, [$attributeValue]])
            ->andReturn($collection)
            ->once();

        $this->mockHoldRelations($collection);

        $iterations = random_int(3, 5);

        for ($i = 1; $i <= $iterations; $i++) {
            $result = $this->getSubject()->searchBy($attributeName, [$attributeValue]);
            self::assertSame($collection, $result);
        }
    }

    private function mockHoldRelations(mixed $result): void
    {
        $this->getWrappedRepositoryMock()
            ->shouldReceive('getContext')
            ->andReturn(new RepositoryContext());

        $this->getWrappedRepositoryMock()
            ->shouldReceive('withRelated')
            ->andReturnSelf();

        $this->getWrappedRepositoryMock()
            ->shouldReceive('loadAllRelations')
            ->andReturn($result);
    }

    public function test_it_returns_lazy_load_flag_same_as_wrapped(): void
    {
        $wrapped = $this->getWrappedRepositoryMock();
        $isLazyLoadDenied = (bool) random_int(0, 1);

        $wrapped->shouldReceive('isLazyLoadDenied')->andReturn($isLazyLoadDenied);
        self::assertSame($isLazyLoadDenied, $this->getSubject()->isLazyLoadDenied());
    }

    abstract protected function getSubject(): AbstractCachedExternalRepositoryWrapper;

    abstract protected function getWrappedRepositoryMock(): MockInterface|AbstractExternalRepository;

    abstract protected function getContext(): RepositoryContext;

    abstract public static function assertSame($expected, $actual, string $message = ''): void;

    abstract protected function getTtlPath(): string;
}
