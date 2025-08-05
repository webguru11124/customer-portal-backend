<?php

namespace Tests\Unit\Repositories\Relations;

use App\Models\External\AbstractExternalModel;
use App\Repositories\AbstractExternalRepository;

trait HasManyModelTrait
{
    private function getModel(int $modelId): AbstractExternalModel
    {
        return new class ($modelId) extends AbstractExternalModel {
            public function __construct(public int $id)
            {
            }

            public static function getRepositoryClass(): string
            {
                return AbstractExternalRepository::class;
            }
        };
    }
}
