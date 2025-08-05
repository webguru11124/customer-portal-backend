<?php

namespace App\Http\Middleware;

namespace App\Http\Middleware;

use App\MagicLink\Guards\MagicJwtAuthGuard;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MagicLink
{
    public function handle(Request $request, Closure $next): mixed
    {
        if ($request->header('X-Auth-Type') === MagicJwtAuthGuard::TYPE &&
            $request->bearerToken() && auth('magicjwtguard')->user() instanceof User
        ) {
            Auth::shouldUse('magicjwtguard');
            return $next($request);
        }

        return $next($request);
    }
}
