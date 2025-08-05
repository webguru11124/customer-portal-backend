<?php

namespace Tests\Unit\Actions\Ticket;

use App\Actions\Ticket\ShowCustomersTicketsAction;
use App\DTO\Ticket\SearchTicketsDTO;
use App\Exceptions\PestRoutesRepository\OfficeNotSetException;
use App\Interfaces\Repository\TicketRepository;
use App\Models\Account;
use App\Models\External\TicketModel;
use Illuminate\Support\Collection;
use Mockery;
use Mockery\MockInterface;
use Tests\Data\TicketData;
use Tests\TestCase;
use Tests\Traits\RandomIntTestData;

class ShowCustomersTicketsActionTest extends TestCase
{
    use RandomIntTestData;

    protected MockInterface|TicketRepository $ticketRepositoryMock;
    protected Account $accountModel;
    protected ShowCustomersTicketsAction $subject;

    public function setUp(): void
    {
        parent::setUp();

        $this->accountModel = Account::factory()->makeOne([
            'account_number' => $this->getTestAccountNumber(),
            'office_id' => $this->getTestOfficeId(),
        ]);

        $this->ticketRepositoryMock = Mockery::mock(TicketRepository::class);

        $this->subject = new ShowCustomersTicketsAction($this->ticketRepositoryMock);
    }

    public function test_it_searches_invoices(): void
    {
        $officeId = $this->getTestOfficeId();
        $accountNumber = $this->getTestAccountNumber();
        $dueOnly = true;

        $tickets = TicketData::getTestEntityData(
            3,
            [
                'ticketID' => 1,
                'invoiceDate' => '2022-01-02T16:00:00.000000+0000',
            ],
            [
                'ticketID' => 2,
                'invoiceDate' => '2022-01-03T16:00:00.000000+0000',
            ],
            [
                'ticketID' => 3,
                'invoiceDate' => '2022-01-01T16:00:00.000000+0000',
            ],
        );
        $expectedSpotIds = [2, 1, 3];

        $this->ticketRepositoryMock
            ->shouldReceive('office')
            ->withArgs([$officeId])
            ->andReturnSelf()
            ->once();

        $this->ticketRepositoryMock
            ->shouldReceive('withRelated')
            ->withArgs([['appointment']])
            ->andReturnSelf()
            ->once();

        $this->ticketRepositoryMock
            ->shouldReceive('search')
            ->withArgs(
                fn (SearchTicketsDTO $dto) => $dto->officeId === $officeId
                    && $dto->accountNumber === $accountNumber
                    && $dto->dueOnly === $dueOnly
            )
            ->andReturn($tickets)
            ->once();

        /** @var Collection<int, TicketModel> $result */
        $result = ($this->subject)($officeId, $accountNumber, $dueOnly);
        $resultIds = $result->map(fn (TicketModel $ticketModel) => $ticketModel->id)->toArray();

        self::assertEquals($expectedSpotIds, $resultIds);
    }

    /**
     * @dataProvider repoExceptionsDataProvider
     */
    public function test_it_passes_exceptions(string $exceptionClass): void
    {
        $this->ticketRepositoryMock
            ->shouldReceive('office')
            ->andReturnSelf();
        $this->ticketRepositoryMock
            ->shouldReceive('withRelated')
            ->andReturnSelf();

        $this->ticketRepositoryMock
            ->shouldReceive('search')
            ->andThrow(new $exceptionClass());

        $this->expectException($exceptionClass);

        ($this->subject)(
            $this->getTestOfficeId(),
            $this->getTestAccountNumber(),
            true
        );
    }

    /**
     * @return iterable<int, array<int, string|null>>
     */
    public function repoExceptionsDataProvider(): iterable
    {
        yield [OfficeNotSetException::class];
    }
}
