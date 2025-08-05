<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use InfluxDB2\Model\WritePrecision;
use InfluxDB2\Client;
use Illuminate\Contracts\Config\Repository;

class InfluxDBServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $config = $this->app->make(Repository::class);

        $this->app->bind(
            Client::class,
            function () use ($config) {
                $clientData = [
                    "url" => $config->get('influxdb.connection.host'),
                    "token" => $config->get('influxdb.connection.token'),
                    "bucket" => $config->get('influxdb.connection.bucket'),
                    "org" => $config->get('influxdb.connection.organization'),
                    "precision" => WritePrecision::S,
                    "tags" => [
                        "environment" => $config->get('app.env')
                    ]
                ];
                return new Client($clientData);
            }
        );
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
