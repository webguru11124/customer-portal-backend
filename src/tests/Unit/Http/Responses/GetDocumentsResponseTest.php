<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Responses;

use App\Enums\Resources;
use App\Http\Responses\GetDocumentsResponse;
use Aptive\PestRoutesSDK\Resources\Documents\Document;
use Tests\TestCase;

final class GetDocumentsResponseTest extends TestCase
{
    protected GetDocumentsResponse $response;

    public function setUp(): void
    {
        parent::setUp();

        $this->response = new GetDocumentsResponse();
    }

    public function test_expected_entity_type(): void
    {
        $this->assertEquals(
            Document::class,
            $this
                ->callProtectedMethod(GetDocumentsResponse::class, 'getExpectedEntityClass')
                ->invoke($this->response)
        );
    }

    public function test_expected_resource_type(): void
    {
        $this->assertEquals(
            Resources::DOCUMENT,
            $this
                ->callProtectedMethod(GetDocumentsResponse::class, 'getExpectedResourceType')
                ->invoke($this->response)
        );
    }
}
