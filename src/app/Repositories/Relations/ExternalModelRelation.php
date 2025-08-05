<?php

declare(strict_types=1);

namespace App\Repositories\Relations;

use App\Interfaces\Repository\ExternalRepository;
use App\Models\External\AbstractExternalModel;

interface ExternalModelRelation
{
    /**
     * @return class-string<AbstractExternalModel>
     */
    public function getRelatedEntityClass(): string;

    public function getForeignKey(): string;

    /**
     * @phpstan-ignore-next-line
     */
    public function getRelated(ExternalRepository $relatedRepository, AbstractExternalModel $model): mixed;

    public function getRelatedLazy(
        LazyRelationPicker $picker,
        AbstractExternalModel $model
    ): LazyRelationPicker;

    public function getLazyRelationStrategy(): LazyRelationStrategy;
}
