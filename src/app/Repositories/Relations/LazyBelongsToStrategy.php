<?php

declare(strict_types=1);

namespace App\Repositories\Relations;

use App\Models\External\AbstractExternalModel;
use Illuminate\Support\Collection;

class LazyBelongsToStrategy implements LazyRelationStrategy
{
    /**
     * @inheritDoc
     */
    public function loadRelated(string $relationName, Collection $searchResult, LazyRelationPicker $picker): Collection
    {
        if ($searchResult->isEmpty()) {
            return $searchResult;
        }

        $fillEmptyRelated = fn (AbstractExternalModel $model) => $model->setRelated($relationName, null);

        if (empty($picker->getValues())) {
            return $searchResult->each($fillEmptyRelated);
        }

        $repository = $picker->getRepository();

        $relatedHeap = $repository->findMany(...$picker->getValues());

        if ($relatedHeap->isEmpty()) {
            return $searchResult->each($fillEmptyRelated);
        }

        /** @var AbstractExternalModel $relatedModel */
        $relatedModel = $relatedHeap->first();
        $relatedPrimaryKey = $relatedModel::getPrimaryKey();
        $foreignKey = $picker->getForeignKey();

        $arrangedHeap = $this->arrangeHeapByPrimaryKey($relatedPrimaryKey, $relatedHeap);
        /** @var AbstractExternalModel $item */
        foreach ($searchResult as $item) {
            $value = $item->$foreignKey;
            $related = $arrangedHeap[$value] ?? null;
            $item->setRelated($relationName, $related);
        }

        return $searchResult;
    }

    /**
     * @param string $primaryKey
     * @param Collection<int, AbstractExternalModel> $relatedHeap
     *
     * @return array<int|string, AbstractExternalModel>
     */
    private function arrangeHeapByPrimaryKey(string $primaryKey, Collection $relatedHeap): array
    {
        $arrangedHeap = [];

        foreach ($relatedHeap as $item) {
            $arrangedHeap[$item->$primaryKey] = $item;
        }

        /* @var array<int|string, AbstractExternalModel> $arrangedHeap */
        return $arrangedHeap;
    }
}
