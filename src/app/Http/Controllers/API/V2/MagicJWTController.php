<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V2;

use App\Actions\MagicLink\MagicJWTGeneratorAction;
use App\Http\Controllers\Controller;
use App\Http\Responses\ErrorResponse;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MagicJWTController extends Controller
{
    /**
     * @param Request $request
     * @param MagicJWTGeneratorAction $action
     * @return JsonResponse|ErrorResponse
     * @throws \Aptive\Component\JsonApi\Exceptions\ValidationException
     */
    public function getToken(Request $request, MagicJWTGeneratorAction $action): JsonResponse|ErrorResponse
    {
        /** @var User $authUser */
        $authUser = auth('magiclinkguard')->user();
        $link = ($action)($authUser);
        return response()->json(['jwt' => $link]);
    }
}
