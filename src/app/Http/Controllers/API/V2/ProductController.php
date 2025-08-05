<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V2;

use App\Http\Controllers\Controller;
use App\Services\AccountService;
use App\Services\PlanBuilderService;
use App\Http\Responses\V2\GetProductsResponse;
use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use Aptive\Component\JsonApi\Exceptions\ValidationException;
use Aptive\Illuminate\Http\JsonApi\JsonApiResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * @param PlanBuilderService $service
     * @param int $accountNumber
     * @param Request $request
     *
     * @return JsonApiResponse
     *
     * @throws InternalServerErrorHttpException
     * @throws ValidationException
     */
    /**
     * @param PlanBuilderService $service
     * @param int $accountNumber
     * @param Request $request
     *
     * @return JsonApiResponse
     *
     * @throws ValidationException
     */
    public function get(
        PlanBuilderService $service,
        AccountService $accountService,
        int $accountNumber,
        Request $request
    ): JsonApiResponse {
        $account = $accountService->getAccountByAccountNumber($accountNumber);

        return GetProductsResponse::make($request, $service->getProducts($account->office_id));
    }
}
