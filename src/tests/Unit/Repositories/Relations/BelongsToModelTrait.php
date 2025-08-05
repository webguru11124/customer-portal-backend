<?php

namespace Tests\Unit\Repositories\Relations;

use App\Models\External\AbstractExternalModel;
use App\Repositories\AbstractExternalRepository;

trait BelongsToModelTrait
{
    private function getModel(int|null $foreignKey): AbstractExternalModel
    {
        return new class ($foreignKey) extends AbstractExternalModel {
            private object $related;

            public function __construct(public int|null $foreignKey)
            {
            }

            public static function getRepositoryClass(): string
            {
                return AbstractExternalRepository::class;
            }

            public function setRelated(string $relationName, ?object $relatedObject): AbstractExternalModel
            {
                $this->related = $relatedObject;

                return $this;
            }

            public function getRelated(string $relationName): object|null
            {
                return $this->related;
            }
        };
    }
}
