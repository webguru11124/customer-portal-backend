<?php

namespace Tests\Unit\Repositories\PestRoutes\ParametersFactories;

use App\DTO\ServiceType\SearchServiceTypesDTO;
use App\Repositories\PestRoutes\ParametersFactories\SubscriptionParametersFactory;
use Tests\TestCase;
use TypeError;

class SubscriptionParametersFactoryTest extends TestCase
{
    protected SubscriptionParametersFactory $subject;

    public function setUp(): void
    {
        parent::setUp();

        $this->subject = new SubscriptionParametersFactory();
    }

    public function test_create_search_throws_error_if_wrong_search_dto_given(): void
    {
        $this->expectException(TypeError::class);

        $this->subject->createSearch(new SearchServiceTypesDTO());
    }
}
