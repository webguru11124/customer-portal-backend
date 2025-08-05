<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V2;

use App\Actions\Payment\CreatePaymentActionV2;
use App\Http\Requests\V2\CreatePaymentRequest;
use App\Http\Responses\ErrorResponse;
use Aptive\Component\Http\HttpStatus;
use Illuminate\Http\JsonResponse;

class PaymentController extends \App\Http\Controllers\API\V1\PaymentController
{
    public function createAptivePayment(
        CreatePaymentRequest $request,
        CreatePaymentActionV2 $action,
        int $accountNumber
    ): JsonResponse|ErrorResponse {
        try {
            return response()->json(($action)($request, $accountNumber)->toArray(), HttpStatus::OK);
        } catch (\Throwable $exception) {
            return ErrorResponse::fromException($request, $exception);
        }
    }
}
