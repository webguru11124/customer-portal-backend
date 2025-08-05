<?php

namespace App\Http\Middleware;

namespace App\Http\Middleware;

use App\MagicLink\Guards\MagicJwtAuthGuard;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class Auth0OrFusion
{
    public function handle(Request $request, Closure $next): mixed
    {
        if ($request->header('X-Auth-Type') === MagicJwtAuthGuard::TYPE || auth('auth0')->user()) {
            return $next($request);
        }

        if ($request->bearerToken() && auth('fusion')->user() instanceof User) {
            return $next($request);
        }

        abort(Response::HTTP_UNAUTHORIZED, 'Unauthorized');
    }
}
