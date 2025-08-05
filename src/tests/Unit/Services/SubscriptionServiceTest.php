<?php

namespace Tests\Unit\Services;

use App\DTO\Subscriptions\ActivateSubscriptionRequestDTO;
use App\DTO\Subscriptions\ActivateSubscriptionResponseDTO;
use App\DTO\Subscriptions\DeactivateSubscriptionRequestDTO;
use App\DTO\Subscriptions\DeactivateSubscriptionResponseDTO;
use App\Interfaces\Repository\SubscriptionRepository;
use App\Models\Account;
use App\Models\External\SubscriptionModel;
use App\Services\AppointmentService;
use App\Services\SubscriptionService;
use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Collection;
use Mockery;
use Tests\Data\SubscriptionData;
use Tests\TestCase;
use Tests\Traits\RandomIntTestData;

class SubscriptionServiceTest extends TestCase
{
    use RandomIntTestData;

    private const DUE_DATE = '2022-08-13';

    protected SubscriptionRepository $subscriptionRepositoryMock;
    protected SubscriptionService $subscriptionService;
    protected AppointmentService $appointmentServiceMock;

    public function setUp(): void
    {
        parent::setUp();

        $this->subscriptionRepositoryMock = Mockery::mock(SubscriptionRepository::class);
        $this->appointmentServiceMock = Mockery::mock(AppointmentService::class);
    }

    public function test_getnextduedateforthecustomer_gets_valid_date()
    {
        $this->setupSubscriptionRepository();

        $date = $this
            ->getSubscriptionService()
            ->getNextDueDateForTheCustomer($this->getTestOfficeId(), $this->getTestAccountNumber());

        $this->assertInstanceOf(Carbon::class, $date);
        $this->assertEquals(self::DUE_DATE, $date->format('Y-m-d'));
    }

    public function test_getnextduedateforthecustomer_gets_null_for_customer_without_subscription()
    {
        $this->setupSubscriptionRepository(false);

        $date = $this
            ->getSubscriptionService()
            ->getNextDueDateForTheCustomer($this->getTestOfficeId(), $this->getTestAccountNumber());

        $this->assertNull($date);
    }

    public function test_getnextduedateforthecustomer_throws_exception_on_subscriptionrepository_exception()
    {
        $this->setupSubscriptionRepositoryException();

        $this->expectException(Exception::class);
        $this->getSubscriptionService()->getNextDueDateForTheCustomer($this->getTestOfficeId(), $this->getTestAccountNumber());
    }

    public function test_activatesubscription_activate_subscription(): void
    {
        $account = $this->getAccountMock();
        $subscription = SubscriptionData::getTestEntityData(1)->first();

        $this->setupOfficeToReturnSubscriptionRepository(2);
        $this->setupActivateSubscriptionToReturnValidResponseDTO($subscription);
        $this->setupSearchByCustomerIdToReturnActiveSubscriptions(SubscriptionData::getTestEntityData(0));

        $this->getSubscriptionService()->activateSubscription($account, $subscription);
    }

    public function test_activatesubscription_deactivate_active_subscription(): void
    {
        $account = $this->getAccountMock();
        $subscription = SubscriptionData::getTestEntityData(1)->first();
        $activeSubscriptions = SubscriptionData::getTestEntityData(1, [
            'subscriptionID' => $this->getTestSubscriptionId() + 1
        ]);

        $this->setupOfficeToReturnSubscriptionRepository(3);
        $this->setupActivateSubscriptionToReturnValidResponseDTO($subscription);
        $this->setupSearchByCustomerIdToReturnActiveSubscriptions($activeSubscriptions);

        $this->appointmentServiceMock
            ->shouldReceive('reassignSubscriptionToAppointment')
            ->withArgs([$subscription, $activeSubscriptions->first()]);

        $this->subscriptionRepositoryMock
            ->shouldReceive('deactivateSubscription')
            ->withArgs(
                fn (DeactivateSubscriptionRequestDTO $requestDTO) =>
                    $requestDTO->subscriptionId === $activeSubscriptions->first()->id &&
                    $requestDTO->officeId === $account->office_id &&
                    $requestDTO->customerId === $account->account_number
            )
            ->once()
            ->andReturn(new DeactivateSubscriptionResponseDTO(subscriptionId: $this->getTestSubscriptionId() + 1));

        $this->getSubscriptionService()->activateSubscription($account, $subscription);
    }

    public function test_activatesubscription_skip_deactivation_newly_activated_subscription(): void
    {
        $account = $this->getAccountMock();

        $subscription = SubscriptionData::getTestEntityData(1, [
            'subscriptionID' => $this->getTestSubscriptionId()
        ])->first();
        $activeSubscriptions = SubscriptionData::getTestEntityData(1, [
            'subscriptionID' => $this->getTestSubscriptionId()
        ]);

        $this->setupOfficeToReturnSubscriptionRepository(2);
        $this->setupActivateSubscriptionToReturnValidResponseDTO($subscription);
        $this->setupSearchByCustomerIdToReturnActiveSubscriptions($activeSubscriptions);

        $this->appointmentServiceMock
            ->shouldReceive('reassignSubscriptionToAppointment')
            ->withArgs([$subscription, $activeSubscriptions->first()]);

        $this->subscriptionRepositoryMock
            ->shouldReceive('deactivateSubscription')
            ->withArgs(
                fn (DeactivateSubscriptionRequestDTO $requestDTO) =>
                    $requestDTO->subscriptionId === $activeSubscriptions->first()->id &&
                    $requestDTO->officeId === $account->office_id &&
                    $requestDTO->customerId === $account->account_number
            )
            ->never();

        $this->getSubscriptionService()->activateSubscription($account, $subscription);
    }

    public function test_activatesubscription_skip_processing_if_there_is_no_active_subscriptions(): void
    {
        $account = $this->getAccountMock();
        $subscription = SubscriptionData::getTestEntityData(1)->first();
        $activeSubscriptions = SubscriptionData::getTestEntityData(0);

        $this->setupOfficeToReturnSubscriptionRepository(2);
        $this->setupActivateSubscriptionToReturnValidResponseDTO($subscription);
        $this->setupSearchByCustomerIdToReturnActiveSubscriptions($activeSubscriptions);

        $this->appointmentServiceMock
            ->shouldReceive('reassignSubscriptionToAppointment')
            ->withAnyArgs()
            ->never();

        $this->subscriptionRepositoryMock
            ->shouldReceive('deactivateSubscription')
            ->withAnyArgs()
            ->never();

        $this->getSubscriptionService()->activateSubscription($account, $subscription);
    }

    public function test_activatesubscription_throws_internal_server_error_http_exception(): void
    {
        $account = $this->getAccountMock();
        $subscription = SubscriptionData::getTestEntityData(1, [
            'subscriptionID' => $this->getTestSubscriptionId()
        ])->first();
        $activeSubscriptions = SubscriptionData::getTestEntityData(1, [
            'subscriptionID' => $this->getTestSubscriptionId() + 1
        ]);

        $this->setupOfficeToReturnSubscriptionRepository(3);
        $this->setupActivateSubscriptionToReturnValidResponseDTO($subscription);
        $this->setupSearchByCustomerIdToReturnActiveSubscriptions($activeSubscriptions);

        $this->appointmentServiceMock
            ->shouldReceive('reassignSubscriptionToAppointment')
            ->withArgs([$subscription, $activeSubscriptions->first()]);

        $this->subscriptionRepositoryMock
            ->shouldReceive('deactivateSubscription')
            ->withArgs(
                fn (DeactivateSubscriptionRequestDTO $requestDTO) =>
                    $requestDTO->subscriptionId === $activeSubscriptions->first()->id &&
                    $requestDTO->officeId === $account->office_id &&
                    $requestDTO->customerId === $account->account_number
            )
            ->once()
            ->andThrow(new InternalServerErrorHttpException());

        $this->getSubscriptionService()->activateSubscription($account, $subscription);
    }

    protected function getSubscriptionService(): SubscriptionService
    {
        if (empty($this->subscriptionService)) {
            $this->subscriptionService = new SubscriptionService(
                $this->subscriptionRepositoryMock,
                $this->appointmentServiceMock
            );
        }

        return $this->subscriptionService;
    }

    protected function setupSubscriptionRepository($withSubscriptions = true)
    {
        $subscriptions = $withSubscriptions
            ? SubscriptionData::getTestEntityData(
                2,
                ['nextService' => '2022-08-13'],
                ['nextService' => '2022-09-30'],
            )
            : new Collection();

        $this->subscriptionRepositoryMock
            ->shouldReceive('office')
            ->with($this->getTestOfficeId())
            ->once()
            ->andReturnSelf();

        $this->subscriptionRepositoryMock
            ->shouldReceive('searchByCustomerId')
            ->with([$this->getTestAccountNumber()])
            ->andReturn($subscriptions)
            ->once();
    }

    protected function setupSubscriptionRepositoryException()
    {
        $this->subscriptionRepositoryMock->shouldReceive('office')->andReturnSelf();

        $this->subscriptionRepositoryMock
            ->shouldReceive('searchByCustomerId')
            ->with([$this->getTestAccountNumber()])
            ->andThrow(Exception::class)
            ->once();
    }

    protected function getAccountMock(): Account
    {
        return Account::factory()->make([
            'office_id' => $this->getTestOfficeId(),
            'account_number' => $this->getTestAccountNumber(),
        ]);
    }

    protected function setupActivateSubscriptionToReturnValidResponseDTO(SubscriptionModel $subscriptionModel): void
    {
        $this->subscriptionRepositoryMock
            ->shouldReceive('activateSubscription')
            ->withArgs(
                fn (ActivateSubscriptionRequestDTO $requestDTO) =>
                    $requestDTO->subscriptionId === $subscriptionModel->id &&
                    $requestDTO->officeId === $this->getAccountMock()->office_id &&
                    $requestDTO->customerId === $this->getAccountMock()->account_number
            )
            ->once()
            ->andReturn(new ActivateSubscriptionResponseDTO(subscriptionId: $subscriptionModel->id));
    }

    protected function setupOfficeToReturnSubscriptionRepository(int $times): void
    {
        $this->subscriptionRepositoryMock
            ->shouldReceive('office')
            ->withArgs([$this->getAccountMock()->office_id])
            ->times($times)
            ->andReturn($this->subscriptionRepositoryMock);
    }

    protected function setupSearchByCustomerIdToReturnActiveSubscriptions(Collection $activeSubscriptions): void
    {
        $this->subscriptionRepositoryMock
            ->shouldReceive('searchByCustomerId')
            ->withArgs([[$this->getAccountMock()->account_number]])
            ->once()
            ->andReturn($activeSubscriptions);
    }
}
