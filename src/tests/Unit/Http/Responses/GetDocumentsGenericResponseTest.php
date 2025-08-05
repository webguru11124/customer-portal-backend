<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Responses;

use App\Enums\Resources;
use App\Http\Responses\GetDocumentsGenericResponse;
use Aptive\PestRoutesSDK\Resources\Documents\Document;
use Tests\TestCase;

final class GetDocumentsGenericResponseTest extends TestCase
{
    protected GetDocumentsGenericResponse $response;

    public function setUp(): void
    {
        parent::setUp();

        $this->response = new GetDocumentsGenericResponse();
    }

    public function test_expected_entity_type(): void
    {
        $this->assertEquals(
            Document::class,
            $this
                ->callProtectedMethod(GetDocumentsGenericResponse::class, 'getExpectedEntityClass')
                ->invoke($this->response)
        );
    }

    public function test_expected_resource_type(): void
    {
        $this->assertEquals(
            Resources::DOCUMENT,
            $this
                ->callProtectedMethod(GetDocumentsGenericResponse::class, 'getExpectedResourceType')
                ->invoke($this->response)
        );
    }
}
