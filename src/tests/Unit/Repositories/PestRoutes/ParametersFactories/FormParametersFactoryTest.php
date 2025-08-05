<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories\PestRoutes\ParametersFactories;

use App\DTO\Form\SearchFormsDTO;
use App\DTO\ServiceType\SearchServiceTypesDTO;
use App\Repositories\PestRoutes\ParametersFactories\FormParametersFactory;
use Aptive\PestRoutesSDK\Resources\Forms\Params\SearchFormsParams;
use Tests\TestCase;
use Tests\Traits\RandomIntTestData;
use TypeError;

class FormParametersFactoryTest extends TestCase
{
    use RandomIntTestData;

    protected FormParametersFactory $testClass;

    public function setUp(): void
    {
        parent::setUp();

        $this->testClass = new FormParametersFactory();
    }

    public function test_create_search_throws_error_if_wrong_search_dto_given(): void
    {
        $this->expectException(TypeError::class);

        $this->testClass->createSearch(new SearchServiceTypesDTO());
    }

    public function test_it_create_search_parameters_correctly(): void
    {
        $officeId = $this->getTestOfficeId();
        $customerId = $this->getTestAccountNumber();

        $this->assertEquals(
            new SearchFormsParams(
                officeIds: [$officeId],
                customerId: $customerId,
                includeDocumentLink: true,
            ),
            $this->testClass->createSearch(new SearchFormsDTO(officeId: $officeId, accountNumber: $customerId))
        );
    }
}
