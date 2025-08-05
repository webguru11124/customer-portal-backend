<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureValidAccountNumber
{
    private const ACCOUNT_NUMBER_PARAMETER_NAME = 'accountNumber';

    public function handle(Request $request, \Closure $next): mixed
    {
        if (!$request->route()?->hasParameter(self::ACCOUNT_NUMBER_PARAMETER_NAME)) {
            abort(
                Response::HTTP_BAD_REQUEST,
                sprintf('\'%s\' parameter is missing', self::ACCOUNT_NUMBER_PARAMETER_NAME)
            );
        }

        $user = $request->user();
        /** @var string|null $accountNumber */
        $accountNumber = $request->route(self::ACCOUNT_NUMBER_PARAMETER_NAME);

        if (!($user instanceof User)) {
            abort(Response::HTTP_UNAUTHORIZED, 'Unauthorized');
        }

        if (!$user->hasAccountNumber((int) $accountNumber)) {
            abort(Response::HTTP_NOT_FOUND, 'Account number not found');
        }

        return $next($request);
    }
}
