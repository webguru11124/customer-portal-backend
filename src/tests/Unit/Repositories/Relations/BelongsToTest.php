<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories\Relations;

use App\Interfaces\Repository\ExternalRepository;
use App\Models\External\AbstractExternalModel;
use App\Repositories\Relations\BelongsTo;
use App\Repositories\Relations\LazyBelongsToStrategy;
use App\Repositories\Relations\LazyRelationPicker;
use PHPUnit\Framework\TestCase;

final class BelongsToTest extends TestCase
{
    use BelongsToModelTrait;

    private const FOREIGN_KEY = 'foreignKey';
    private const RELATED_MODEL_CLASS = 'relatedModelClass';

    public function test_set_and_get_related_entity_and_foreign_key(): void
    {
        $class = sprintf('relatedClass%d', random_int(1, 9999939));
        $key = sprintf('foreignKey%d', random_int(2, 98989898));

        $relation = new BelongsTo($class, $key);

        $this->assertSame($class, $relation->getRelatedEntityClass());
        $this->assertSame($key, $relation->getForeignKey());
    }

    public function test_get_related_returns_null_if_foreign_key_is_null(): void
    {
        $modelClass = sprintf('relatedClass%d', random_int(1, 9999939));
        $foreignKey = sprintf('foreignKey%d', random_int(2, 98989898));

        $relation = new BelongsTo($modelClass, $foreignKey);

        $repositoryMock = $this->createMock(ExternalRepository::class);
        $repositoryMock->expects(self::never())->method('find');

        $modelMock = $this->createMock(AbstractExternalModel::class);

        $this->assertNull($relation->getRelated(
            $repositoryMock,
            $modelMock
        ));
    }

    public function test_get_related_looks_for_related_model(): void
    {
        $class = sprintf('relatedClass%d', random_int(1, 9999939));
        $relatedModelId = random_int(3, 198989891);

        $model = $this->getModel($relatedModelId);

        $relatedModelMock = $this->createMock(AbstractExternalModel::class);

        $relation = new BelongsTo($class, self::FOREIGN_KEY);

        $repositoryMock = $this->createMock(ExternalRepository::class);
        $repositoryMock
            ->expects(self::once())
            ->method('find')
            ->with($relatedModelId)
            ->willReturn($relatedModelMock);

        $this->assertSame(
            $relatedModelMock,
            $relation->getRelated(
                $repositoryMock,
                $model
            )
        );
    }

    public function test_get_related_lazy_picks_data(): void
    {
        $class = sprintf('relatedClass%d', random_int(1, 9999939));
        $relatedModelId = random_int(3, 198989891);

        $model = $this->getModel($relatedModelId);

        $relation = new BelongsTo($class, self::FOREIGN_KEY);

        $repositoryMock = $this->createMock(ExternalRepository::class);

        $lazyPicker = new LazyRelationPicker($repositoryMock, $relation->getForeignKey());

        $result = $relation->getRelatedLazy(
            $lazyPicker,
            $model
        );

        $this->assertEquals([$relatedModelId], $result->getValues());
    }

    public function test_get_related_lazy_peeks_no_values_if_foreing_key_is_null(): void
    {
        $class = sprintf('relatedClass%d', random_int(1, 9999939));
        $relatedModelId = null;

        $model = $this->getModel($relatedModelId);

        $relation = new BelongsTo($class, self::FOREIGN_KEY);

        $repositoryMock = $this->createMock(ExternalRepository::class);

        $lazyPicker = new LazyRelationPicker($repositoryMock, $relation->getForeignKey());
        $result = $relation->getRelatedLazy(
            $lazyPicker,
            $model
        );

        $this->assertEquals([], $result->getValues());
    }

    public function test_it_returns_a_proper_lazy_relation_strategy(): void
    {
        $relation = new BelongsTo(self::RELATED_MODEL_CLASS, self::FOREIGN_KEY);

        self::assertInstanceOf(LazyBelongsToStrategy::class, $relation->getLazyRelationStrategy());
    }
}
