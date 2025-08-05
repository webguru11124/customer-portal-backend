<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\CustomerSessionService;
use Closure;
use Illuminate\Http\Request;

class HandleCustomerSession
{
    private const ACCOUNT_NUMBER_PARAMETER_NAME = 'accountNumber';

    public function __construct(private CustomerSessionService $customerSessionService)
    {
    }

    public function handle(Request $request, Closure $next): mixed
    {
        if (!$request->route()?->hasParameter(self::ACCOUNT_NUMBER_PARAMETER_NAME)) {
            return $next($request);
        }

        $user = $request->user();

        if (!($user instanceof User)) {
            return $next($request);
        }

        /** @var string $accountNumber */
        $accountNumber = $request->route(self::ACCOUNT_NUMBER_PARAMETER_NAME);

        $this->customerSessionService->handleSession((int) $accountNumber);

        return $next($request);
    }
}
