<?php

declare(strict_types=1);

namespace App\MagicLink\Providers;

use App\MagicLink\Guards\MagicJwtAuthGuard;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class MagicJwtAuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Auth::provider('magic_link_eloquent', function (Application $app, array $config) {
            return new MagicLinkAuthEloquentUserProvider($app['hash'], $config['model']);
        });
        Auth::extend('magicjwtdriver', function (Application $app, string $name, array $config) {
            /** @var MagicLinkAuthEloquentUserProvider $provider */
            $provider = $app['auth']->createUserProvider($config['provider']);
            $guard = new MagicJwtAuthGuard(
                $app['tymon.jwt'],
                $provider,
                $app['request'],
            );

            $app->refresh('request', $guard, 'setRequest');

            return $guard;
        });
    }
}
