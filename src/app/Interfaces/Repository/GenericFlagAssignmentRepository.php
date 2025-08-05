<?php

declare(strict_types=1);

namespace App\Interfaces\Repository;

use App\DTO\GenericFlagAssignmentsRequestDTO;
use App\Models\External\GenericFlagAssignmentModel;

/**
 * @extends ExternalRepository<GenericFlagAssignmentModel>
 */
interface GenericFlagAssignmentRepository extends ExternalRepository
{
    public function assignGenericFlag(GenericFlagAssignmentsRequestDTO $assignmentsRequestDTO): int;
}
