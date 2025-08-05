<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Responses;

use App\Enums\Resources;
use App\Http\Responses\GetFormsResponse;
use Aptive\PestRoutesSDK\Resources\Forms\Form;
use Tests\TestCase;

final class GetFormsResponseTest extends TestCase
{
    protected GetFormsResponse $response;

    public function setUp(): void
    {
        parent::setUp();

        $this->response = new GetFormsResponse();
    }

    public function test_expected_entity_type(): void
    {
        $this->assertEquals(
            Form::class,
            $this
                ->callProtectedMethod(GetFormsResponse::class, 'getExpectedEntityClass')
                ->invoke($this->response)
        );
    }

    public function test_expected_resource_type(): void
    {
        $this->assertEquals(
            Resources::FORM,
            $this
                ->callProtectedMethod(GetFormsResponse::class, 'getExpectedResourceType')
                ->invoke($this->response)
        );
    }
}
