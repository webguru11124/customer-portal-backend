<?php

declare(strict_types=1);

namespace App\Exceptions\Entity;

use Exception;

class InvalidRelationName extends Exception
{
    public function __construct(string $relationName)
    {
        $message = sprintf(
            'Invalid relation name %s. Expected template: Relation.NestedRelation.NextNestedRelation',
            $relationName
        );

        parent::__construct($message);
    }
}
