<?php

declare(strict_types=1);

namespace App\Traits\Repositories;

use App\Interfaces\ExternalModelMapper;
use App\Models\External\AbstractExternalModel;
use Aptive\PestRoutesSDK\Entity;

/**
 * @template S of Entity
 * @template R of AbstractExternalModel
 */
trait EntityMapperAware
{
    /** @var ExternalModelMapper<S, R> */
    protected ExternalModelMapper $entityMapper;

    protected function getEntityMapper(): ExternalModelMapper
    {
        return $this->entityMapper;
    }
}
