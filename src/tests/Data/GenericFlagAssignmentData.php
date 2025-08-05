<?php

declare(strict_types=1);

namespace Tests\Data;

use App\Models\External\GenericFlagAssignmentModel;
use App\Repositories\Mappers\PestRoutesGenericFlagAssignmentToExternalModelMapper;
use Aptive\PestRoutesSDK\Resources\GenericFlagAssignments\GenericFlagAssignment;
use Aptive\PestRoutesSDK\Resources\GenericFlagAssignments\GenericFlagAssignmentType;

/**
 * @extends AbstractTestPestRoutesData<GenericFlagAssignment, GenericFlagAssignmentModel>
 */
final class GenericFlagAssignmentData extends AbstractTestPestRoutesData
{
    protected static function getRequiredEntityClass(): string
    {
        return GenericFlagAssignment::class;
    }

    protected static function getSignature(): array
    {
        return [
            'genericFlagAssignmentID' => random_int(100, PHP_INT_MAX),
            'genericFlagID' => random_int(100, PHP_INT_MAX),
            'entityID' => random_int(100, PHP_INT_MAX),
            'type' => GenericFlagAssignmentType::SUBS->value,
        ];
    }

    protected static function getMapperClass(): string
    {
        return PestRoutesGenericFlagAssignmentToExternalModelMapper::class;
    }
}
