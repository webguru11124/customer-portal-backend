<?php

namespace Tests\Unit\Repositories\PestRoutes\ParametersFactories;

use App\DTO\ServiceType\SearchServiceTypesDTO;
use App\Repositories\PestRoutes\ParametersFactories\DocumentParametersFactory;
use Tests\TestCase;
use TypeError;

class DocumentParametersFactoryTest extends TestCase
{
    protected DocumentParametersFactory $subject;

    public function setUp(): void
    {
        parent::setUp();

        $this->subject = new DocumentParametersFactory();
    }

    public function test_create_search_throws_error_if_wrong_search_dto_given(): void
    {
        $this->expectException(TypeError::class);

        $this->subject->createSearch(new SearchServiceTypesDTO());
    }
}
