<?php

namespace Tests\Unit\Repositories\Relations;

use App\Interfaces\Repository\ExternalRepository;
use App\Models\External\AbstractExternalModel;
use App\Repositories\Relations\LazyHasManyStrategy;
use App\Repositories\Relations\LazyRelationPicker;
use Illuminate\Support\Collection;
use Tests\TestCase;

class LazyHasManyStrategyTest extends TestCase
{
    use HasManyModelTrait;

    private const RELATION_NAME = 'testRelationName';
    private const FOREIGN_KEY = 'foreignKey';

    protected LazyHasManyStrategy $subject;

    public function setUp(): void
    {
        parent::setUp();

        $this->subject = new LazyHasManyStrategy();
    }

    public function test_it_doesnt_treat_result_if_result_is_empty(): void
    {
        $searchResult = new Collection();
        $repositoryMock = $this->createMock(ExternalRepository::class);

        $repositoryMock->expects(self::never())->method('searchBy');

        $picker = new LazyRelationPicker($repositoryMock, self::FOREIGN_KEY);
        $picker->pick(random_int(1, 100));

        $this->subject->loadRelated(
            self::RELATION_NAME,
            $searchResult,
            $picker
        );
    }

    public function test_it_feels_result_with_empty_collections_if_picker_does_not_provide_values(): void
    {
        $modelMock = $this->createMock(AbstractExternalModel::class);
        $searchResult = new Collection([$modelMock]);
        $repositoryMock = $this->createMock(ExternalRepository::class);

        $repositoryMock->expects(self::never())->method('searchBy');
        $modelMock->expects(self::once())
            ->method('setRelated')
            ->with(self::RELATION_NAME, new Collection());

        $picker = new LazyRelationPicker($repositoryMock, self::FOREIGN_KEY);
        $picker->pick(null);

        $this->subject->loadRelated(
            self::RELATION_NAME,
            $searchResult,
            $picker
        );
    }

    public function test_it_feels_result_with_empty_collections_if_related_not_found(): void
    {
        $modelMock = $this->createMock(AbstractExternalModel::class);
        $searchResult = new Collection([$modelMock]);
        $repositoryMock = $this->createMock(ExternalRepository::class);
        $value = random_int(1, 100);

        $repositoryMock->expects(self::once())
            ->method('searchBy')
            ->willReturn(new Collection());

        $modelMock->expects(self::once())
            ->method('setRelated')
            ->with(self::RELATION_NAME, new Collection());

        $picker = new LazyRelationPicker($repositoryMock, self::FOREIGN_KEY);
        $picker->pick($value);

        $result = $this->subject->loadRelated(
            self::RELATION_NAME,
            $searchResult,
            $picker
        );

        self::assertSame($searchResult, $result);
    }
}
