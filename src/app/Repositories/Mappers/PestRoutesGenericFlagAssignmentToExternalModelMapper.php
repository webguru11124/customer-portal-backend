<?php

declare(strict_types=1);

namespace App\Repositories\Mappers;

use App\Interfaces\ExternalModelMapper;
use App\Models\External\AbstractExternalModel;
use App\Models\External\GenericFlagAssignmentModel;
use Aptive\PestRoutesSDK\Resources\GenericFlagAssignments\GenericFlagAssignment;

/**
 * @implements ExternalModelMapper<GenericFlagAssignment, GenericFlagAssignmentModel>
 */
class PestRoutesGenericFlagAssignmentToExternalModelMapper implements ExternalModelMapper
{
    /**
     * @param GenericFlagAssignment $source
     *
     * @return GenericFlagAssignmentModel
     */
    public function map(object $source): AbstractExternalModel
    {
        return GenericFlagAssignmentModel::from((array) $source);
    }
}
