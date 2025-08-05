<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\MagicLink\Guards\MagicJwtAuthGuard;
use App\FusionAuth\FusionAuthJwtGuard;
use App\Models\User;
use App\Services\UserService;
use Auth0\Laravel\Contract\Model\User as Auth0User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

final readonly class Authorize
{
    public function __construct(
        private UserService $userService
    ) {
    }

    public function handle(Request $request, Closure $next): mixed
    {
        if ($request->header('X-Auth-Type') === MagicJwtAuthGuard::TYPE) {
            $this->handleMagicLinkAuthUser();
            return $next($request);
        }

        /** @var ?Auth0User $auth0User */
        $auth0User = auth('auth0')->user();
        if ($auth0User instanceof Auth0User) {
            $this->handleAuth0User($auth0User);
        } else {
            $this->handleFusionAuthUser();

        }

        return $next($request);
    }

    private function handleAuth0User(Auth0User $auth0User): void
    {
        if ($auth0User->getAttribute('email') === null) {
            abort(Response::HTTP_UNAUTHORIZED, 'Email is missing from the token');
        }

        if ($auth0User->getAttribute('email_verified') !== true) {
            abort(Response::HTTP_UNAUTHORIZED, 'Email is not verified');
        }

        $user = $this->findOrCreateUser(
            $auth0User->getAttribute('sub'),
            $auth0User->getAttribute('email')
        );

        if ($user === null) {
            abort(Response::HTTP_PRECONDITION_FAILED, 'Could not find existing account in PestRoutes');
        }
        $this->userService->syncUserAccounts($user);

        Auth::login($user);
        Auth::shouldUse('auth0');
    }

    private function handleFusionAuthUser(): void
    {
        try {
            /** @var FusionAuthJwtGuard $guard */
            $guard = auth('fusion');
            $guard->payload();
            $authUser = $guard->user();
        } catch (TokenInvalidException $exception) {
            abort(Response::HTTP_UNAUTHORIZED, $exception->getMessage());
        }

        if (!($authUser instanceof User)) {
            abort(Response::HTTP_UNAUTHORIZED, 'Unauthorized');
        }

        $authUser = $this->findOrCreateUser(
            $authUser->getAttribute(User::FUSIONCOLUMN),
            $authUser->getAttribute('email'),
            User::FUSIONCOLUMN
        );

        if ($authUser === null) {
            abort(Response::HTTP_PRECONDITION_FAILED, 'Could not find existing account in PestRoutes');
        }

        $this->userService->syncUserAccounts($authUser);
        Auth::shouldUse('fusion');
    }

    private function findOrCreateUser(string $externalId, string $email, string $idName = User::AUTH0COLUMN): User|null
    {
        $user = $this->userService->findUserByEmailAndExtId($email, $externalId, $idName);

        return $user ?: $this->userService->createOrUpdateUserWithExternalId($externalId, $email, $idName);
    }

    private function handleMagicLinkAuthUser(): void
    {
        try {
            /** @var MagicJwtAuthGuard $guard */
            $guard = auth('magicjwtguard');
            $guard->payload();
            $authUser = $guard->user();
        } catch (JWTException $exception) {
            abort(Response::HTTP_UNAUTHORIZED, $exception->getMessage());
        }

        if (!($authUser instanceof User)) {
            abort(Response::HTTP_UNAUTHORIZED, 'Unauthorized');
        }

        if (empty($authUser->id)) {
            $authUser = $this->userService->createOrUpdateUserWithExternalId(
                $authUser->getAttribute('email'),
                $authUser->getAttribute('email'),
                'email'
            );
        }

        if ($authUser === null) {
            abort(Response::HTTP_PRECONDITION_FAILED, 'Could not find existing account in PestRoutes');
        }

        $this->userService->syncUserAccounts($authUser);
        Auth::shouldUse('magicjwtguard');
    }
}
