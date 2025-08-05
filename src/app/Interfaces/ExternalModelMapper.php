<?php

declare(strict_types=1);

namespace App\Interfaces;

use App\Models\External\AbstractExternalModel;
use Aptive\PestRoutesSDK\Entity;

/**
 * @template S of Entity
 * @template R of AbstractExternalModel
 */
interface ExternalModelMapper
{
    /**
     * @param S $source
     *
     * @return R
     */
    public function map(object $source): AbstractExternalModel;
}
