<?php

namespace Tests\Unit\Services;

use App\DTO\Customer\SearchCustomersDTO;
use App\DTO\Customer\UpdateCommunicationPreferencesDTO;
use App\DTO\Payment\AchPaymentMethod;
use App\DTO\Payment\CreditCardPaymentMethod;
use App\DTO\Payment\PaymentMethodsListRequestDTO;
use App\Enums\Models\Payment\PaymentMethod as PaymentMethodEnum;
use App\Exceptions\Entity\EntityNotFoundException;
use App\Interfaces\Repository\CustomerRepository;
use App\Interfaces\Repository\OfficeRepository;
use App\Interfaces\Repository\PaymentProfileRepository;
use App\Models\Account;
use App\Models\External\CustomerModel;
use App\Models\External\PaymentProfileModel;
use App\Models\External\SubscriptionModel;
use App\Models\User;
use App\Repositories\AptivePayment\AptivePaymentRepository;
use App\Services\CustomerService;
use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use JsonException;
use Mockery;
use Mockery\MockInterface;
use Tests\Data\CustomerData;
use Tests\Data\PaymentProfileData;
use Tests\Data\ServiceTypeData;
use Tests\Data\SubscriptionData;
use Tests\TestCase;
use Tests\Traits\PestroutesSdkExceptionProvider;
use Tests\Traits\RandomIntTestData;
use Tests\Traits\RandomStringTestData;
use Throwable;

class CustomerServiceTest extends TestCase
{
    use PestroutesSdkExceptionProvider;
    use RandomIntTestData;
    use RandomStringTestData;

    private const EMAIL = 'test@email.com';

    protected CustomerService $customerService;
    protected CustomerRepository $customerRepositoryMock;
    protected PaymentProfileRepository $paymentProfileRepositoryMock;
    protected CustomerModel $customer;
    protected MockInterface|OfficeRepository $officeRepositoryMock;
    protected AptivePaymentRepository $aptivePaymentRepository;

    public function setUp(): void
    {
        parent::setUp();

        $this->customerRepositoryMock = Mockery::mock(CustomerRepository::class);
        $this->paymentProfileRepositoryMock = Mockery::mock(PaymentProfileRepository::class);
        $this->officeRepositoryMock = Mockery::mock(OfficeRepository::class);
        $this->aptivePaymentRepository = Mockery::mock(AptivePaymentRepository::class);
        $this->customerService = new CustomerService(
            $this->customerRepositoryMock,
            $this->paymentProfileRepositoryMock,
            $this->officeRepositoryMock,
            $this->aptivePaymentRepository
        );
        $this->customer = CustomerData::getTestEntityData()->firstOrFail();
        $subscriptions = SubscriptionData::getTestEntityData(1, [
            'customerID' => $this->customer->id,
            'officeID' => $this->customer->officeId,
        ]);

        /** @var SubscriptionModel $subscription */
        $subscription = $subscriptions->first();
        $serviceType = ServiceTypeData::getTestEntityDataOfTypes($subscription->serviceId)->first();
        $subscription->setRelated('serviceType', $serviceType);
        $this->customer->setRelated('subscriptions', $subscriptions);
    }

    public function test_it_updates_communication_preferences(): void
    {
        $dto = $this->getUpdateCommunicationPreferencesDto();

        $this
            ->customerRepositoryMock
            ->expects('updateCustomerCommunicationPreferences')
            ->withArgs([$dto])
            ->once()
            ->andReturn($dto->accountNumber);

        $updatedCustomerId = $this->customerService->updateCommunicationPreferences($dto);

        $this->assertSame($dto->accountNumber, $updatedCustomerId);
    }

    public function test_update_communication_preferences_passes_exception(): void
    {
        $dto = $this->getUpdateCommunicationPreferencesDto();

        $this
            ->customerRepositoryMock
            ->expects('updateCustomerCommunicationPreferences')
            ->withArgs([$dto])
            ->once()
            ->andThrow(new InternalServerErrorHttpException());

        $this->expectException(InternalServerErrorHttpException::class);

        $this->customerService->updateCommunicationPreferences($dto);
    }

    /**
     * @dataProvider pestroutesSdkExceptionProvider
     */
    public function test_getting_autopay_data_passes_api_exceptions_when_fetching_customer(
        Throwable $exception
    ): void {
        $account = $this->getAccount();

        $this->customerRepositoryMock
            ->shouldReceive('office')
            ->andReturnSelf();

        $this->customerRepositoryMock
            ->shouldReceive('withRelated')
            ->andReturnSelf();

        $this->customerRepositoryMock
            ->expects('find')
            ->withAnyArgs()
            ->andThrow($exception);

        $this->paymentProfileRepositoryMock
            ->expects('getPaymentProfile')
            ->withAnyArgs()
            ->never();

        $this->expectException($exception::class);

        $this->customerService->getCustomerAutoPayData($account);
    }

    /**
     * @dataProvider getPaymentProfileExceptionProvider
     */
    public function test_getting_autopay_data_passes_api_exceptions_when_fetching_payment_profile(
        Throwable $exception,
        bool $usePaymentServiceApi = false
    ): void {
        $account = $this->getAccount();

        $this->customerRepositoryMock
            ->shouldReceive('office')
            ->andReturnSelf();

        $this->customerRepositoryMock
            ->shouldReceive('withRelated')
            ->andReturnSelf();

        $this->customerRepositoryMock
            ->expects('find')
            ->withAnyArgs()
            ->andReturn($this->customer);

        if($usePaymentServiceApi) {
            $this->aptivePaymentRepository
                ->expects('getPaymentMethodsList')
                ->with(PaymentMethodsListRequestDTO::class)
                ->andThrow($exception);
        } else {
            $this->paymentProfileRepositoryMock
                ->expects('office')
                ->andReturnSelf();

            $this->paymentProfileRepositoryMock
                ->expects('find')
                ->andThrow($exception);
        }

        $this->expectException($exception::class);

        $this->customerService->getCustomerAutoPayData($account, $usePaymentServiceApi);
    }

    public function getPaymentProfileExceptionProvider(): array
    {
        return [
            [new JsonException(), true],
            [new JsonException(), false],
            [new EntityNotFoundException(), false],
        ];
    }

    /**
     * @dataProvider customerWithoutAutoPayDataProvider
     */
    public function test_autopay_disabled($aPay = 'No'): void
    {
        $this->customer = CustomerData::getTestEntityData(1, [
            'aPay' => $aPay,
            'autoPayPaymentProfileID' => null,
        ])->firstOrFail();
        $account = $this->getAccount();

        $this->customerRepositoryMock
            ->shouldReceive('office')
            ->with($account->office_id)
            ->once()
            ->andReturnSelf();

        $this->customerRepositoryMock
            ->shouldReceive('withRelated')
            ->with(['subscriptions.serviceType'])
            ->once()
            ->andReturnSelf();

        $this->customerRepositoryMock
            ->shouldReceive('find')
            ->with($account->account_number)
            ->once()
            ->andReturn($this->customer);

        $this
            ->paymentProfileRepositoryMock
            ->expects('getPaymentProfile')
            ->withAnyArgs()
            ->never();

        $autopayData = $this->customerService->getCustomerAutoPayData($account);

        $this->assertCount(1, $autopayData);
        $this->assertFalse($autopayData[0]->isEnabled);
        $this->assertNull($autopayData[0]->planName);
        $this->assertNull($autopayData[0]->cardType);
        $this->assertNull($autopayData[0]->cardLastFour);
        $this->assertNull($autopayData[0]->nextPaymentAmount);
        $this->assertNull($autopayData[0]->nextPaymentDate);
        $this->assertNull($autopayData[0]->preferredBillingDate);
    }

    public function customerWithoutAutoPayDataProvider(): array
    {
        return [
            'no autopay' => [
                'aPay' => 'No',
            ],
            'empty payment profile' => [
                'aPay' => 'CC',
            ],
        ];
    }

    /**
     * @param DateTimeInterface $currentTestDate
     * @param int $customerPreferredBillingDay
     * @param string|null $expectedBillingDate
     *
     * @dataProvider autopayPreferredBillingDateProvider
     */
    public function test_autopay_enabled_using_pestroutes(
        DateTimeInterface $currentTestDate,
        int $customerPreferredBillingDay,
        ?string $expectedBillingDate,
    ): void {
        Carbon::setTestNow($currentTestDate);

        $account = $this->getAccount();
        /** @var PaymentProfileModel $paymentProfile */
        $paymentProfile = PaymentProfileData::getTestEntityData()->firstOrFail();

        $activeRecurringCharge = 10.11;
        $frozenRecurringCharge = 20.22;

        $subscriptionsData = [
            [
                'recurringCharge' => $activeRecurringCharge,
                'active' => '1',
                'activeText' => 'Active',
                'serviceID' => ServiceTypeData::PRO,
                'nextBillingDate' => '2023-05-01',
            ],
            [
                'recurringCharge' => $activeRecurringCharge,
                'active' => '1',
                'activeText' => 'Active',
                'serviceID' => ServiceTypeData::PREMIUM,
                'nextBillingDate' => '2023-05-02',
            ],
            [
                'recurringCharge' => $frozenRecurringCharge,
                'active' => '0',
                'activeText' => 'Frozen',
                'serviceID' => ServiceTypeData::RESERVICE,
                'nextBillingDate' => '2023-05-03',
            ],
        ];

        $this->setUpCustomerAndRepository(
            $customerPreferredBillingDay,
            $subscriptionsData,
            $account
        );

        $this->paymentProfileRepositoryMock
            ->shouldReceive('office')
            ->with($this->customer->officeId)
            ->once()
            ->andReturnSelf();

        $this->paymentProfileRepositoryMock
            ->shouldReceive('find')
            ->with($this->customer->autoPayPaymentProfileId)
            ->once()
            ->andReturn($paymentProfile);

        $autopayData = $this->customerService->getCustomerAutoPayData($account, false);

        $this->assertCount(2, $autopayData);

        foreach ($autopayData as $index => $autopay) {
            $expectedPlanName = ServiceTypeData::SERVICE_NAMES[$subscriptionsData[$index]['serviceID']];
            $this->assertTrue($autopay->isEnabled);
            $this->assertSame($expectedPlanName, $autopay->planName);
            $this->assertSame($paymentProfile->cardType, ucfirst(strtolower($autopay->cardType)));
            $this->assertSame($paymentProfile->cardLastFour, $autopay->cardLastFour);
            $this->assertEquals($activeRecurringCharge, $autopay->nextPaymentAmount);
            $this->assertEquals(
                $subscriptionsData[$index]['nextBillingDate'],
                $autopay->nextPaymentDate->format('Y-m-d')
            );
            $this->assertSame($expectedBillingDate, $autopay->preferredBillingDate);
        }

        Carbon::setTestNow();
    }

    /**
     * @dataProvider autopayPreferredBillingDateProvider
     */
    public function test_autopay_enabled_using_payment_service_api(
        DateTimeInterface $currentTestDate,
        int $customerPreferredBillingDay,
        ?string $expectedBillingDate,
    ): void {
        Carbon::setTestNow($currentTestDate);

        $account = $this->getAccount();

        $activeRecurringCharge = 10.11;
        $frozenRecurringCharge = 20.22;

        $subscriptionsData = [
            [
                'recurringCharge' => $activeRecurringCharge,
                'active' => '1',
                'activeText' => 'Active',
                'serviceID' => ServiceTypeData::PRO,
                'nextBillingDate' => '2023-05-01',
            ],
            [
                'recurringCharge' => $activeRecurringCharge,
                'active' => '1',
                'activeText' => 'Active',
                'serviceID' => ServiceTypeData::PREMIUM,
                'nextBillingDate' => '2023-05-02',
            ],
            [
                'recurringCharge' => $frozenRecurringCharge,
                'active' => '0',
                'activeText' => 'Frozen',
                'serviceID' => ServiceTypeData::RESERVICE,
                'nextBillingDate' => '2023-05-03',
            ],
        ];

        $paymentMethodsData = [
            new CreditCardPaymentMethod(
                paymentMethodId: $this->getTestPaymentMethodUuid(),
                crmAccountId: $this->getTestCrmAccountUuid(),
                type: PaymentMethodEnum::CREDIT_CARD->value,
                dateAdded: "2023-11-16 10:48:58",
                isPrimary: true,
                isAutoPay: true,
                ccLastFour: '1111'
            ),
            new AchPaymentMethod(
                paymentMethodId: $this->getTestPaymentMethodUuid(),
                crmAccountId: $this->getTestCrmAccountUuid(),
                type: PaymentMethodEnum::CREDIT_CARD->value,
                dateAdded: "2023-11-16 10:48:58",
                isPrimary: false,
                isAutoPay: false,
                achAccountLastFour: '1111',
                achRoutingNumber: '985612814',
                achAccountType: 'personal_checking',
                achBankName: 'Universal Bank',
            ),
        ];

        $this->setUpCustomerAndRepository(
            $customerPreferredBillingDay,
            $subscriptionsData,
            $account
        );

        $this->aptivePaymentRepository
            ->expects('getPaymentMethodsList')
            ->with(PaymentMethodsListRequestDTO::class)
            ->andReturn($paymentMethodsData);

        $autopayData = $this->customerService->getCustomerAutoPayData($account, true);

        $this->assertCount(2, $autopayData);

        foreach ($autopayData as $index => $autopay) {
            $expectedPlanName = ServiceTypeData::SERVICE_NAMES[$subscriptionsData[$index]['serviceID']];
            $this->assertTrue($autopay->isEnabled);
            $this->assertSame($expectedPlanName, $autopay->planName);
            $this->assertSame($paymentMethodsData[0]->type, $autopay->cardType);
            $this->assertSame($paymentMethodsData[0]->ccLastFour, $autopay->cardLastFour);
            $this->assertEquals($activeRecurringCharge, $autopay->nextPaymentAmount);
            $this->assertEquals(
                $subscriptionsData[$index]['nextBillingDate'],
                $autopay->nextPaymentDate->format('Y-m-d')
            );
            $this->assertSame($expectedBillingDate, $autopay->preferredBillingDate);
        }

        Carbon::setTestNow();
    }

    /**
     * @dataProvider autopayPreferredBillingDateProvider
     */
    public function test_autopay_enabled_returns_disabled_if_no_payment_profile_exists_using_payment_service_api(
        DateTimeInterface $currentTestDate,
        int $customerPreferredBillingDay,
        ?string $expectedBillingDate,
    ): void {
        Carbon::setTestNow($currentTestDate);

        $account = $this->getAccount();

        $activeRecurringCharge = 10.11;
        $frozenRecurringCharge = 20.22;

        $subscriptionsData = [
            [
                'recurringCharge' => $activeRecurringCharge,
                'active' => '1',
                'activeText' => 'Active',
                'serviceID' => ServiceTypeData::PRO,
                'nextBillingDate' => '2023-05-01',
            ],
            [
                'recurringCharge' => $activeRecurringCharge,
                'active' => '1',
                'activeText' => 'Active',
                'serviceID' => ServiceTypeData::PREMIUM,
                'nextBillingDate' => '2023-05-02',
            ],
            [
                'recurringCharge' => $frozenRecurringCharge,
                'active' => '0',
                'activeText' => 'Frozen',
                'serviceID' => ServiceTypeData::RESERVICE,
                'nextBillingDate' => '2023-05-03',
            ],
        ];

        $this->setUpCustomerAndRepository(
            $customerPreferredBillingDay,
            $subscriptionsData,
            $account
        );

        $this->aptivePaymentRepository
            ->expects('getPaymentMethodsList')
            ->with(PaymentMethodsListRequestDTO::class)
            ->andReturn([]);

        $autopayData = $this->customerService->getCustomerAutoPayData($account, true);

        $this->assertCount(1, $autopayData);

        foreach ($autopayData as $autopay) {
            $this->assertFalse($autopay->isEnabled);
        }

        Carbon::setTestNow();
    }

    public function test_autopay_throws_exception_with_invalid_preferred_day(): void
    {
        $account = $this->getAccount();
        $subscriptionsData = [[], []];

        $this->setUpCustomerAndRepository(32, $subscriptionsData, $account);

        $this
            ->paymentProfileRepositoryMock
            ->expects('getPaymentProfile')
            ->withAnyArgs()
            ->never();

        $this->expectException(InvalidArgumentException::class);

        $this->customerService->getCustomerAutoPayData($account);
    }

    public function autopayPreferredBillingDateProvider(): iterable
    {
        $feb28 = 'February 28th';

        yield 'No preference' => [
            Carbon::create(2022, 1, 2),
            -1,
            null,
        ];
        yield 'No preference again' => [
            Carbon::create(2022, 1, 2),
            0,
            null,
        ];
        yield 'Tomorrow' => [
            Carbon::create(2022, 1, 1),
            2,
            'January 2nd',
        ];
        yield 'Today' => [
            Carbon::create(2022, 1, 2),
            2,
            'January 2nd',
        ];
        yield 'Yesterday' => [
            Carbon::create(2022, 1, 3),
            2,
            'February 2nd',
        ];
        yield 'Next month' => [
            Carbon::create(2022, 1, 31),
            28,
            $feb28,
        ];
        yield 'Last day of February' => [
            Carbon::create(2022, 1, 31),
            29,
            $feb28,
        ];
        yield 'Also last day of February' => [
            Carbon::create(2022, 1, 31),
            30,
            $feb28,
        ];
        yield 'Leap year' => [
            Carbon::create(2024, 1, 31),
            30,
            'February 29th',
        ];
    }

    public function test_get_customers_collection_for_user()
    {
        $accountsCollection = Account::factory()
            ->count(3)
            ->state(new Sequence(
                [
                    'office_id' => 1,
                    'account_number' => 1,
                ],
                [
                    'office_id' => 1,
                    'account_number' => 2,
                ],
                [
                    'office_id' => 2,
                    'account_number' => 3,
                ],
            ))->make();

        $user = new User();
        $user->accounts = $accountsCollection;

        $customersCollection = CustomerData::getTestEntityData(
            3,
            [
                'officeID' => 1,
                'customerID' => 1,
            ],
            [
                'officeID' => 1,
                'customerID' => 2,
            ],
            [
                'officeID' => 2,
                'customerID' => 3,
            ],
        );

        $this->customerRepositoryMock->shouldReceive('office')
            ->with(0)
            ->once()
            ->andReturnSelf();

        $this->customerRepositoryMock
            ->shouldReceive('search')
            ->withArgs(
                fn (SearchCustomersDTO $dto) => array_values($dto->officeIds) === [1, 2]
                    && array_values($dto->accountNumbers) === [1, 2, 3]
                    && $dto->isActive === true
            )->once()
            ->andReturn($customersCollection);

        $result = $this->customerService->getActiveCustomersCollectionForUser($user);

        self::assertInstanceOf(Collection::class, $result);
        self::assertSame($customersCollection, $result);
    }

    public function test_get_customers_collection_for_user_returns_empty_collection_for_user_without_accounts()
    {
        $emptyAccountsCollection = new Collection([]);

        $user = new User();
        $user->accounts = $emptyAccountsCollection;

        $this->customerRepositoryMock->shouldNotReceive('searchCustomers');

        $result = $this->customerService->getActiveCustomersCollectionForUser($user);

        self::assertInstanceOf(Collection::class, $result);
        self::assertEmpty($result);
    }

    /**
     * @dataProvider isCustomerWithGivenEmailExistsDataProvider
     */
    public function test_is_customer_with_given_email_exists(int $customersAmount, bool $expectedResult)
    {
        $officeIds = range(1, 100);

        $customers = $customersAmount > 0
            ? CustomerData::getTestEntityData($customersAmount)
            : new Collection();

        $this->customerRepositoryMock
            ->shouldReceive('office')
            ->with(0)
            ->andReturnSelf();

        $this->customerRepositoryMock
            ->shouldReceive('searchActiveCustomersByEmail')
            ->withArgs([self::EMAIL, $officeIds])
            ->andReturn($customers);

        $this->officeRepositoryMock
            ->shouldReceive('getAllOfficeIds')
            ->once()
            ->andReturn($officeIds);

        $result = $this->customerService->isCustomerWithGivenEmailExists(self::EMAIL);
        self::assertEquals($expectedResult, $result);
    }

    /**
     * @return iterable<int, array<int, mixed>>
     */
    public function isCustomerWithGivenEmailExistsDataProvider(): iterable
    {
        yield [1, true];
        yield [5, true];
        yield [0, false];
    }

    private function getUpdateCommunicationPreferencesDto(): UpdateCommunicationPreferencesDTO
    {
        return new UpdateCommunicationPreferencesDTO(
            officeId: $this->getTestOfficeId(),
            accountNumber: $this->getTestAccountNumber(),
            smsReminders: random_int(0, 1) === 1,
            emailReminders: random_int(0, 1) === 1,
            phoneReminders: random_int(0, 1) === 1,
        );
    }

    private function getAccount(): Account
    {
        return new Account([
            'office_id' => $this->getTestOfficeId(),
            'account_number' => $this->getTestAccountNumber(),
        ]);
    }

    private function setUpCustomerAndRepository(
        int $customerPreferredBillingDay,
        array $subscriptionsData,
        Account $account
    ): void {
        $this->customer = CustomerData::getTestEntityData(1, [
            'preferredBillingDate' => (string) $customerPreferredBillingDay,
        ])->firstOrFail();

        $subscriptions = SubscriptionData::getTestEntityData(
            count($subscriptionsData),
            ...$subscriptionsData
        );

        $subscriptions->each(
            fn (SubscriptionModel $subscription) => $subscription->setRelated(
                'serviceType',
                ServiceTypeData::getTestEntityDataOfTypes($subscription->serviceId)->first()
            )
        );

        $this->customer->setRelated('subscriptions', $subscriptions);

        $this->customerRepositoryMock
            ->shouldReceive('office')
            ->with($account->office_id)
            ->andReturnSelf();

        $this->customerRepositoryMock
            ->shouldReceive('withRelated')
            ->with(['subscriptions.serviceType'])
            ->andReturnSelf();

        $this->customerRepositoryMock
            ->expects('find')
            ->with($account->account_number)
            ->andReturn($this->customer);
    }
}
