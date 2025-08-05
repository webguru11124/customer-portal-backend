<?php

namespace App\Providers;

use App\Http\Middleware\Authorize;
use App\Http\Middleware\EnsureValidAccountNumber;
use App\Http\Middleware\Auth0OrFusion;
use App\Http\Middleware\MagicLink;
use App\Http\Middleware\MagicToken;
use App\Http\Middleware\HandleCustomerSession;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Routing\Router;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->registerPolicies();

        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('aptive.authorize', Authorize::class);
        $router->aliasMiddleware('aptive.valid_account_number', EnsureValidAccountNumber::class);
        $router->aliasMiddleware('aptive.customer_session', HandleCustomerSession::class);
        $router->aliasMiddleware('aptive.fusion_optional', Auth0OrFusion::class);
        $router->aliasMiddleware('aptive.magiclink_optional', MagicLink::class);
        $router->aliasMiddleware('aptive.magiclink', MagicToken::class);
    }
}
