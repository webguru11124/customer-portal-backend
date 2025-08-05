<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Responses\ErrorResponse;
use App\Interfaces\Repository\OfficeRepository;
use Illuminate\Http\JsonResponse;
use Aptive\PestRoutesSDK\CredentialsRepository;

class OfficeController extends Controller
{
    public function __construct(
        private readonly OfficeRepository $officeRepository,
        private readonly CredentialsRepository $credentialsRepository,
    ) {
    }

    /**
     * @return JsonResponse|ErrorResponse
     */
    public function getIds(): JsonResponse|ErrorResponse
    {
        $officeIds = $this->officeRepository->getAllOfficeIds();

        return response()->json($officeIds);
    }

    /**
     * @return JsonResponse|ErrorResponse
     */
    public function getPestroutesCredentials(int $officeID): JsonResponse|ErrorResponse
    {
        $credentials = $this->getParsedPestroutesCredentials($officeID);
        return response()->json($credentials);
    }

    /**
     * @return array<string, string>
     */
    private function getParsedPestroutesCredentials(int $officeID): array
    {
        $credentials =  $this->credentialsRepository->find($officeID);

        return [
            'authenticationKey' => $credentials->key,
            'authenticationToken' => $credentials->token
        ];
    }
}
