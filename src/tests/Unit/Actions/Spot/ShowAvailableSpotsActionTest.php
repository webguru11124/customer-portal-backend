<?php

namespace Tests\Unit\Actions\Spot;

use App\Actions\Spot\ShowAvailableSpotsAction;
use App\DTO\Route\SearchRoutesDTO;
use App\DTO\Spot\SearchSpotsDTO;
use App\Exceptions\Entity\EntityNotFoundException;
use App\Helpers\ConfigHelper;
use App\Interfaces\Repository\CustomerRepository;
use App\Interfaces\Repository\RouteRepository;
use App\Interfaces\Repository\SpotRepository;
use App\Models\Account;
use App\Models\External\CustomerModel;
use App\Models\External\SpotModel;
use Illuminate\Support\Collection;
use Mockery;
use Mockery\MockInterface;
use Tests\Data\CustomerData;
use Tests\Data\RouteData;
use Tests\Data\SpotData;
use Tests\TestCase;
use Tests\Traits\RandomIntTestData;

class ShowAvailableSpotsActionTest extends TestCase
{
    use RandomIntTestData;

    protected MockInterface|SpotRepository $spotRepositoryMock;
    protected MockInterface|CustomerRepository $customerRepositoryMock;
    protected MockInterface|RouteRepository $routeRepositoryMock;

    protected Account $accountModel;
    protected ShowAvailableSpotsAction $subject;

    public function setUp(): void
    {
        parent::setUp();

        $this->accountModel = Account::factory()->makeOne([
            'account_number' => $this->getTestAccountNumber(),
            'office_id' => $this->getTestOfficeId(),
        ]);

        $this->spotRepositoryMock = Mockery::mock(SpotRepository::class);
        $this->customerRepositoryMock = Mockery::mock(CustomerRepository::class);
        $this->routeRepositoryMock = Mockery::mock(RouteRepository::class);

        $this->subject = new ShowAvailableSpotsAction(
            $this->spotRepositoryMock,
            $this->customerRepositoryMock,
            $this->routeRepositoryMock
        );
    }

    public function test_it_searches_available_spots(): void
    {
        $dateStart = '2022-02-24';
        $dateEnd = '2023-02-24';
        $officeId = $this->getTestOfficeId();
        $accountNumber = $this->getTestAccountNumber();

        $routes = RouteData::getTestEntityData(
            4,
            [
                'routeID' => 1,
                'officeID' => $officeId,
                'groupTitle' => 'Regular Routes',
                'groupID' => 0,
                'title' => 'Regular Routes',
            ],
            [
                'routeID' => 2,
                'officeID' => $officeId,
                'groupTitle' => 'Some other Routes',
                'groupID' => 0,
                'title' => 'Some other Routes',
            ],
            [
                'routeID' => 3,
                'officeID' => $officeId,
                'groupTitle' => 'Initial Routes',
                'groupID' => 0,
                'title' => 'Regular Routes',
            ],
            [
                'routeID' => 4,
                'officeID' => $officeId,
                'groupTitle' => 'Regular Routes',
                'groupID' => 0,
                'title' => 'INITIAL Routes',
            ],
        );

        $regularRoutesIDs = [1, 2];

        $spots = SpotData::getTestEntityData(
            3,
            [
                'spotID' => 1,
                'date' => '2022-02-24',
                'start' => '08:00:00',
            ],
            [
                'spotID' => 2,
                'date' => '2022-02-24',
                'start' => '09:00:00',
            ],
            [
                'spotID' => 3,
                'date' => '2022-02-24',
                'start' => '16:00:00',
            ],
        );

        $expectedSpotIds = [1, 3];

        /** @var CustomerModel $customer */
        $customer = CustomerData::getTestEntityData(
            1,
            [
                'officeID' => $officeId,
                'customerID' => $accountNumber,
            ]
        )->first();

        $this->setupRouteRepositoryMockToReturnRoutes($customer, $officeId, $dateStart, $dateEnd, $routes);
        $this->setupCustomerRepositoryMockToReturnValidCustomer($customer, $officeId, $accountNumber);

        $this->spotRepositoryMock
            ->shouldReceive('office')
            ->withArgs([$officeId])
            ->andReturn($this->spotRepositoryMock)
            ->once();

        $this->spotRepositoryMock
            ->shouldReceive('search')
            ->withArgs(
                fn (SearchSpotsDTO $dto) => $dto->officeId === $officeId
                && $dto->dateStart === $dateStart
                && $dto->dateEnd === $dateEnd
                && $dto->latitude === $customer->latitude
                && $dto->longitude === $customer->longitude
                && $dto->maxDistance === ConfigHelper::getSpotsMaxDistance()
                && $dto->routeIds === $regularRoutesIDs
            )
            ->andReturn($spots)
            ->once();

        /** @var Collection<int, SpotModel> $result */
        $result = ($this->subject)($officeId, $accountNumber, $dateStart, $dateEnd);
        $resultIds = $result->map(fn (SpotModel $spotModel) => $spotModel->id)->toArray();

        self::assertEquals($expectedSpotIds, $resultIds);
    }

    public function test_it_skips_spots_search_when_no_regular_routes_found(): void
    {
        $dateStart = '2022-02-24';
        $dateEnd = '2023-02-24';
        $officeId = $this->getTestOfficeId();
        $accountNumber = $this->getTestAccountNumber();

        $routes = RouteData::getTestEntityData(
            1,
            [
                'routeID' => 4,
                'officeID' => $officeId,
                'groupTitle' => 'Regular Routes',
                'groupID' => 0,
                'title' => 'INITIAL Routes',
            ],
        );

        /** @var CustomerModel $customer */
        $customer = CustomerData::getTestEntityData(
            1,
            [
                'officeID' => $officeId,
                'customerID' => $accountNumber,
            ]
        )->first();

        $this->setupRouteRepositoryMockToReturnRoutes($customer, $officeId, $dateStart, $dateEnd, $routes);

        $this->setupCustomerRepositoryMockToReturnValidCustomer($customer, $officeId, $accountNumber);

        $this->spotRepositoryMock
            ->shouldReceive('office')
            ->never();

        $this->spotRepositoryMock
            ->shouldReceive('search')
            ->never();

        /** @var Collection<int, SpotModel> $result */
        $result = ($this->subject)($officeId, $accountNumber, $dateStart, $dateEnd);

        self::assertEquals(new Collection([]), $result);
    }

    /**
     * @dataProvider repoExceptionsDataProvider
     */
    public function test_it_passes_exceptions(string $exceptionClass): void
    {
        $this->customerRepositoryMock
            ->shouldReceive('office')
            ->andReturn($this->customerRepositoryMock);

        $this->customerRepositoryMock
            ->shouldReceive('find')
            ->andThrow(new $exceptionClass());

        $this->expectException($exceptionClass);

        ($this->subject)(
            $this->getTestOfficeId(),
            $this->getTestAccountNumber(),
            '2023-01-01',
            '2023-21-01'
        );
    }

    /**
     * @return iterable<int, array<int, string|null>>
     */
    public function repoExceptionsDataProvider(): iterable
    {
        yield [EntityNotFoundException::class];
    }

    protected function setupCustomerRepositoryMockToReturnValidCustomer(
        CustomerModel $customer,
        int $officeId,
        int $accountNumber
    ): void {
        $this->customerRepositoryMock
            ->shouldReceive('office')
            ->withArgs([$officeId])
            ->andReturn($this->customerRepositoryMock)
            ->once();

        $this->customerRepositoryMock
            ->shouldReceive('find')
            ->withArgs([$accountNumber])
            ->andReturn($customer)
            ->once();
    }

    protected function setupRouteRepositoryMockToReturnRoutes(
        CustomerModel $customer,
        int $officeId,
        string $dateStart,
        string $dateEnd,
        Collection $routes
    ): void {
        $this->routeRepositoryMock
            ->shouldReceive('office')
            ->with($customer->officeId)
            ->once()
            ->andReturnSelf();

        $this->routeRepositoryMock
            ->shouldReceive('search')
            ->withArgs(
                fn (SearchRoutesDTO $dto) => $dto->officeId === $officeId
                    && $dto->dateStart === $dateStart
                    && $dto->dateEnd === $dateEnd
                    && $dto->latitude === $customer->latitude
                    && $dto->longitude === $customer->longitude
                    && $dto->maxDistance === ConfigHelper::getSpotsMaxDistance()
            )->once()
            ->andReturn($routes);
    }
}
