<?php

namespace App\Providers;

use App\Logging\CPLogManager;
use App\Models\TransactionSetup;
use App\Observers\TransactionSetupObserver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Psr\Log\LoggerInterface;
use Twilio\Rest\Client;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(Client::class, function () {
            $sid = env('TWILIO_ACCOUNT_SID');
            $apiKey = env('TWILIO_API_KEY');
            $apiSecret = env('TWILIO_API_SECRET');

            return new Client($apiKey, $apiSecret, $sid);
        });
        $app = $this->app;
        $this->app->bind(LoggerInterface::class, function () use ($app) {
            return new CPLogManager($app);
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        TransactionSetup::observe(TransactionSetupObserver::class);

        if (env('APP_DEBUG')) {
            // Add in boot function
            DB::listen(function ($query) {
                Log::info(
                    $query->sql,
                    [
                        'bindings' => $query->bindings,
                        'time' => $query->time,
                    ]
                );
            });
        }
    }
}
