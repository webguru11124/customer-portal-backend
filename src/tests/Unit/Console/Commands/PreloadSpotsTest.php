<?php

namespace Tests\Unit\Console\Commands;

use App\Actions\Spot\ShowAvailableSpotsAction;
use App\Models\Account;
use App\Services\AccountService;
use Illuminate\Support\Collection;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Traits\RandomIntTestData;

class PreloadSpotsTest extends TestCase
{
    use RandomIntTestData;

    protected MockInterface|AccountService $accountServiceMock;
    protected MockInterface|ShowAvailableSpotsAction $showAvailableSpotsActionMock;

    protected Account $account;
    protected $dateStart = '2022-02-24';
    protected $dateEnd = '2023-04-30';

    public function setUp(): void
    {
        parent::setUp();

        $this->accountServiceMock = Mockery::mock(AccountService::class);
        $this->instance(AccountService::class, $this->accountServiceMock);

        $this->showAvailableSpotsActionMock = Mockery::mock(ShowAvailableSpotsAction::class);
        $this->instance(ShowAvailableSpotsAction::class, $this->showAvailableSpotsActionMock);

        $this->account = Account::factory()->make([
            'office_id' => $this->getTestOfficeId(),
            'account_number' => $this->getTestAccountNumber(),
        ]);
    }

    public function test_it_triggers_a_method_that_is_used_to_load_spots(): void
    {
        $this->accountServiceMock
            ->shouldReceive('getAccountByAccountNumber')
            ->with($this->account->account_number)
            ->once()
            ->andReturn($this->account);

        $this->showAvailableSpotsActionMock
            ->shouldReceive('__invoke')
            ->with(
                $this->account->office_id,
                $this->account->account_number,
                $this->dateStart,
                $this->dateEnd
            )->once()
            ->andReturn(new Collection());

        $this->artisan('preload:spots', [
            'accountNumber' => $this->account->account_number,
            'dateStart' => $this->dateStart,
            'dateEnd' => $this->dateEnd,
        ])->assertSuccessful();
    }
}
