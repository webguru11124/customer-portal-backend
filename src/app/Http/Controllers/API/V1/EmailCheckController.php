<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1;

use App\Actions\CheckEmailAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\EmailCheckRequest;
use App\Http\Responses\ErrorResponse;
use Illuminate\Http\JsonResponse;

class EmailCheckController extends Controller
{
    /**
     * @param EmailCheckRequest $request
     *
     * @return JsonResponse|ErrorResponse
     */
    public function check(EmailCheckRequest $request, CheckEmailAction $action): JsonResponse|ErrorResponse
    {
        return response()->json(($action)($request->email, $request->auth ?? 'Auth0'));
    }
}
