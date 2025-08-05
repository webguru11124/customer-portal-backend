<?php

declare(strict_types=1);

namespace App\Repositories\Relations;

use App\Interfaces\Repository\ExternalRepository;
use App\Models\External\AbstractExternalModel;

readonly class BelongsTo implements ExternalModelRelation
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
     * @phpstan-ignore-next-line
     */
    public function getRelated(ExternalRepository $relatedRepository, AbstractExternalModel $model): AbstractExternalModel|null
    {
        $foreignKey = $this->getForeignKey();
        $value = $model->$foreignKey;

        if ($value === null) {
            return null;
        }

        return $relatedRepository->find($value);
    }

    public function getRelatedLazy(
        LazyRelationPicker $picker,
        AbstractExternalModel $model
    ): LazyRelationPicker {
        $foreignKey = $this->getForeignKey();
        $value = $model->$foreignKey;

        if ($value === null) {
            return $picker;
        }

        return $picker->pick($value);
    }

    public function getLazyRelationStrategy(): LazyRelationStrategy
    {
        return new LazyBelongsToStrategy();
    }
}
