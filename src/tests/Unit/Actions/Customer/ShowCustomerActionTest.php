<?php

namespace Tests\Unit\Actions\Customer;

use App\Actions\Customer\ShowCustomerAction;
use App\DTO\Customer\ShowCustomerResultDTO;
use App\DTO\PlanBuilder\CurrentPlanDTO;
use App\Exceptions\PlanBuilder\FieldNotFound;
use App\Exceptions\Subscription\CanNotDetermineDueSubscription;
use App\Interfaces\Repository\AppointmentRepository;
use App\Interfaces\Repository\CustomerRepository;
use App\Interfaces\Repository\PaymentProfileRepository;
use App\Interfaces\Repository\ServiceTypeRepository;
use App\Models\Account;
use App\Models\External\AppointmentModel;
use App\Models\External\CustomerModel;
use App\Models\External\SubscriptionModel;
use App\Services\PlanBuilderService;
use App\Utilites\CustomerDutyDeterminer;
use Aptive\PestRoutesSDK\Resources\Appointments\AppointmentStatus;
use Aptive\PestRoutesSDK\Resources\Customers\CustomerAutoPay;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Mockery;
use Mockery\MockInterface;
use Tests\Data\AppointmentData;
use Tests\Data\CustomerData;
use Tests\Data\PaymentProfileData;
use Tests\Data\PlanBuilderResultsData;
use Tests\Data\ServiceTypeData;
use Tests\Data\SubscriptionData;
use Tests\TestCase;
use Tests\Traits\RandomIntTestData;

class ShowCustomerActionTest extends TestCase
{
    use RandomIntTestData;

    protected MockInterface|CustomerRepository $customerRepositoryMock;
    protected MockInterface|PaymentProfileRepository $paymentProfileRepositoryMock;
    protected MockInterface|AppointmentRepository $appointmentRepositoryMock;
    protected MockInterface|ServiceTypeRepository $serviceTypeRepositoryMock;
    protected MockInterface|CustomerDutyDeterminer $customerDutyDeterminerMock;
    protected MockInterface|PlanBuilderService $planBuilderServiceMock;

    protected Account $accountModel;

    public function setUp(): void
    {
        parent::setUp();

        $this->customerRepositoryMock = Mockery::mock(CustomerRepository::class);
        $this->paymentProfileRepositoryMock = Mockery::mock(PaymentProfileRepository::class);
        $this->customerDutyDeterminerMock = Mockery::mock(CustomerDutyDeterminer::class);
        $this->planBuilderServiceMock = Mockery::mock(PlanBuilderService::class);

        $this->accountModel = Account::factory()->makeOne([
            'account_number' => $this->getTestAccountNumber(),
            'office_id' => $this->getTestOfficeId(),
        ]);
    }

    private function getExtendedObject(): object
    {
        return new class (
            $this->customerRepositoryMock,
            $this->paymentProfileRepositoryMock,
            $this->customerDutyDeterminerMock,
            $this->planBuilderServiceMock,
        ) extends ShowCustomerAction {
            public function isCustomerDueForStandardTreatmentTest(CustomerModel $customer): bool
            {
                return parent::isCustomerDueForStandardTreatment($customer);
            }

            public function getCustomerAutoPayProfileLastFourTest(CustomerModel $customer): string|null
            {
                return parent::getCustomerAutoPayProfileLastFour($customer);
            }

            public function getLastTreatmentDateTest(CustomerModel $customer): string|null
            {
                return parent::getLastTreatmentDate($customer);
            }
        };
    }

    public function test_show_customer(): void
    {
        $subject = $this->getAction();
        $customerDueDate = Carbon::now()->addDays(random_int(1, 10))->format('Y-m-d');
        $subscriptions = $this->getSubscriptions($customerDueDate);
        $subscriptionsRaw = SubscriptionData::getRawTestData();

        $phone = (string) random_int(10000000, 19999999);
        $balanceCents = random_int(10, 1000);
        $isOnMonthlyBilling = false;
        $customer = $this->getCustomer(
            $phone,
            $balanceCents,
            $subscriptionsRaw,
            $subscriptions,
        );
        $customerAutoPayProfileLastFour = random_int(1000, 9999);
        $isCustomerDueForStandardTreatment = true;
        $lastTreatmentDate = Carbon::now()->subDays(random_int(65, 100))->format('Y-m-d');

        $this->setUpRepositoriesToReturnValidData(
            $subject,
            $customer,
            $customerAutoPayProfileLastFour,
            $isCustomerDueForStandardTreatment,
            $lastTreatmentDate
        );

        $products = PlanBuilderResultsData::getProducts();
        $this->planBuilderServiceMock
            ->shouldReceive('getProducts')
            ->withArgs([$this->getTestOfficeId()])
            ->once()
            ->andReturn($products);
        $plan = PlanBuilderResultsData::getCurrentPlan();
        $this->planBuilderServiceMock
            ->shouldReceive('getServicePlan')
            ->withArgs([$subscriptions->first()->serviceId, $this->getTestOfficeId()])
            ->once()
            ->andReturn($plan);

        $expectedDto = new ShowCustomerResultDTO(
            id: $customer->id,
            officeId: $customer->officeId,
            firstName: $customer->firstName,
            lastName: $customer->lastName,
            email: $customer->email,
            phoneNumber: $phone,
            balanceCents: $balanceCents,
            isOnMonthlyBilling: $isOnMonthlyBilling,
            dueDate: $customerDueDate,
            paymentProfileId: $customer->autoPayPaymentProfileId,
            autoPayProfileLastFour: $customerAutoPayProfileLastFour,
            isDueForStandardTreatment: $isCustomerDueForStandardTreatment,
            lastTreatmentDate: $lastTreatmentDate,
            status: $customer->status,
            autoPayMethod: CustomerAutoPay::AutoPayCC,
            currentPlan: new CurrentPlanDTO(
                'Premium',
                [
                    'Pantry Pests',
                    'Snail/Slug/Aphid',
                    'Outdoor Rodent (includes voles)',
                    'Mosquitoes',
                    'Outdoor Wasp Trap Service',
                    'Indoor Fly Trap Service'
                ],
                \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2024-03-22 08:33:10'),
                \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2024-03-22 08:33:10'),
                false,
            ),
        );

        $result = ($subject)($this->accountModel);

        self::assertEquals($expectedDto, $result);
    }

    public function test_show_customer_with_empty_plan_data_on_field_not_found(): void
    {
        $subject = $this->getAction();
        $customerDueDate = Carbon::now()->addDays(random_int(1, 10))->format('Y-m-d');
        $subscriptions = $this->getSubscriptions($customerDueDate);
        $subscriptionsRaw = SubscriptionData::getRawTestData();

        $phone = (string) random_int(10000000, 19999999);
        $balanceCents = random_int(10, 1000);
        $isOnMonthlyBilling = false;
        $customer = $this->getCustomer(
            $phone,
            $balanceCents,
            $subscriptionsRaw,
            $subscriptions,
        );
        $customerAutoPayProfileLastFour = random_int(1000, 9999);
        $isCustomerDueForStandardTreatment = true;
        $lastTreatmentDate = Carbon::now()->subDays(random_int(65, 100))->format('Y-m-d');

        $this->setUpRepositoriesToReturnValidData(
            $subject,
            $customer,
            $customerAutoPayProfileLastFour,
            $isCustomerDueForStandardTreatment,
            $lastTreatmentDate
        );

        $this->planBuilderServiceMock
            ->shouldReceive('getProducts')
            ->withArgs([$this->getTestOfficeId()])
            ->never();
        $this->planBuilderServiceMock
            ->shouldReceive('getServicePlan')
            ->withArgs([$subscriptions->first()->serviceId, $this->getTestOfficeId()])
            ->once()
            ->andThrow(new FieldNotFound());

        $expectedDto = new ShowCustomerResultDTO(
            id: $customer->id,
            officeId: $customer->officeId,
            firstName: $customer->firstName,
            lastName: $customer->lastName,
            email: $customer->email,
            phoneNumber: $phone,
            balanceCents: $balanceCents,
            isOnMonthlyBilling: $isOnMonthlyBilling,
            dueDate: $customerDueDate,
            paymentProfileId: $customer->autoPayPaymentProfileId,
            autoPayProfileLastFour: $customerAutoPayProfileLastFour,
            isDueForStandardTreatment: $isCustomerDueForStandardTreatment,
            lastTreatmentDate: $lastTreatmentDate,
            status: $customer->status,
            autoPayMethod: CustomerAutoPay::AutoPayCC,
            currentPlan: new CurrentPlanDTO(
                '',
                [],
                \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2024-03-22 08:33:10'),
                \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2024-03-22 08:33:10'),
                true,
            ),
        );

        $result = ($subject)($this->accountModel);

        self::assertEquals($expectedDto, $result);
    }

    /**
     * @return MockInterface|ShowCustomerAction
     *
     */
    protected function getAction(): MockInterface|ShowCustomerAction
    {
        $subject = Mockery::mock(ShowCustomerAction::class, [
            $this->customerRepositoryMock,
            $this->paymentProfileRepositoryMock,
            $this->customerDutyDeterminerMock,
            $this->planBuilderServiceMock,
        ])->makePartial();

        $subject->shouldAllowMockingProtectedMethods();
        return $subject;
    }

    /**
     * @param string $customerDueDate
     * @return Collection
     * @throws \App\Exceptions\Entity\RelationNotFoundException
     */
    protected function getSubscriptions(string $customerDueDate): Collection
    {
        $subscriptions = SubscriptionData::getTestEntityData(
            1,
            [
                'serviceType' => 'Pro',
                'dateAdded' => '2024-03-22 08:33:10',
                'nextService' => $customerDueDate,
                'agreementLength' => '18',
            ]
        );
        $subscriptions->first()->setRelated('serviceType', ServiceTypeData::getTestEntityData()->first());
        return $subscriptions;
    }

    /**
     * @param string $phone
     * @param float $balanceCents
     * @param Collection $subscriptionsRaw
     * @param Collection $subscriptions
     * @return CustomerModel|\Closure|null
     * @throws \App\Exceptions\Entity\RelationNotFoundException
     */
    protected function getCustomer(
        string $phone,
        float $balanceCents,
        Collection $subscriptionsRaw,
        Collection $subscriptions,
    ): CustomerModel {
        $customer = CustomerData::getTestEntityData(1, [
            'customerID' => $this->getTestAccountNumber(),
            'officeID' => $this->getTestOfficeId(),
            'fname' => 'firstName',
            'lname' => 'lastName',
            'email' => 'test@email.com',
            'phone1' => $phone,
            'autoPayPaymentProfileID' => $this->getTestAutoPayPaymentProfileID(),
            'balance' => (string) $balanceCents/100,
            'responsibleBalance' => (string) $balanceCents/100,
            'subscriptions' => $subscriptionsRaw->map(static fn ($item) => (object) $item)->toArray(),
        ])->first();
        $customer->setRelated('subscriptions', $subscriptions);

        return $customer;
    }

    protected function setUpRepositoriesToReturnValidData(
        MockInterface|ShowCustomerAction $subject,
        CustomerModel $customer,
        int $customerAutoPayProfileLastFour,
        bool $isCustomerDueForStandardTreatment,
        string $lastTreatmentDate
    ) {
        $this->customerRepositoryMock
            ->shouldReceive('office')
            ->withArgs([$this->accountModel->office_id])
            ->andReturn($this->customerRepositoryMock)
            ->once();

        $this->customerRepositoryMock
            ->shouldReceive('withRelated')
            ->withArgs([['subscriptions.serviceType', 'appointments.serviceType']])
            ->andReturn($this->customerRepositoryMock)
            ->once();

        $this->customerRepositoryMock
            ->shouldReceive('find')
            ->withArgs([$this->accountModel->account_number])
            ->andReturn($customer)
            ->once();

        $subject->shouldReceive('getCustomerAutoPayProfileLastFour')
            ->withArgs([$customer])
            ->andReturn($customerAutoPayProfileLastFour)
            ->once();

        $subject->shouldReceive('isCustomerDueForStandardTreatment')
            ->withArgs([$customer])
            ->andReturn($isCustomerDueForStandardTreatment)
            ->once();

        $subject->shouldReceive('getLastTreatmentDate')
            ->withArgs([$customer])
            ->andReturn($lastTreatmentDate)
            ->once();
    }

    public function test_is_customer_due_for_standard_treatment_returns_true_if_subscription_determined(): void
    {
        $customer = CustomerData::getTestEntityData()->first();
        $subscription = SubscriptionData::getTestEntityData()->first();

        $this->customerDutyDeterminerMock
            ->shouldReceive('getSubscriptionCustomerIsDueFor')
            ->withArgs([$customer])
            ->andReturn($subscription)
            ->once();

        $subject = $this->getExtendedObject();
        $result = $subject->isCustomerDueForStandardTreatmentTest($customer);

        self::assertTrue($result);

        // Check that determiner triggered only once
        $subject->isCustomerDueForStandardTreatmentTest($customer);
    }

    public function test_is_customer_due_for_standard_treatment_returns_false_if_no_due_subscription(): void
    {
        $customer = CustomerData::getTestEntityData()->first();

        $this->customerDutyDeterminerMock
            ->shouldReceive('getSubscriptionCustomerIsDueFor')
            ->withArgs([$customer])
            ->andReturn(null)
            ->once();

        $subject = $this->getExtendedObject();
        $result = $subject->isCustomerDueForStandardTreatmentTest($customer);

        self::assertFalse($result);
    }

    public function test_is_customer_due_for_standard_treatment_returns_false_if_can_not_determine_subscription(): void
    {
        Log::spy();

        $customer = CustomerData::getTestEntityData()->first();

        $this->customerDutyDeterminerMock
            ->shouldReceive('getSubscriptionCustomerIsDueFor')
            ->withArgs([$customer])
            ->andThrow(new CanNotDetermineDueSubscription())
            ->once();

        $subject = $this->getExtendedObject();
        $result = $subject->isCustomerDueForStandardTreatmentTest($customer);

        self::assertFalse($result);
    }

    /**
     * @dataProvider customerAutoPayDataProvider
     */
    public function test_get_customer_autopay_last_four(
        CustomerAutoPay|null $autoPay,
        string|null $lastFour,
        string $expectedResult
    ): void {
        /** @var CustomerModel $customer */
        $customer = CustomerData::getTestEntityData(
            1,
            ['aPay' => $autoPay->value]
        )->first();

        if ($customer->autoPay === CustomerAutoPay::NotOnAutoPay) {
            $this->paymentProfileRepositoryMock
                ->shouldReceive('getPaymentProfile')
                ->never();
        } else {
            $paymentProfile = PaymentProfileData::getTestEntityData(
                1,
                ['lastFour' => $lastFour]
            )->first();

            $this->paymentProfileRepositoryMock
                ->shouldReceive('office')
                ->with($customer->officeId)
                ->once()
                ->andReturnSelf();

            $this->paymentProfileRepositoryMock
                ->shouldReceive('find')
                ->with($customer->autoPayPaymentProfileId)
                ->once()
                ->andReturn($paymentProfile);
        }

        $result = $this->getExtendedObject()->getCustomerAutoPayProfileLastFourTest($customer);

        self::assertEquals($expectedResult, $result);
    }

    /**
     * @return iterable<int, array<int, mixed>>
     */
    public function customerAutoPayDataProvider(): iterable
    {
        yield [
            CustomerAutoPay::AutoPayCC,
            $lastFour = (string) random_int(1000, 9999),
            $lastFour,
        ];
        yield [
            CustomerAutoPay::AutoPayACH,
            $lastFour = (string) random_int(1000, 9999),
            $lastFour,
        ];
        yield [
            CustomerAutoPay::NotOnAutoPay,
            null,
            '',
        ];
    }

    /**
     * @dataProvider lastTreatmentDateDataProvider
     */
    public function test_get_last_treatment_date(
        array $subscriptionsData,
        array $appointmentsData,
        array|null $dueSubscriptionData,
        string|null $expectedResult
    ): void {
        /** @var CustomerModel $customer */
        $customer = CustomerData::getTestEntityData()->first();

        $subscriptions = new Collection();

        foreach ($subscriptionsData as $datum) {
            /** @var SubscriptionModel $subscription */
            $subscription = SubscriptionData::getTestEntityData(1, $datum)->first();
            $serviceType = ServiceTypeData::getTestEntityDataOfTypes($subscription->serviceId)->first();
            $subscription->setRelated('serviceType', $serviceType);
            $subscriptions->add($subscription);
        }

        $appointments = new Collection();

        foreach ($appointmentsData as $datum) {
            /** @var AppointmentModel $appointment */
            $appointment = AppointmentData::getTestEntityData(1, $datum)->first();
            $serviceType = ServiceTypeData::getTestEntityDataOfTypes($appointment->serviceTypeId)->first();
            $appointment->setRelated('serviceType', $serviceType);
            $appointments->add($appointment);
        }

        $dueSubscription = $dueSubscriptionData
            ? SubscriptionData::getTestEntityData(1, $dueSubscriptionData)->first()
            : null;

        $customer->setRelated('subscriptions', $subscriptions);
        $customer->setRelated('appointments', $appointments);

        $this->customerDutyDeterminerMock
            ->shouldReceive('getSubscriptionCustomerIsDueFor')
            ->withArgs([$customer])
            ->andReturn($dueSubscription);

        $result = $this->getExtendedObject()->getLastTreatmentDateTest($customer);

        self::assertEquals($expectedResult, $result);
    }

    /**
     * @return iterable<int, array<int, mixed>>
     */
    public function lastTreatmentDateDataProvider(): iterable
    {
        $dateFormat = 'Y-m-d';

        $keySubscriptions = 'subscriptions';
        $keyAppointments = 'appointments';
        $keyDueSubscription = 'due subscription';
        $keyResult = 'result';

        yield 'no subscriptions' => [
            $keySubscriptions => [],
            $keyAppointments => [],
            $keyDueSubscription => null,
            $keyResult => null,
        ];

        yield 'no appointments, is due' => [
            $keySubscriptions => [['serviceID' => ServiceTypeData::PRO]],
            $keyAppointments => [],
            $keyDueSubscription => ['serviceID' => ServiceTypeData::PRO],
            $keyResult => null,
        ];

        yield '2 subscription, is due' => [
            $keySubscriptions => [
                ['serviceID' => ServiceTypeData::PRO],
                ['serviceID' => ServiceTypeData::MOSQUITO],
            ],
            $keyAppointments => [
                [
                    'date' => Carbon::now()->subDays(random_int(11, 20))->format($dateFormat),
                    'type' => ServiceTypeData::PRO,
                    'status' => AppointmentStatus::Completed->value,
                ],
                [
                    'date' => $lastDate = Carbon::now()->subDays(random_int(5, 10))->format($dateFormat),
                    'type' => ServiceTypeData::PRO,
                    'status' => AppointmentStatus::Completed->value,
                ],
                [
                    'date' => Carbon::now()->subDays(random_int(21, 30))->format($dateFormat),
                    'type' => ServiceTypeData::PRO,
                    'status' => AppointmentStatus::Completed->value,
                ],
                [
                    'date' => Carbon::now()->subDays(random_int(1, 4))->format($dateFormat),
                    'type' => ServiceTypeData::MOSQUITO,
                    'status' => AppointmentStatus::Completed->value,
                ],
            ],
            $keyDueSubscription => ['serviceID' => ServiceTypeData::PRO],
            $keyResult => $lastDate,
        ];

        yield '2 subscription, not due' => [
            $keySubscriptions => [
                ['serviceID' => ServiceTypeData::PRO],
                ['serviceID' => ServiceTypeData::MOSQUITO],
            ],
            $keyAppointments => [
                [
                    'date' => Carbon::now()->subDays(random_int(11, 20))->format($dateFormat),
                    'type' => ServiceTypeData::PRO,
                    'status' => AppointmentStatus::Completed->value,
                ],
                [
                    'date' => Carbon::now()->subDays(random_int(5, 10))->format($dateFormat),
                    'type' => ServiceTypeData::PRO,
                    'status' => AppointmentStatus::Completed->value,
                ],
                [
                    'date' => Carbon::now()->subDays(random_int(21, 30))->format($dateFormat),
                    'type' => ServiceTypeData::PRO,
                    'status' => AppointmentStatus::Completed->value,
                ],
                [
                    'date' => $lastDate = Carbon::now()->subDays(random_int(1, 4))->format($dateFormat),
                    'type' => ServiceTypeData::MOSQUITO,
                    'status' => AppointmentStatus::Completed->value,
                ],
            ],
            $keyDueSubscription => null,
            $keyResult => $lastDate,
        ];

        yield '2 subscription, not match' => [
            $keySubscriptions => [
                ['serviceID' => ServiceTypeData::QUARTERLY_SERVICE],
                ['serviceID' => ServiceTypeData::MOSQUITO],
            ],
            $keyAppointments => [
                [
                    'date' => Carbon::now()->subDays(random_int(11, 20))->format($dateFormat),
                    'type' => ServiceTypeData::PREMIUM,
                    'status' => AppointmentStatus::Completed->value,
                ],
                [
                    'date' => Carbon::now()->subDays(random_int(5, 10))->format($dateFormat),
                    'type' => ServiceTypeData::PRO_PLUS,
                    'status' => AppointmentStatus::Completed->value,
                ],
                [
                    'date' => Carbon::now()->subDays(random_int(21, 30))->format($dateFormat),
                    'type' => ServiceTypeData::RESERVICE,
                    'status' => AppointmentStatus::Completed->value,
                ],
                [
                    'date' =>  Carbon::now()->subDays(random_int(1, 4))->format($dateFormat),
                    'type' => ServiceTypeData::PRO,
                    'status' => AppointmentStatus::Completed->value,
                ],
            ],
            $keyDueSubscription => ['serviceID' => ServiceTypeData::QUARTERLY_SERVICE],
            $keyResult => null,
        ];
    }
}
