<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1;

use App\Actions\ResendEmailVerificationAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class EmailVerificationController extends Controller
{
    /**
     * @param Request $request
     * @param ResendEmailVerificationAction $action
     * @return Response
     */
    public function resendVerificationEmail(
        Request $request,
        ResendEmailVerificationAction $action
    ): Response {
        $action($request->user()->sub);

        return response()->noContent();
    }
}
