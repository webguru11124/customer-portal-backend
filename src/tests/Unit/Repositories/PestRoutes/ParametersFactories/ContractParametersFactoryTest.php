<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories\PestRoutes\ParametersFactories;

use App\DTO\Contract\SearchContractsDTO;
use App\DTO\ServiceType\SearchServiceTypesDTO;
use App\Repositories\PestRoutes\ParametersFactories\ContractParametersFactory;
use Aptive\PestRoutesSDK\Resources\Contracts\Params\SearchContractsParams;
use Tests\TestCase;
use Tests\Traits\RandomIntTestData;
use TypeError;

class ContractParametersFactoryTest extends TestCase
{
    use RandomIntTestData;

    protected ContractParametersFactory $testClass;

    public function setUp(): void
    {
        parent::setUp();

        $this->testClass = new ContractParametersFactory();
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
            new SearchContractsParams(
                officeIds: [$officeId],
                customerIds: [$customerId],
                includeDocumentLink: true,
            ),
            $this->testClass->createSearch(new SearchContractsDTO(officeId: $officeId, accountNumbers: [$customerId]))
        );
    }
}
