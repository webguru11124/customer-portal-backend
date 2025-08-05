<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V2\Admin;

use App\Actions\MagicLink\MagicLinkGeneratorAction;
use App\Exceptions\Account\AccountNotFoundException;
use App\Http\Controllers\Controller;
use App\Http\Requests\MagicLinkRequest;
use App\Http\Responses\ErrorResponse;
use Illuminate\Http\JsonResponse;

class MagicLinkController extends Controller
{
    /**
     * @param MagicLinkRequest $request
     * @param MagicLinkGeneratorAction $action
     * @return JsonResponse|ErrorResponse
     * @throws \Aptive\Component\JsonApi\Exceptions\ValidationException
     */
    public function getLink(MagicLinkRequest $request, MagicLinkGeneratorAction $action): JsonResponse|ErrorResponse
    {
        try {
            $link = ($action)($request->email, $request->hours);
            return response()->json(['link' => $link]);
        } catch (AccountNotFoundException $exception) {
            return ErrorResponse::fromException($request, $exception, $exception->statusCode());
        }
    }
}
