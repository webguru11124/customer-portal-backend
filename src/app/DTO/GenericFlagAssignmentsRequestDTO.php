<?php

declare(strict_types=1);

namespace App\DTO;

use Aptive\PestRoutesSDK\Resources\GenericFlagAssignments\GenericFlagAssignmentType;

final class GenericFlagAssignmentsRequestDTO
{
    public function __construct(
        public int $genericFlagId,
        public int $entityId,
        public GenericFlagAssignmentType $type,
    ) {
    }
}
