<?php

namespace Tests\Unit\Repositories\Relations;

use App\Interfaces\Repository\ExternalRepository;
use App\Models\External\AbstractExternalModel;
use App\Repositories\Relations\BelongsTo;
use App\Repositories\Relations\LazyBelongsToStrategy;
use App\Repositories\Relations\LazyRelationPicker;
use Illuminate\Support\Collection;
use Tests\TestCase;

class LazyBelongsToStrategyTest extends TestCase
{
    private const RELATION_NAME = 'testRelationName';
    private const FOREIGN_KEY_NAME = 'foreignKey';

    protected LazyBelongsToStrategy $subject;

    public function setUp(): void
    {
        parent::setUp();

        $this->subject = new LazyBelongsToStrategy();
    }

    public function test_it_doesnt_treat_result_if_result_is_empty(): void
    {
        $findResult = new Collection();
        $repositoryMock = $this->createMock(ExternalRepository::class);

        $repositoryMock->expects(self::never())->method('findMany');

        $lazyPicker = new LazyRelationPicker($repositoryMock, self::FOREIGN_KEY_NAME);
        $lazyPicker->pick(random_int(1, 100));

        $this->subject->loadRelated(self::RELATION_NAME, $findResult, $lazyPicker);
    }

    public function test_it_feels_result_with_empty_data_if_picker_does_not_provide_values(): void
    {
        $modelMock = $this->createMock(AbstractExternalModel::class);
        $findResult = new Collection([$modelMock]);

        $repositoryMock = $this->createMock(ExternalRepository::class);

        $repositoryMock->expects(self::never())->method('findMany');
        $modelMock->expects(self::once())
            ->method('setRelated')
            ->with(self::RELATION_NAME, null);

        $lazyPicker = new LazyRelationPicker($repositoryMock, self::FOREIGN_KEY_NAME);
        $lazyPicker->pick(null);

        /* @var Collection<int, AbstractExternalModel> $result */
        $this->subject->loadRelated(self::RELATION_NAME, $findResult, $lazyPicker);
    }

    public function test_it_feels_result_with_empty_data_if_related_not_found(): void
    {
        $modelMock = $this->createMock(AbstractExternalModel::class);
        $findResult = new Collection([$modelMock]);
        $repositoryMock = $this->createMock(ExternalRepository::class);
        $value = random_int(1, 100);

        $repositoryMock->expects(self::once())
            ->method('findMany')
            ->willReturn(new Collection());

        $modelMock->expects(self::once())
            ->method('setRelated')
            ->with(self::RELATION_NAME, null);

        $lazyPicker = new LazyRelationPicker($repositoryMock, self::FOREIGN_KEY_NAME);
        $lazyPicker->pick($value);

        $result = $this->subject->loadRelated(self::RELATION_NAME, $findResult, $lazyPicker);

        self::assertSame($findResult, $result);
    }

    public function test_it_loads_related(): void
    {
        $relatedRepositoryMock = $this->createMock(ExternalRepository::class);
        $relatedId = random_int(1, 1000);

        $model = $this->getSearchResultModel($relatedId);
        $findResult = new Collection([$model]);

        $relatedModel = $this->getRelatedModel($relatedId);

        $relatedRepositoryMock->expects(self::once())
            ->method('findMany')
            ->with($relatedId)
            ->willReturn(new Collection([$relatedModel]));

        $lazyPicker = new LazyRelationPicker($relatedRepositoryMock, self::FOREIGN_KEY_NAME);
        $lazyPicker->pick($relatedId);

        /** @var Collection<int, AbstractExternalModel> $result */
        $result = $this->subject->loadRelated(self::RELATION_NAME, $findResult, $lazyPicker);

        self::assertSame($relatedModel, $result->first()->testRelationName);
    }

    private function getSearchResultModel(int $relatedId): AbstractExternalModel
    {
        return new class ($relatedId) extends AbstractExternalModel {
            public function __construct(
                public int $foreignKey
            ) {
            }

            public function getRelations(): array
            {
                return [
                    'testRelationName' => new BelongsTo(
                        AbstractExternalModel::class,
                        'foreignKey'
                    ),
                ];
            }

            public static function getRepositoryClass(): string
            {
                return ExternalRepository::class;
            }
        };
    }

    private function getRelatedModel(int $relatedId): AbstractExternalModel
    {
        return new class ($relatedId) extends AbstractExternalModel {
            public function __construct(
                public int $id
            ) {
            }

            public static function getRepositoryClass(): string
            {
                return ExternalRepository::class;
            }
        };
    }
}
