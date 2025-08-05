<?php

declare(strict_types=1);

namespace App\Repositories\Relations;

use App\Exceptions\Entity\ExternalModelCanNotBeSearchedByAttribute;
use App\Interfaces\Repository\ExternalRepository;
use App\Models\External\AbstractExternalModel;
use Illuminate\Support\Collection;

readonly class HasMany implements ExternalModelRelation
{
    /**
     * @param class-string<AbstractExternalModel> $relatedEntityClass
     * @param string $foreignKey
     */
    public function __construct(
        private string $relatedEntityClass,
        private string $foreignKey
    ) {
    }

    /**
     * @return class-string<AbstractExternalModel>
     */
    public function getRelatedEntityClass(): string
    {
        return $this->relatedEntityClass;
    }

    public function getForeignKey(): string
    {
        return $this->foreignKey;
    }

    /**
     * @return Collection<int, AbstractExternalModel>
     *
     * @throws ExternalModelCanNotBeSearchedByAttribute
     *
     * @phpstan-ignore-next-line
     */
    public function getRelated(ExternalRepository $relatedRepository, AbstractExternalModel $model): Collection
    {
        $relatedModelForeignKey = $this->getForeignKey();
        $modelPrimaryKey = $model::getPrimaryKey();

        $value = $model->$modelPrimaryKey;

        return $relatedRepository->searchBy($relatedModelForeignKey, (array) $value);
    }

    public function getRelatedLazy(
        LazyRelationPicker $picker,
        AbstractExternalModel $model
    ): LazyRelationPicker {
        $modelPrimaryKey = $model::getPrimaryKey();
        $value = $model->$modelPrimaryKey;

        return $picker->pick($value);
    }

    public function getLazyRelationStrategy(): LazyRelationStrategy
    {
        return new LazyHasManyStrategy();
    }
}
