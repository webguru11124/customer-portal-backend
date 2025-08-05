<?php

declare(strict_types=1);

namespace App\Exceptions\Entity;

use App\Models\External\AbstractExternalModel;
use Exception;

class RelationNotFoundException extends Exception
{
    public function __construct(AbstractExternalModel $relatedEntity, string $relationName)
    {
        $message = sprintf('Relation %s not found in %s', $relationName, $relatedEntity::class);

        parent::__construct($message);
    }
}
