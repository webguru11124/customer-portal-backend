<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V2;

use App\Actions\Document\DownloadActionV2;
use App\Enums\Resources;
use App\Exceptions\Document\DocumentLinkDoesNotExist;
use App\Http\Requests\DownloadCustomerDocumentRequest;
use App\Http\Responses\ErrorResponse;
use App\Http\Responses\GetDocumentsGenericResponse;
use App\Services\GenericDocumentService;
use Aptive\Component\Http\Exceptions\ForbiddenHttpException;
use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use Aptive\Component\Http\Exceptions\NotFoundHttpException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

final class DocumentController
{
    public function __construct(
        private readonly GenericDocumentService $genericDocumentService
    ) {
    }

    public function getCustomerDocuments(Request $request, int $accountNumber): Response
    {
        $account = $request->user()->getAccountByAccountNumber($accountNumber);

        try {
            return GetDocumentsGenericResponse::make(
                $request,
                $this->genericDocumentService->getDocumentsForAccount($account)
            );
        } catch (Throwable $exception) {
            return ErrorResponse::fromException($request, $exception);
        }
    }

    /**
     * @throws NotFoundHttpException
     * @throws DocumentLinkDoesNotExist
     * @throws InternalServerErrorHttpException
     * @throws ForbiddenHttpException
     */
    public function downloadCustomerDocument(
        DownloadCustomerDocumentRequest $request,
        int $accountNumber,
        int $documentId,
        DownloadActionV2 $action
    ): StreamedResponse {
        return ($action)(
            account: $request->user()->getAccountByAccountNumber($accountNumber),
            documentId: $documentId,
            documentType: $request->get('documentType', Resources::DOCUMENT->value)
        );
    }
}
