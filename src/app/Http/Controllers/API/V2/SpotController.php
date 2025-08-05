<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V2;

use App\Actions\Spot\ShowSpotsFromFlexIVRAction;
use App\Exceptions\Account\AccountNotFoundException;
use App\Exceptions\Entity\EntityNotFoundException;
use App\Http\Controllers\Controller;
use App\Http\Responses\V2\GetSpotsResponse;
use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use Aptive\Component\Http\Exceptions\NotFoundHttpException;
use Aptive\Component\JsonApi\Exceptions\ValidationException;
use Aptive\Illuminate\Http\JsonApi\JsonApiResponse;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\Request;
use JsonException;

final class SpotController extends Controller
{
    /**
     * @param ShowSpotsFromFlexIVRAction $action
     * @param int $accountNumber
     * @param Request $request
     *
     * @return JsonApiResponse
     *
     * @throws InternalServerErrorHttpException
     * @throws NotFoundHttpException
     * @throws ValidationException
     */
    public function search(ShowSpotsFromFlexIVRAction $action, int $accountNumber, Request $request): JsonApiResponse
    {
        try {
            return GetSpotsResponse::make($request, $action($accountNumber));
        } catch (AccountNotFoundException|EntityNotFoundException $exception) {
            throw new NotFoundHttpException(previous: $exception);
        } catch (GuzzleException|JsonException $exception) {
            throw new InternalServerErrorHttpException(previous: $exception);
        }
    }
}
