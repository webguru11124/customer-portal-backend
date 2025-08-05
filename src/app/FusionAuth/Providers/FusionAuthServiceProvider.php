<?php

declare(strict_types=1);

namespace App\FusionAuth\Providers;

use App\FusionAuth\Claims\Audience;
use App\FusionAuth\Claims\Email;
use App\FusionAuth\Claims\EmailVerified;
use App\FusionAuth\Claims\Issuer;
use App\FusionAuth\FusionAuthJwtGuard;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Tymon\JWTAuth\Http\Parser\Cookies;

class FusionAuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Auth::provider('fusionauth_eloquent', function (Application $app, array $config) {
            return new FusionAuthEloquentUserProvider($app['hash'], $config['model']);
        });
        Auth::extend('fusionjwt', function (Application $app, string $name, array $config) {
            /** @var FusionAuthEloquentUserProvider $provider */
            $provider = $app['auth']->createUserProvider($config['provider']);
            $guard = new FusionAuthJwtGuard(
                $app['tymon.jwt'],
                $provider,
                $app['request'],
            );

            $app->refresh('request', $guard, 'setRequest');

            return $guard;
        });

        /** @var \Tymon\JWTAuth\Claims\Factory $factory */
        $factory = $this->app->get('tymon.jwt.claim.factory');
        $factory->extend('iss', Issuer::class);
        $factory->extend('aud', Audience::class);
        $factory->extend('email', Email::class);
        $factory->extend('email_verified', EmailVerified::class);

        /** @var \Tymon\JWTAuth\Http\Parser\Parser $parsers */
        $parsers = $this->app->get('tymon.jwt.parser');
        foreach ($parsers->getChain() as $parser) {
            if ($parser instanceof Cookies) {
                $parser->setKey('app_at');
                break;
            }
        }
    }
}
