<?php

declare(strict_types=1);

namespace App\Repositories\Relations;

use App\Models\External\AbstractExternalModel;
use Illuminate\Support\Collection;

class LazyHasManyStrategy implements LazyRelationStrategy
{
    /**
     * @inheritDoc
     */
    public function loadRelated(string $relationName, Collection $searchResult, LazyRelationPicker $picker): Collection
    {
        if ($searchResult->isEmpty()) {
            return $searchResult;
        }

        $fillEmptyRelated = fn (AbstractExternalModel $model) => $model->setRelated($relationName, new Collection());

        if (empty($picker->getValues())) {
            return $searchResult->each($fillEmptyRelated);
        }

        $repository = $picker->getRepository();
        $foreignKey = $picker->getForeignKey();

        $relatedHeap = $repository->searchBy(
            $foreignKey,
            $picker->getValues()
        );

        if ($relatedHeap->isEmpty()) {
            return $searchResult->each($fillEmptyRelated);
        }

        /** @var AbstractExternalModel $foundModel */
        $foundModel = $searchResult->first();
        $primaryKey = $foundModel::getPrimaryKey();

        $arrangedHeap = $this->arrangeHeapByForeignKey($foreignKey, $relatedHeap);
        /** @var AbstractExternalModel $item */
        foreach ($searchResult as $item) {
            $value = $item->$primaryKey;
            $related = $arrangedHeap[$value] ?? new Collection();
            $item->setRelated($relationName, $related);
        }

        return $searchResult;
    }

    /**
     * @param string $foreignKey
     * @param Collection<int, AbstractExternalModel> $relatedHeap
     *
     * @return array<int|string, Collection<int, AbstractExternalModel>>
     */
    private function arrangeHeapByForeignKey(string $foreignKey, Collection $relatedHeap): array
    {
        $arrangedHeap = [];

        foreach ($relatedHeap as $item) {
            if (!isset($arrangedHeap[$item->$foreignKey])) {
                $arrangedHeap[$item->$foreignKey] = new Collection();
            }

            $arrangedHeap[$item->$foreignKey]->add($item);
        }

        /* @var array<int|string, Collection<int, AbstractExternalModel>> $arrangedHeap */
        return $arrangedHeap;
    }
}
