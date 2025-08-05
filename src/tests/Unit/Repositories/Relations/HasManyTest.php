<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories\Relations;

use App\Interfaces\Repository\ExternalRepository;
use App\Models\External\AbstractExternalModel;
use App\Repositories\Relations\HasMany;
use App\Repositories\Relations\LazyRelationPicker;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

final class HasManyTest extends TestCase
{
    use HasManyModelTrait;

    public function test_set_and_get_related_entity_and_foreign_key(): void
    {
        $class = sprintf('relatedClass%d', random_int(1, 9999939));
        $key = sprintf('foreignKey%d', random_int(2, 98989898));

        $relation = new HasMany($class, $key);

        $this->assertSame($class, $relation->getRelatedEntityClass());
        $this->assertSame($key, $relation->getForeignKey());
    }

    public function test_get_related_looks_for_related_model(): void
    {
        $class = sprintf('relatedClass%d', random_int(1, 9999939));
        $foreignKey = sprintf('foreignKey%d', random_int(2, 98989898));
        $modelId = random_int(3, 198989891);

        $model = $this->getModel($modelId);

        $relatedModels = new Collection(
            $this->createMock(AbstractExternalModel::class)
        );

        $relation = new HasMany($class, $foreignKey);

        $repositoryMock = $this->createMock(ExternalRepository::class);
        $repositoryMock
            ->expects(self::once())
            ->method('searchBy')
            ->with($foreignKey, [$modelId])
            ->willReturn($relatedModels);

        $this->assertSame(
            $relatedModels,
            $relation->getRelated(
                $repositoryMock,
                $model
            )
        );
    }

    public function test_get_related_lazy_picks_data(): void
    {
        $class = sprintf('relatedClass%d', random_int(1, 9999939));
        $foreignKey = sprintf('foreignKey%d', random_int(2, 98989898));
        $modelId = random_int(3, 198989891);

        $model = $this->getModel($modelId);

        $relation = new HasMany($class, $foreignKey);

        $repositoryMock = $this->createMock(ExternalRepository::class);

        $lazyPicker = new LazyRelationPicker($repositoryMock, $relation->getForeignKey());
        $result = $relation->getRelatedLazy(
            $lazyPicker,
            $model
        );

        $this->assertEquals([$modelId], $result->getValues());
    }
}
