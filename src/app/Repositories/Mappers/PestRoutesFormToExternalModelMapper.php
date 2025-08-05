<?php

declare(strict_types=1);

namespace App\Repositories\Mappers;

use App\Interfaces\ExternalModelMapper;
use App\Models\External\AbstractExternalModel;
use App\Models\External\FormModel;
use Aptive\PestRoutesSDK\Resources\Forms\Form;

/**
 * @implements ExternalModelMapper<Form, FormModel>
 */
class PestRoutesFormToExternalModelMapper implements ExternalModelMapper
{
    /**
     * @param Form $source
     *
     * @return FormModel
     */
    public function map(object $source): AbstractExternalModel
    {
        return FormModel::from((array) $source);
    }
}
