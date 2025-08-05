<?php

declare(strict_types=1);

namespace App\Models\External;

use App\Interfaces\Repository\ExternalRepository;
use App\Interfaces\Repository\GenericFlagAssignmentRepository;
use Aptive\PestRoutesSDK\Resources\GenericFlagAssignments\GenericFlagAssignmentType;

class GenericFlagAssignmentModel extends AbstractExternalModel
{
    public int $id;
    public int $genericFlagId;
    public int $entityId;
    public GenericFlagAssignmentType $type;

    /**
     * @return class-string<ExternalRepository<self>>
     */
    public static function getRepositoryClass(): string
    {
        return GenericFlagAssignmentRepository::class;
    }
}
