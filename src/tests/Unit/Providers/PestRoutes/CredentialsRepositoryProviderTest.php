<?php

declare(strict_types=1);

namespace Tests\Unit\Providers\PestRoutes;

use App\Providers\PestRoutes\CredentialsRepositoryProvider;
use Aptive\PestRoutesSDK\CredentialsRepository;
use Aptive\PestRoutesSDK\DynamoDbCredentialsRepository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

final class CredentialsRepositoryProviderTest extends TestCase
{
    public function test_register_creates_dynamodb_repository(): void
    {
        Config::expects('get')
            ->with('pestroutes.auth.dynamodb.table')
            ->once()
            ->andReturn('T');
        Config::expects('get')
            ->with('pestroutes.auth.dynamodb.region')
            ->once()
            ->andReturn('eu-north-1');

        $appMock = $this->createMock(Application::class);
        $appMock
            ->expects(self::once())
            ->method('bind')
            ->with(
                CredentialsRepository::class,
                self::callback(
                    fn ($callback) => $callback() instanceof DynamoDbCredentialsRepository
                )
            );

        $provider = new CredentialsRepositoryProvider($appMock);

        $provider->register();
    }
}
