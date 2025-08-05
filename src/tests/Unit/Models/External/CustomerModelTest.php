<?php

declare(strict_types=1);

namespace Tests\Unit\Models\External;

use App\Exceptions\Entity\RelationNotLoadedException;
use App\Interfaces\Repository\CustomerRepository;
use App\Models\External\CustomerModel;
use Aptive\PestRoutesSDK\Resources\Customers\CustomerPhone;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Tests\Data\AppointmentData;
use Tests\Data\CustomerData;
use Tests\Data\SubscriptionData;
use Tests\TestCase;

class CustomerModelTest extends TestCase
{
    private const RELATION_NAME_SUBSCRIPTIONS = 'subscriptions';
    private const RELATION_NAME_APPOINTMENTS = 'appointments';
    private const MONTHLY_BILLING_FREQUENCY = 30;
    private const EVERY_4_WEEKS_BILLING_FREQUENCY = 28;

    protected CustomerModel $subject;
    protected Collection $subscriptions;
    protected Collection $appointments;

    public function setUp(): void
    {
        parent::setUp();

        $this->subject = CustomerData::getTestEntityData()->first();
        $this->subscriptions = SubscriptionData::getTestEntityData();
        $this->appointments = AppointmentData::getTestEntityData(2);
    }

    public function test_it_throws_exception_when_trying_to_get_non_loaded_suscriptions(): void
    {
        $this->expectException(RelationNotLoadedException::class);

        $this->subject->subscriptions;
    }

    public function test_it_throws_exception_when_trying_to_get_non_loaded_appointments(): void
    {
        $this->expectException(RelationNotLoadedException::class);

        $this->subject->appointments;
    }

    public function test_can_set_and_get_subscriptions_relation(): void
    {
        $this->subject->setRelated(self::RELATION_NAME_SUBSCRIPTIONS, $this->subscriptions);

        $result = $this->subject->subscriptions;

        self::assertSame($this->subscriptions, $result);
    }

    public function test_can_set_and_get_appointments_relation(): void
    {
        $this->subject->setRelated(self::RELATION_NAME_APPOINTMENTS, $this->appointments);

        $result = $this->subject->appointments;

        self::assertSame($this->appointments, $result);
    }

    public function test_it_returns_proper_repository_class(): void
    {
        self::assertEquals(CustomerRepository::class, CustomerModel::getRepositoryClass());
    }

    /**
     * @return array<int, CustomerPhone>
     */
    private function makePhonesArray(string|null $firstPhone, string|null $secondPhone): array
    {
        $phones = array_map(
            fn (string|null $phone) => $phone ? new CustomerPhone($phone, null, false) : null,
            [$firstPhone, $secondPhone]
        );

        return array_filter($phones);
    }

    /**
     * @dataProvider phoneDataProvider
     */
    public function test_get_first_phone(string|null $firstPhone, string|null $secondPhone)
    {
        $this->subject->phones = $this->makePhonesArray($firstPhone, $secondPhone);
        $result = $this->subject->getFirstPhone();

        self::assertEquals($firstPhone, $result);
    }

    /**
     * @dataProvider phoneDataProvider
     */
    public function test_get_second_phone(string|null $firstPhone, string|null $secondPhone)
    {
        $this->subject->phones = $this->makePhonesArray($firstPhone, $secondPhone);
        $result = $this->subject->getSecondPhone();

        self::assertEquals($secondPhone, $result);
    }

    public function phoneDataProvider(): iterable
    {
        yield 'no phone' => [null, null];
        yield '1 phone' => [(string) random_int(10000000, 19999999), null];
        yield '2 phones' => [(string) random_int(10000000, 19999999), (string) random_int(10000000, 19999999)];
    }

    /**
     * @dataProvider balanceCentsTestDataProvider
     */
    public function test_get_balance_cents_test(float $responsibleBalance, float $expectedResult): void
    {
        $this->subject->responsibleBalance = $responsibleBalance;
        $result = $this->subject->getBalanceCents();

        self::assertEquals($expectedResult, $result);
    }

    public function balanceCentsTestDataProvider(): iterable
    {
        yield [0, 0];
        yield [10.25, 1025];
        yield [5.1, 510];
    }

    /**
     * @dataProvider dueDateDataProvider
     */
    public function test_get_due_date(array $serviceDates, string|null $expectedResult): void
    {
        $subscriptionsCollection = new Collection();

        foreach ($serviceDates as $date) {
            $subscriptionsCollection->add(
                SubscriptionData::getTestEntityData(
                    1,
                    ['nextService' => $date]
                )->first()
            );
        }

        $this->subject->setRelated('subscriptions', $subscriptionsCollection);

        $result = $this->subject->getDueDate();

        self::assertEquals($expectedResult, $result);
    }

    public function dueDateDataProvider(): iterable
    {
        yield 'no subscriptions' => [[], null];
        yield '1 subscription' => [
            [$nextService = Carbon::now()->addDays(random_int(2, 10))->format('Y-m-d')],
            $nextService,
        ];
        yield '2 subscriptions' => [
            [
                Carbon::now()->addDays(random_int(11, 20))->format('Y-m-d'),
                $nextService = Carbon::now()->addDays(random_int(2, 10))->format('Y-m-d'),
            ],
            $nextService,
        ];
    }

    /**
     * @dataProvider isCustomerOnMonthlyBillingDataProvider
     */
    public function test_is_customer_on_monthly_billing(array $billingFrequencies, bool $expectedResult): void
    {
        $subscriptionsCollection = new Collection();

        foreach ($billingFrequencies as $frequency) {
            $subscriptionsCollection->add(
                SubscriptionData::getTestEntityData(
                    1,
                    ['billingFrequency' => $frequency]
                )->first()
            );
        }

        $this->subject->setRelated('subscriptions', $subscriptionsCollection);

        $result = $this->subject->isOnMonthlyBilling();

        self::assertEquals($expectedResult, $result);
    }

    /**
     * @return iterable<string, array<int, mixed>>
     */
    public function isCustomerOnMonthlyBillingDataProvider(): iterable
    {
        yield 'no subscriptions' => [[], false];
        yield '1 subscription monthly billing' => [
            [self::MONTHLY_BILLING_FREQUENCY],
            true,
        ];
        yield '1 subscription non monthly billing' => [
            [self::EVERY_4_WEEKS_BILLING_FREQUENCY],
            false,
        ];
        yield '2 subscriptions both monthly billing' => [
            [
                self::MONTHLY_BILLING_FREQUENCY,
                self::MONTHLY_BILLING_FREQUENCY,
            ],
            true,
        ];
        yield '2 subscriptions no monthly billing' => [
            [
                self::EVERY_4_WEEKS_BILLING_FREQUENCY,
                self::EVERY_4_WEEKS_BILLING_FREQUENCY,
            ],
            false,
        ];
    }
}
