<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V2;

use App\Actions\Subscription\ActivateSubscriptionAction;
use App\Actions\Subscription\CreateFrozenSubscriptionAction;
use App\Actions\Subscription\ShowSubscriptionsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateSubscriptionRequest;
use App\Http\Responses\ErrorResponse;
use App\Http\Responses\SearchSubscriptionsResponse;
use Aptive\Component\JsonApi\Exceptions\ValidationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Throwable;

class SubscriptionController extends Controller
{
    /**
     * @param Request $request
     * @param ShowSubscriptionsAction $action
     * @param int $accountNumber
     *
     * @return Response
     *
     * @throws ValidationException
     */
    public function getUserSubscriptions(
        Request $request,
        ShowSubscriptionsAction $action,
        int $accountNumber
    ): Response {
        try {
            $account = $request->user()->getAccountByAccountNumber($accountNumber);

            return SearchSubscriptionsResponse::make($request, ($action)($account));
        } catch (Throwable $exception) {
            return ErrorResponse::fromException($request, $exception);
        }
    }

    public function createFrozenSubscription(
        CreateSubscriptionRequest $request,
        CreateFrozenSubscriptionAction $action,
        int $accountNumber
    ): JsonResponse|ErrorResponse {
        try {
            $account = $request->user()->getAccountByAccountNumber($accountNumber);

            return response()->json(($action)($account, $request)->toArray());
        } catch (Throwable $exception) {
            return ErrorResponse::fromException($request, $exception);
        }
    }

    public function activateSubscription(
        Request $request,
        ActivateSubscriptionAction $action,
        int $accountNumber,
        int $subscriptionId
    ): JsonResponse|ErrorResponse {
        try {
            $account = $request->user()->getAccountByAccountNumber($accountNumber);

            return response()->json(($action)($account, $subscriptionId)->toArray());
        } catch (Throwable $exception) {
            return ErrorResponse::fromException($request, $exception);
        }
    }
}
