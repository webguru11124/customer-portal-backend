<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V2;

use App\Actions\Upgrade\ShowUpgradesAction;
use App\Exceptions\Account\AccountNotFoundException;
use App\Exceptions\Entity\EntityNotFoundException;
use App\Exceptions\PlanBuilder\FieldNotFound;
use App\Exceptions\Subscription\SubscriptionNotFound;
use App\Http\Controllers\Controller;
use App\Http\Responses\V2\GetUpgradesResponse;
use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use Aptive\Component\Http\Exceptions\NotFoundHttpException;
use Aptive\Component\JsonApi\Exceptions\ValidationException;
use Aptive\Illuminate\Http\JsonApi\JsonApiResponse;
use Illuminate\Http\Request;

class UpgradeController extends Controller
{
    /**
     * @param ShowUpgradesAction $action
     * @param int $accountNumber
     * @param Request $request
     *
     * @return JsonApiResponse
     *
     * @throws InternalServerErrorHttpException
     * @throws NotFoundHttpException
     * @throws ValidationException
     */
    public function get(ShowUpgradesAction $action, int $accountNumber, Request $request): JsonApiResponse
    {
        try {
            $result = $action($accountNumber);
            return GetUpgradesResponse::make($request, $result);
        } catch (AccountNotFoundException|EntityNotFoundException|SubscriptionNotFound $exception) {
            throw new NotFoundHttpException(previous: $exception);
        } catch (FieldNotFound $exception) {
            throw new InternalServerErrorHttpException(previous: $exception);
        }
    }
}
