<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories\PestRoutes;

use App\DTO\Office\SearchOfficesDTO;
use App\Repositories\PestRoutes\AbstractPestRoutesRepository;
use Aptive\PestRoutesSDK\Client;
use Aptive\PestRoutesSDK\Collection;
use Aptive\PestRoutesSDK\Resources\Offices\OfficesResource;
use Tests\TestCase;
use Tests\Traits\RandomIntTestData;

abstract class GenericRepositoryWithoutSearchTest extends TestCase
{
    use RandomIntTestData;

    abstract protected function getSubject(): AbstractPestRoutesRepository;

    public function test_it_finds_many(): void
    {
        $ids = [
            $this->getTestSubscriptionFlagId(),
        ];

        $result = $this->getSubject()
            ->office($this->getTestOfficeId())
            ->findMany(...$ids);

        $this->assertCount(0, $result);
    }

    public function test_it_searches_generic_flags_assignments(): void
    {
        $officeResource = \Mockery::mock(OfficesResource::class);
        $officeResource
            ->shouldReceive('includeData')
            ->andReturn($officeResource);
        $officeResource
            ->shouldReceive('search')
            ->andReturn($officeResource);
        $officeResource
            ->shouldReceive('all')
            ->andReturn(new Collection());

        $pestRoutesClientMock = \Mockery::mock(Client::class);
        $pestRoutesClientMock
            ->shouldReceive('office')
            ->withArgs([$this->getTestOfficeId()])
            ->andReturn($officeResource);

        $this->getSubject()->setPestRoutesClient($pestRoutesClientMock);

        $result = $this->getSubject()
            ->office($this->getTestOfficeId())
            ->search(new SearchOfficesDTO([$this->getTestOfficeId()]));

        $this->assertEquals((new Collection())->count(), $result->count());
    }
}
