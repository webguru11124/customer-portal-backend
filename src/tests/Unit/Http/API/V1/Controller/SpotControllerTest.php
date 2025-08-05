<?php

declare(strict_types=1);

namespace Tests\Unit\Http\API\V1\Controller;

use App\Actions\Spot\ShowAvailableSpotsAction;
use App\Exceptions\Entity\EntityNotFoundException;
use Aptive\Component\JsonApi\Exceptions\ValidationException;
use Illuminate\Testing\Fluent\AssertableJson;
use Mockery;
use Mockery\MockInterface;
use Symfony\Component\HttpFoundation\Response;
use Tests\Data\SpotData;
use Tests\Traits\ExpectedV1ResponseData;
use Tests\Traits\TestAuthorizationMiddleware;

class SpotControllerTest extends ApiTestCase
{
    use ExpectedV1ResponseData;
    use TestAuthorizationMiddleware;

    private const DATE_START = '2022-08-01';
    private const DATE_END = '2022-09-30';

    public string $searchRoute;

    public ShowAvailableSpotsAction|MockInterface $showAvailableSpotsActionMock;
    public MockInterface $customerServiceMock;
    public array $validRequest = [
        'date_start' => self::DATE_START,
        'date_end' => self::DATE_END,
    ];

    public function setUp(): void
    {
        parent::setUp();

        $this->searchRoute = route(
            'api.customer.spots',
            ['accountNumber' => $this->getTestAccountNumber()]
        );

        $this->showAvailableSpotsActionMock = Mockery::mock(ShowAvailableSpotsAction::class);
        $this->instance(ShowAvailableSpotsAction::class, $this->showAvailableSpotsActionMock);
    }

    public function test_search_requires_authentication(): void
    {
        $this->checkAuthorizationMiddleware(
            fn () => $this->postJson($this->searchRoute, $this->validRequest)
        );
    }

    public function test_search_requires_authorization(): void
    {
        $this->createAndLogInAuth0User();

        $this->postJson($this->searchRoute, $this->validRequest)
            ->assertNotFound();
    }

    public function test_search_valid_request(): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $startTimeAM = '08:00:00';
        $startTimePM = '16:00:00';

        $spotsCollection = SpotData::getTestEntityData(
            2,
            [
                'date' => self::DATE_START,
                'start' => $startTimeAM,
            ],
            [
                'date' => self::DATE_START,
                'start' => $startTimePM,
            ],
        );

        $this->showAvailableSpotsActionMock
            ->shouldReceive('__invoke')
            ->withArgs([
                $this->getTestOfficeId(),
                $this->getTestAccountNumber(),
                self::DATE_START,
                self::DATE_END,
            ])
            ->andReturn($spotsCollection)
            ->once();

        $this->postJson($this->searchRoute, $this->validRequest)
            ->assertOk()
            ->assertJson(
                fn (AssertableJson $json) => $json
                    ->where('links.self', sprintf('/api/v1/customer/%s/spots', $this->getTestAccountNumber()))
                    ->where('data.0.type', 'Spot')
                    ->where('data.0.id', (string) $spotsCollection->get(0)->id)
                    ->where('data.0.attributes.date', self::DATE_START)
                    ->where('data.0.attributes.time', 'AM')
                    ->where('data.1.type', 'Spot')
                    ->where('data.1.id', (string) $spotsCollection->get(1)->id)
                    ->where('data.1.attributes.date', self::DATE_START)
                    ->where('data.1.attributes.time', 'PM')
            );
    }

    /**
     * @dataProvider invalidSearchDataProvider
     */
    public function test_search_returns_validation_error($requestData): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $response = $this->postJson($this->searchRoute, $requestData);

        self::assertStringContainsString('error', $response->getContent());
        $response->assertUnprocessable();
    }

    public function invalidSearchDataProvider(): array
    {
        return [
            [
                [
                    'date_start' => '2022/09/23',
                    'date_end' => self::DATE_END,
                ],
            ],
            [
                [
                    'date_start' => self::DATE_START,
                    'date_end' => '2022-09-111',
                ],
            ],
            [
                [
                    'date_start' => '2022/08/23',
                    'date_end' => '2022-09-333',
                ],
            ],
            [
                [
                    'date_start' => self::DATE_END,
                    'date_end' => self::DATE_START,
                ],
            ],
            [[]],
        ];
    }

    /**
     * @dataProvider exceptionsDataProvider
     */
    public function test_search_passes_exceptions(string $exceptionClass, int $expectedStatus): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $this->showAvailableSpotsActionMock
            ->shouldReceive('__invoke')
            ->andThrow(new $exceptionClass());

        $this->postJson($this->searchRoute, $this->validRequest)
            ->assertStatus($expectedStatus)
            ->assertJsonStructure(['errors']);
    }

    /**
     * @return iterable<int, array<int, mixed>>
     */
    public function exceptionsDataProvider(): iterable
    {
        yield [ValidationException::class, Response::HTTP_UNPROCESSABLE_ENTITY];
        yield [EntityNotFoundException::class, Response::HTTP_NOT_FOUND];
    }
}
