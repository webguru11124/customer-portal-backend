<?php

declare(strict_types=1);

namespace Tests\Unit\Http\API\V2\Controller;

use App\Actions\Spot\ShowAvailableSpotsAction;
use App\Actions\Spot\ShowSpotsFromFlexIVRAction;
use App\DTO\FlexIVR\Spot\Spot;
use App\Exceptions\Account\AccountNotFoundException;
use App\Exceptions\Entity\EntityNotFoundException;
use Aptive\Component\Http\HttpStatus;
use GuzzleHttp\Exception\InvalidArgumentException;
use JsonException;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\MockObject\Exception;
use RuntimeException;
use Tests\Traits\ExpectedV2ResponseData;
use Tests\Traits\RandomIntTestData;
use Tests\Traits\TestAuthorizationMiddleware;
use Tests\Unit\Http\API\V1\Controller\ApiTestCase;
use Throwable;

final class SpotControllerTest extends ApiTestCase
{
    use ExpectedV2ResponseData;
    use RandomIntTestData;
    use TestAuthorizationMiddleware;

    public ShowAvailableSpotsAction|MockInterface $showAvailableSpotsActionMock;

    public function setUp(): void
    {
        parent::setUp();

        $this->showAvailableSpotsActionMock = Mockery::mock(ShowAvailableSpotsAction::class);
        $this->instance(ShowAvailableSpotsAction::class, $this->showAvailableSpotsActionMock);
    }

    public function test_search_requires_authentication(): void
    {
        $this->checkAuthorizationMiddleware(
            fn () => $this->getJson($this->getSearchRoute())
        );
    }

    public function test_search_requires_authorization(): void
    {
        $this->createAndLogInAuth0User();

        $this->getJson($this->getSearchRoute())
            ->assertNotFound();
    }

    /**
     * @param array<string, scalar|scalar[]> $queryParams
     *
     * @return string
     */
    private function getSearchRoute(): string
    {
        return route('api.v2.customer.spots.get', ['accountNumber' => $this->getTestAccountNumber()]);
    }

    /**
     * @throws Exception when random_int fails
     */
    public function test_search_returns_available_spots(): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $spotData = [
            'spotID' => $this->getTestSpotId(),
            'date' => date('Y-m-d'),
            'window' => random_int(0, 1) === 1 ? 'AM' : 'PM',
            'isAroSpot' => random_int(0, 1) === 1,
        ];
        $spot = Spot::fromApiResponse((object) $spotData);

        $actionMock = $this->createMock(ShowSpotsFromFlexIVRAction::class);
        $actionMock
            ->expects(self::once())
            ->method('__invoke')
            ->with($this->getTestAccountNumber())
            ->willReturn([$spot]);

        $this->instance(ShowSpotsFromFlexIVRAction::class, $actionMock);

        $response = $this->getJson($this->getSearchRoute());

        $response->assertOk()->assertExactJson(['data' => [[
            'id' => (string) $this->getTestSpotId(),
            'type' => 'Spot',
            'attributes' => [
                'date' => $spotData['date'],
                'is_aro_spot' => $spotData['isAroSpot'],
                'window' => $spotData['window'],
            ],
        ]]]);
    }

    /**
     * @dataProvider spotSearchExceptionProvider
     */
    public function test_search_returns_proper_error_on_exception(Throwable $exception, int $expectedStatusCode): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $actionMock = $this->createMock(ShowSpotsFromFlexIVRAction::class);
        $actionMock
            ->expects(self::once())
            ->method('__invoke')
            ->with($this->getTestAccountNumber())
            ->willThrowException($exception);

        $this->instance(ShowSpotsFromFlexIVRAction::class, $actionMock);

        $response = $this->getJson($this->getSearchRoute());

        $response->assertStatus($expectedStatusCode);
    }

    public static function spotSearchExceptionProvider(): iterable
    {
        yield 'Account not found' => [
            new AccountNotFoundException(),
            HttpStatus::NOT_FOUND,
        ];
        yield 'Entity not found' => [
            new EntityNotFoundException(),
            HttpStatus::NOT_FOUND,
        ];
        yield 'Guzzle exception' => [
            new InvalidArgumentException(),
            HttpStatus::INTERNAL_SERVER_ERROR,
        ];
        yield 'JSON exception' => [
            new JsonException(),
            HttpStatus::INTERNAL_SERVER_ERROR,
        ];
        yield 'Unexpected exception' => [
            new RuntimeException(),
            HttpStatus::INTERNAL_SERVER_ERROR,
        ];
    }
}
