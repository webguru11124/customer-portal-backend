<?php

declare(strict_types=1);

namespace Tests\Unit\Http\API\V1\Controller;

use App\Actions\Ticket\ShowCustomersTicketsAction;
use App\Exceptions\Entity\EntityNotFoundException;
use App\Models\Account;
use App\Models\External\TicketModel;
use App\Services\AccountService;
use Aptive\Component\JsonApi\Exceptions\ValidationException;
use Illuminate\Testing\Fluent\AssertableJson;
use Mockery;
use Mockery\MockInterface;
use Symfony\Component\HttpFoundation\Response;
use Tests\Data\TicketData;
use Tests\Traits\RandomIntTestData;
use Tests\Traits\TestAuthorizationMiddleware;

class TicketControllerTest extends ApiTestCase
{
    use RandomIntTestData;
    use TestAuthorizationMiddleware;

    private const SEARCH_ROUTE_NAME = 'api.customer.invoices.get';

    public AccountService|MockInterface $accountServiceMock;
    public Account $account;

    public function setUp(): void
    {
        parent::setUp();

        $this->showCustomersTicketsAction = Mockery::mock(ShowCustomersTicketsAction::class);
        $this->instance(ShowCustomersTicketsAction::class, $this->showCustomersTicketsAction);

        $this->account = Account::factory()->make([
            'office_id' => $this->getTestOfficeId(),
            'account_number' => $this->getTestAccountNumber(),
        ]);
    }

    public function test_get_tickets_requires_authentication(): void
    {
        $this->checkAuthorizationMiddleware(
            fn () => $this->getJson($this->getTicketsRoute($this->getTestAccountNumber()))
        );
    }

    public function test_get_tickets_requires_authorization(): void
    {
        $this->createAndLogInAuth0User();

        $this->getJson($this->getTicketsRoute($this->getTestAccountNumber()))
            ->assertNotFound();
    }

    public function getTicketsParamProvider(): iterable
    {
        yield 'dueOnly not set' => [null];
        yield 'dueOnly false' => [false];
        yield 'dueOnly true' => [true];
    }

    /**
     * @dataProvider getTicketsParamProvider
     */
    public function test_get_tickets_returns_tickets(bool|null $dueOnly): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $tickets = TicketData::getTestEntityData(2);
        $tickets->each(
            fn (TicketModel $ticket) => $ticket->setRelated('appointment', null)
        );

        $this
            ->showCustomersTicketsAction
            ->expects('__invoke')
            ->withArgs([
                $this->account->office_id,
                $this->account->account_number,
                (bool) $dueOnly,
            ])
            ->once()
            ->andReturn($tickets);

        $this
            ->getJson($this->getTicketsRoute($this->getTestAccountNumber(), $dueOnly))
            ->assertOk()
            ->assertJson(fn (AssertableJson $json) => $json->has('data')->has('links')->etc())
            ->assertJsonCount(2, 'data');
    }

    public function test_get_tickets_returns_validation_error(): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $this
            ->getJson($this->getTicketsRoute())
            ->assertNotFound();
    }

    /**
     * @dataProvider exceptionsDataProvider
     */
    public function test_search_passes_exceptions(string $exceptionClass, int $expectedStatus): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $this->showCustomersTicketsAction
            ->shouldReceive('__invoke')
            ->andThrow(new $exceptionClass());

        $this->getJson($this->getTicketsRoute($this->getTestAccountNumber()))
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

    protected function getTicketsRoute(int $accountNumber = 0, ?bool $dueOnly = null): string
    {
        $routeParams = ['accountNumber' => $accountNumber];

        if ($dueOnly !== null) {
            $routeParams['dueOnly'] = $dueOnly;
        }

        return route(self::SEARCH_ROUTE_NAME, $routeParams);
    }
}
