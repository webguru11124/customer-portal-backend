<?php

namespace App\Http\Middleware;

use App\Exceptions\Admin\ApiKeyMissingException;
use App\Helpers\ApiKey;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

class RequireKey
{
    public function __construct(
        private ApiKey $keyHelper
    ) {
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse) $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function handle(Request $request, Closure $next): Response|JsonResponse|RedirectResponse|null
    {
        try {
            if ($this->keyHelper->validateKeyPermission(
                (string) $request->bearerToken(),
                (string) Route::currentRouteName()
            )) {
                return $next($request);
            }
        } catch (ApiKeyMissingException $exception) {
            Log::error($exception);
        }

        return response()->json(['message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
    }
}
