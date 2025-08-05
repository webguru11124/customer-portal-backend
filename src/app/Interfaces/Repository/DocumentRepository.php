<?php

namespace App\Interfaces\Repository;

use App\DTO\Document\SearchDocumentsDTO;
use App\Models\External\DocumentModel;
use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use Aptive\PestRoutesSDK\Exceptions\ResourceNotFoundException;
use Aptive\PestRoutesSDK\Resources\Documents\Document;
use Illuminate\Support\Collection;

/**
 * @extends ExternalRepository<DocumentModel>
 */
interface DocumentRepository extends ExternalRepository
{
    public function searchDocuments(SearchDocumentsDTO $searchDocumentDTO): mixed;

    /**
     * @return Collection<int, Document>
     */
    public function getDocuments(
        SearchDocumentsDTO $searchDocumentDTO
    ): Collection;

    /**
     * Get single document.
     *
     * @param int $officeId
     * @param int $documentId
     *
     * @return Document
     *
     * @throws ResourceNotFoundException when document cannot be found
     * @throws InternalServerErrorHttpException
     */
    public function getDocument(int $officeId, int $documentId): Document;

    /**
     * @param int[] $appointmentIds
     *
     * @return Collection<int, DocumentModel>
     */
    public function searchByAppointmentId(array $appointmentIds): Collection;
}
