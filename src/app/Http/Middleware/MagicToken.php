<?php

namespace App\Http\Middleware;

namespace App\Http\Middleware;

use App\MagicLink\Guards\MagicJwtAuthGuard;
use App\Models\User;
use App\MagicLink\Guards\MagicLinkGuard;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class MagicToken
{
    public function handle(Request $request, Closure $next): mixed
    {
        if ($request->header('X-Auth-Type') === MagicJwtAuthGuard::TYPE) {
            /** @var MagicLinkGuard $guard */
            $guard = auth('magiclinkguard');
            $authUser = $guard->user();

            if ($authUser instanceof User) {
                Auth::shouldUse('magiclinkguard');
                return $next($request);
            }

            $error = $guard->getValidationError();
            if ($error) {
                abort($error->code, 'Unauthorized: ' . $error->message);
            }
        }

        abort(Response::HTTP_UNAUTHORIZED, 'Unauthorized');
    }
}
