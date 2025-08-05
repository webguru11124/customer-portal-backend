<?php

declare(strict_types=1);

namespace App\Repositories\Mappers;

use App\Interfaces\ExternalModelMapper;
use App\Models\External\AbstractExternalModel;
use App\Models\External\DocumentModel;
use Aptive\PestRoutesSDK\Resources\Documents\Document;

/**
 * @implements ExternalModelMapper<Document, DocumentModel>
 */
class PestRoutesDocumentToExternalModelMapper implements ExternalModelMapper
{
    /**
     * @param Document $source
     *
     * @return DocumentModel
     */
    public function map(object $source): AbstractExternalModel
    {
        return DocumentModel::from((array) $source);
    }
}
