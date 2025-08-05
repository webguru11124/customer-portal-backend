<?php

declare(strict_types=1);

namespace App\Providers\Worldpay;

use Aptive\Worldpay\CredentialsRepository\CredentialsRepository;
use Aptive\Worldpay\CredentialsRepository\DynamoDbCredentialsRepository;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;

final class CredentialsRepositoryProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CredentialsRepository::class, function () {
            return new DynamoDbCredentialsRepository(
                Config::get('worldpay.auth.dynamodb.table'),
                Config::get('worldpay.auth.dynamodb.region'),
            );
        });
    }
}
