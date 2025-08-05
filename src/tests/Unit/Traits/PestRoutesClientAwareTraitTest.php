<?php

declare(strict_types=1);

namespace Tests\Unit\Traits;

use App\Services\PestRoutesClientAwareTrait;
use Aptive\PestRoutesSDK\Client;
use Tests\TestCase;

final class PestRoutesClientAwareTraitTest extends TestCase
{
    public function test_get_pestroutes_client(): void
    {
        $traitObject = $this->getObjectWithClient();

        $pestRoutesClientMock = $this->createMock(Client::class);
        $this->instance(Client::class, $pestRoutesClientMock);

        $this->assertSame($pestRoutesClientMock, $traitObject->getPestRoutesClient());
    }

    public function test_it_sets_valid_pest_routes_client(): void
    {
        $pestRoutesClientMock = $this->createMock(Client::class);

        $traitObject = $this->getObjectWithClient();
        $traitObject->setPestRoutesClient($pestRoutesClientMock);

        self::assertSame($pestRoutesClientMock, $traitObject->getPestRoutesClient());
    }

    private function getObjectWithClient(): object
    {
        return new class () {
            use PestRoutesClientAwareTrait;
        };
    }
}
