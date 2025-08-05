<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Spot;

use App\Actions\Spot\ShowSpotsFromFlexIVRAction;
use App\Events\Spot\SpotSearched;
use App\Interfaces\Repository\CustomerRepository;
use App\Models\Account;
use App\Repositories\FlexIVR\SpotRepository;
use App\Services\AccountService;
use Illuminate\Support\Facades\Event;
use Tests\Data\CustomerData;
use Tests\TestCase;
use Tests\Traits\RandomIntTestData;

final class ShowSpotsFromFlexIVRActionTest extends TestCase
{
    use RandomIntTestData;

    public function test_searching_spots(): void
    {
        $account = Account::factory()->makeOne([
            'account_number' => $this->getTestAccountNumber(),
            'office_id' => $this->getTestOfficeId(),
        ]);
        $customer = CustomerData::getTestEntityData(
            1,
            ['customerID' => $this->getTestAccountNumber()]
        )->first();

        $accountServiceMock = $this->createMock(AccountService::class);
        $accountServiceMock
            ->expects(self::once())
            ->method('getAccountByAccountNumber')
            ->with($this->getTestAccountNumber())
            ->willReturn($account);

        $customerRepositoryMock = $this->createMock(CustomerRepository::class);
        $customerRepositoryMock
            ->expects(self::once())
            ->method('office')
            ->with($this->getTestOfficeId())
            ->willReturnSelf();
        $customerRepositoryMock
            ->expects(self::once())
            ->method('find')
            ->with($this->getTestAccountNumber())
            ->willReturn($customer);

        $spotRepositoryMock = $this->createMock(SpotRepository::class);
        $spotRepositoryMock
            ->expects(self::once())
            ->method('getSpots')
            ->with(self::callback(static function ($dto) use ($customer) {
                return $dto->officeId === $customer->officeId
                    && $dto->customerId === $customer->id
                    && $dto->lat === $customer->latitude
                    && $dto->lng === $customer->longitude
                    && $dto->state === $customer->address->state
                    && $dto->isInitial === false;
            }))
            ->willReturn(['test']);

        $action = new ShowSpotsFromFlexIVRAction(
            $accountServiceMock,
            $customerRepositoryMock,
            $spotRepositoryMock
        );

        Event::fake();
        $this->assertSame(['test'], $action($this->getTestAccountNumber()));
        Event::assertDispatched(SpotSearched::class);
    }
}
