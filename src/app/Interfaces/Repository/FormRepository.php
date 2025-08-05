<?php

declare(strict_types=1);

namespace App\Interfaces\Repository;

use App\DTO\Form\SearchFormsDTO;
use App\Models\External\FormModel;
use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use Aptive\PestRoutesSDK\Exceptions\ResourceNotFoundException;
use Aptive\PestRoutesSDK\Resources\Forms\Form;
use Illuminate\Support\Collection;

/**
 * @extends ExternalRepository<FormModel>
 */
interface FormRepository extends ExternalRepository
{
    public function searchDocuments(SearchFormsDTO $searchFormsDTO): mixed;

    /**
     * @return Collection<int, Form>
     */
    public function getDocuments(SearchFormsDTO $searchFormsDTO): Collection;

    /**
     * @param int $officeId
     * @param int $documentId
     *
     * @return Form
     *
     * @throws ResourceNotFoundException
     * @throws InternalServerErrorHttpException
     */
    public function getDocument(int $officeId, int $documentId): Form;
}
