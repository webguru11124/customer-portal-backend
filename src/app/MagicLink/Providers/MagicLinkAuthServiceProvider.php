<?php

declare(strict_types=1);

namespace App\MagicLink\Providers;

use App\MagicLink\Guards\MagicLinkGuard;
use App\MagicLink\MagicLink;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class MagicLinkAuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Auth::provider('magic_link_eloquent', function (Application $app, array $config) {
            return new MagicLinkAuthEloquentUserProvider($app['hash'], $config['model']);
        });
        Auth::extend('magiclinkdriver', function (Application $app, string $name, array $config) {
            /** @var MagicLinkAuthEloquentUserProvider $provider */
            $provider = $app['auth']->createUserProvider($config['provider']);
            $guard = new MagicLinkGuard(
                new MagicLink(),
                $provider,
                $app['request'],
            );

            $app->refresh('request', $guard, 'setRequest');

            return $guard;
        });
    }
}
