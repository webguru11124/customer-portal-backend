<?php

declare(strict_types=1);

namespace App\Providers\PestRoutes;

use Aptive\PestRoutesSDK\CredentialsRepository;
use Aptive\PestRoutesSDK\DynamoDbCredentialsRepository;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;

final class CredentialsRepositoryProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            CredentialsRepository::class,
            fn () => $this->configureDynamoDbCredentialsRepository()
        );
    }

    private function configureDynamoDbCredentialsRepository(): CredentialsRepository
    {
        return new DynamoDbCredentialsRepository(
            Config::get('pestroutes.auth.dynamodb.table'),
            Config::get('pestroutes.auth.dynamodb.region'),
        );
    }
}
