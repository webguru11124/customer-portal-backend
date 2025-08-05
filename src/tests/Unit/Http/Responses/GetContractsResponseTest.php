<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Responses;

use App\Enums\Resources;
use App\Http\Responses\GetContractsResponse;
use Aptive\PestRoutesSDK\Resources\Contracts\Contract;
use Tests\TestCase;

final class GetContractsResponseTest extends TestCase
{
    protected GetContractsResponse $response;

    public function setUp(): void
    {
        parent::setUp();

        $this->response = new GetContractsResponse();
    }

    public function test_expected_entity_type(): void
    {
        $this->assertEquals(
            Contract::class,
            $this
                ->callProtectedMethod(GetContractsResponse::class, 'getExpectedEntityClass')
                ->invoke($this->response)
        );
    }

    public function test_expected_resource_type(): void
    {
        $this->assertEquals(
            Resources::CONTRACT,
            $this
                ->callProtectedMethod(GetContractsResponse::class, 'getExpectedResourceType')
                ->invoke($this->response)
        );
    }
}
