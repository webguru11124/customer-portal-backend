<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1;

use App\Actions\Document\DownloadAction;
use App\Exceptions\Document\DocumentLinkDoesNotExist;
use App\Http\Responses\ErrorResponse;
use App\Http\Responses\GetDocumentsResponse;
use App\Services\DocumentService;
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
        private readonly DocumentService $documentService
    ) {
    }

    public function getCustomerDocuments(Request $request, int $accountNumber): Response
    {
        $account = $request->user()->getAccountByAccountNumber($accountNumber);

        try {
            return GetDocumentsResponse::make(
                $request,
                $this->documentService->getDocumentsForAccount($account)
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
        Request $request,
        int $accountNumber,
        int $documentId,
        DownloadAction $action
    ): StreamedResponse {
        $account = $request->user()->getAccountByAccountNumber($accountNumber);

        return ($action)($account, $documentId);
    }
}
