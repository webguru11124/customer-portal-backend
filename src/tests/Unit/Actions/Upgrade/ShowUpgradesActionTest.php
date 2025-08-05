<?php

namespace Tests\Unit\Actions\Upgrade;

use App\Actions\Upgrade\ShowUpgradesAction;
use App\DTO\PlanBuilder\Plan;
use App\DTO\PlanBuilder\PlanServiceFrequency;
use App\DTO\PlanBuilder\Product;
use App\Exceptions\Account\AccountNotFoundException;
use App\Exceptions\Entity\EntityNotFoundException;
use App\Exceptions\PlanBuilder\FieldNotFound;
use App\Exceptions\Subscription\SubscriptionNotFound;
use App\Interfaces\Repository\CustomerRepository;
use App\Models\Account;
use App\Models\External\CustomerModel;
use App\Services\AccountService;
use App\Services\PlanBuilderService;
use Aptive\PestRoutesSDK\Resources\Tickets\Ticket;
use Aptive\PestRoutesSDK\Resources\Tickets\TicketTemplateType;
use DateTime;
use Illuminate\Support\Collection;
use Mockery;
use Mockery\MockInterface;
use Tests\Data\CustomerData;
use Tests\Data\PlanBuilderResponseData;
use Tests\Data\SubscriptionData;
use Tests\TestCase;
use Tests\Traits\RandomIntTestData;

class ShowUpgradesActionTest extends TestCase
{
    use RandomIntTestData;

    protected MockInterface|AccountService $accountServiceMock;
    protected MockInterface|CustomerRepository $customerRepositoryMock;
    protected MockInterface|PlanBuilderService $planBuilderServiceMock;
    protected ShowUpgradesAction $action;

    protected Account $accountModel;
    protected CustomerModel $customer;
    protected string $planString;
    protected string $upgradesString;

    public function setUp(): void
    {
        parent::setUp();

        $this->accountServiceMock = Mockery::mock(AccountService::class);
        $this->customerRepositoryMock = Mockery::mock(CustomerRepository::class);
        $this->planBuilderServiceMock = Mockery::mock(PlanBuilderService::class);
        $this->action = new ShowUpgradesAction(
            $this->accountServiceMock,
            $this->customerRepositoryMock,
            $this->planBuilderServiceMock
        );
        $this->accountModel = Account::factory()->makeOne([
            'account_number' => $this->getTestAccountNumber(),
            'office_id' => $this->getTestOfficeId(),
        ]);
        $this->customer = CustomerData::getTestEntityData(
            1,
            [
                'customerID' => $this->getTestAccountNumber(),
                'officeID' => $this->getTestOfficeId(),
            ]
        )->first();

        $this->planString = '{"id":31,"ext_reference_id":1799,"name":"Basic","start_on":"2023-02-01","end_on":"2080-09-01","plan_service_frequency_id":9,"plan_status_id":2,"bill_monthly":true,"initial_discount":20,"recurring_discount":10,"company_id":1,"created_at":"2023-08-24T23:17:22.000000Z","updated_at":"2023-10-26T10:14:14.000000Z","order":0,"plan_category_ids":[8],"default_area_plan":{"id":863,"area_id":1,"plan_id":31,"created_at":"2023-08-24T23:17:22.000000Z","updated_at":"2023-08-24T23:17:22.000000Z","can_sell_percentage_threshold":20,"service_product_ids":[],"area_plan_pricings":[{"id":64358,"plan_pricing_level_id":8,"area_plan_id":863,"initial_min":49,"initial_max":399,"recurring_min":135,"recurring_max":159,"created_at":null,"updated_at":null}],"addons":        [{"id":235120,"area_plan_id":863,"product_id":4,"is_recurring":true,"initial_min":0,"initial_max":0,"recurring_min":0,"recurring_max":15,"created_at":null,"updated_at":null},        {"id":235121,"area_plan_id":863,"product_id":5,"is_recurring":true,"initial_min":0,"initial_max":0,"recurring_min":15,"recurring_max":45,"created_at":null,"updated_at":null},        {"id":235125,"area_plan_id":863,"product_id":13,"is_recurring":true,"initial_min":0,"initial_max":0,"recurring_min":5,"recurring_max":15,"created_at":null,"updated_at":null},        {"id":235130,"area_plan_id":863,"product_id":2,"is_recurring":true,"initial_min":149,"initial_max":149,"recurring_min":58,"recurring_max":99,"created_at":null,"updated_at":null}]},"area_plans":[],"agreement_length_ids":[1,2,3]}';

        $this->upgradesString = '{"1-22":{"id":22,"ext_reference_id":2827,"name":"Pro","start_on":"2023-01-01","end_on":"2080-01-01","plan_service_frequency_id":2,"plan_status_id":2,"bill_monthly":false,"initial_discount":10,"recurring_discount":10,"company_id":1,"created_at":"2023-08-24T23:15:12.000000Z","updated_at":"2023-10-26T10:16:20.000000Z","order":1,"plan_category_ids":[8],"default_area_plan":{"id":293,"area_id":1,"plan_id":22,"created_at":"2023-08-24T23:15:13.000000Z","updated_at":"2023-08-24T23:15:13.000000Z","can_sell_percentage_threshold":null,"service_product_ids":[19,20],"area_plan_pricings":[{"id":62648,"plan_pricing_level_id":8,"area_plan_id":293,"initial_min":39,"initial_max":399,"recurring_min":125,"recurring_max":149,"created_at":null,"updated_at":null}],"addons":{"1":{"id":229055,"area_plan_id":293,"product_id":4,"is_recurring":true,"initial_min":0,"initial_max":0,"recurring_min":0,"recurring_max":15,"created_at":null,"updated_at":null},"2":{"id":229056,"area_plan_id":293,"product_id":5,"is_recurring":true,"initial_min":0,"initial_max":0,"recurring_min":15,"recurring_max":45,"created_at":null,"updated_at":null},"3":{"id":229057,"area_plan_id":293,"product_id":9,"is_recurring":true,"initial_min":0,"initial_max":0,"recurring_min":49,"recurring_max":99,"created_at":null,"updated_at":null},"7":{"id":229061,"area_plan_id":293,"product_id":13,"is_recurring":true,"initial_min":0,"initial_max":0,"recurring_min":5,"recurring_max":15,"created_at":null,"updated_at":null}}},"area_plans":[],"agreement_length_ids":[1,2,3]},"2-25":{"id":25,"ext_reference_id":1800,"name":"Pro +","start_on":"2023-01-01","end_on":"2080-01-01","plan_service_frequency_id":2,"plan_status_id":2,"bill_monthly":true,"initial_discount":0,"recurring_discount":10,"company_id":1,"created_at":"2023-08-24T23:15:52.000000Z","updated_at":"2023-10-26T10:16:59.000000Z","order":2,"plan_category_ids":[8],"default_area_plan":{"id":487,"area_id":1,"plan_id":25,"created_at":"2023-08-24T23:15:52.000000Z","updated_at":"2023-08-24T23:15:52.000000Z","can_sell_percentage_threshold":null,"service_product_ids":[5,13,4,19,20],"area_plan_pricings":[{"id":63230,"plan_pricing_level_id":8,"area_plan_id":487,"initial_min":29,"initial_max":399,"recurring_min":145,"recurring_max":169,"created_at":null,"updated_at":null}],"addons":{"1":{"id":231545,"area_plan_id":487,"product_id":9,"is_recurring":true,"initial_min":0,"initial_max":0,"recurring_min":49,"recurring_max":99,"created_at":null,"updated_at":null},"9":{"id":231553,"area_plan_id":487,"product_id":2,"is_recurring":true,"initial_min":149,"initial_max":149,"recurring_min":59,"recurring_max":99,"created_at":null,"updated_at":null}}},"area_plans":[],"agreement_length_ids":[1,3,2]},"4-28":{"id":28,"ext_reference_id":2828,"name":"Premium","start_on":"2023-01-01","end_on":"2080-01-01","plan_service_frequency_id":6,"plan_status_id":2,"bill_monthly":false,"initial_discount":0,"recurring_discount":10,"company_id":1,"created_at":"2023-08-24T23:16:58.000000Z","updated_at":"2023-10-26T10:17:16.000000Z","order":4,"plan_category_ids":[8],"default_area_plan":{"id":688,"area_id":1,"plan_id":28,"created_at":"2023-08-24T23:16:59.000000Z","updated_at":"2023-08-24T23:16:59.000000Z","can_sell_percentage_threshold":null,"service_product_ids":[13,4,5,9,20,19],"area_plan_pricings":[{"id":63833,"plan_pricing_level_id":8,"area_plan_id":688,"initial_min":29,"initial_max":399,"recurring_min":155,"recurring_max":209,"created_at":null,"updated_at":null}],"addons":{"1":{"id":233535,"area_plan_id":688,"product_id":2,"is_recurring":true,"initial_min":149,"initial_max":149,"recurring_min":0,"recurring_max":99,"created_at":null,"updated_at":null}}},"area_plans":[],"agreement_length_ids":[1,2,3]}}';
    }

    public function test_action_returns_valid_data()
    {
        $subscription = SubscriptionData::getTestEntityData(1, [
            'subscriptionID' => $this->getTestSubscriptionId(),
            'officeID' => $this->accountModel->office_id,
            'customerID' => $this->accountModel->account_number,
            'serviceID' => $this->getTestServiceId(),
        ])->first();
        $this->customer->setRelated('subscriptions', new Collection([$subscription]));

        $this->setupAccountServiceToReturnValidAccount($this->accountModel);
        $this->setupCustomerRepositoryToReturnValidCustomer($this->customer);

        $planData = json_decode(json: $this->planString, associative: false, flags: JSON_THROW_ON_ERROR);
        $plan = Plan::fromApiResponse($planData);
        $this->planBuilderServiceMock
            ->shouldReceive('getServicePlan')
            ->withArgs([$this->getTestServiceId(), $this->getTestOfficeId()])
            ->andReturn($plan)
            ->once();

        $this->planBuilderServiceMock
            ->shouldReceive('getProducts')
            ->with($this->getTestOfficeId())
            ->once();

        $upgradesData = json_decode(json: $this->upgradesString, associative: false, flags: JSON_THROW_ON_ERROR);
        $plans = array_map(
            static fn (object $plan) => Plan::fromApiResponse($plan),
            (array) $upgradesData
        );
        $this->planBuilderServiceMock
            ->shouldReceive('getUpgradesForServicePlan')
            ->withArgs([$this->getTestServiceId(), $this->getTestOfficeId()])
            ->andReturn($plans)
            ->once();
        $settings = PlanBuilderResponseData::getSettingsResponse();
        $serviceFrequencies = $settings['planServiceFrequencies'];
        $this->planBuilderServiceMock
            ->shouldReceive('getServiceFrequencies')
            ->andReturn($serviceFrequencies)
            ->once();

        $result = ($this->action)($this->getTestAccountNumber());
        self::assertIsArray($result);
        self::assertArrayHasKey('current', $result);
        self::assertArrayHasKey('upgrades', $result);
        self::assertArrayHasKey('upgrades', $result);
        self::assertArrayHasKey('hasDiscountToPro', $result);
        self::assertTrue($result['hasDiscountToPro']);
        self::assertInstanceOf(Plan::class, $result['current']);
        self::assertInstanceOf(Plan::class, array_pop($result['upgrades']));
        self::assertInstanceOf(PlanServiceFrequency::class, array_pop($result['serviceFrequencies']));
    }

    public function test_action_marks_addons_that_are_already_bought_by_customer(): void
    {
        $subscription = SubscriptionData::getTestEntityData(1, [
            'subscriptionID' => $this->getTestSubscriptionId(),
            'officeID' => $this->accountModel->office_id,
            'customerID' => $this->accountModel->account_number,
            'serviceID' => $this->getTestServiceId(),
        ])->first();
        $subscription->recurringTicket = $this->getRecurringTickets();
        $this->customer->setRelated('subscriptions', new Collection([$subscription]));

        $this->setupAccountServiceToReturnValidAccount($this->accountModel);
        $this->setupCustomerRepositoryToReturnValidCustomer($this->customer);

        $planData = json_decode(json: $this->planString, associative: false, flags: JSON_THROW_ON_ERROR);
        $planData->default_area_plan->service_product_ids = [4];
        $plan = Plan::fromApiResponse($planData);

        $this->planBuilderServiceMock
            ->shouldReceive('getServicePlan')
            ->withArgs([$this->getTestServiceId(), $this->getTestOfficeId()])
            ->andReturn($plan)
            ->once();

        $product1Object = (object) [
            'id' => 4,
            'product_sub_category_id' => 1,
            'ext_reference_id' => 244,
            'name' => 'Product 1',
            'order' => 1,
            'image' => 'image.jpg',
            'is_recurring' => true,
            'initial_min' => 0,
            'initial_max' => 0,
            'recurring_min' => 0,
            'recurring_max' => 15,
            'company_id' => 1,
            'created_at' => '2021-01-01',
            'updated_at' => '2021-01-01',
            'needs_customer_support' => false,
            'description' => null,
            'image_name' => 'image.jpg',
        ];
        $product2Object = clone $product1Object;
        $product2Object->id = 13;
        $product2Object->ext_reference_id = 245;

        $this->planBuilderServiceMock
            ->shouldReceive('getProducts')
            ->with($this->getTestOfficeId())
            ->andReturn([
                Product::fromApiResponse($product1Object),
                Product::fromApiResponse($product2Object),
            ])
            ->once();

        $upgradesData = json_decode(json: $this->upgradesString, associative: false, flags: JSON_THROW_ON_ERROR);
        $plans = array_map(
            static fn (object $plan) => Plan::fromApiResponse($plan),
            (array) $upgradesData
        );
        $this->planBuilderServiceMock
            ->shouldReceive('getUpgradesForServicePlan')
            ->withArgs([$this->getTestServiceId(), $this->getTestOfficeId()])
            ->andReturn($plans)
            ->once();
        $settings = PlanBuilderResponseData::getSettingsResponse();
        $serviceFrequencies = $settings['planServiceFrequencies'];
        $this->planBuilderServiceMock
            ->shouldReceive('getServiceFrequencies')
            ->andReturn($serviceFrequencies)
            ->once();

        $result = ($this->action)($this->getTestAccountNumber());
        $this->assertContains(4, $result['current']->defaultAreaPlan->serviceProductIds);
        $this->assertNotContains(13, $result['current']->defaultAreaPlan->serviceProductIds);
        $this->assertNotContains(5, $result['current']->defaultAreaPlan->serviceProductIds);
        $this->assertCount(4, $result['current']->defaultAreaPlan->addons);
        $this->assertCount(2, $result["purchasedAddons"]);
        $this->assertArrayHasKey(4, $result['purchasedAddons']);
        $this->assertEquals(244, $result['purchasedAddons'][4]);
    }

    public function test_action_throws_exception_when_no_active_subscriptions()
    {
        $subscription = SubscriptionData::getTestEntityData(1, [
            'subscriptionID' => $this->getTestSubscriptionId(),
            'officeID' => $this->accountModel->office_id,
            'customerID' => $this->accountModel->account_number,
            'serviceID' => $this->getTestServiceId(),
            'active' => '0',
        ])->first();
        $this->customer->setRelated('subscriptions', new Collection([$subscription]));

        $this->setupAccountServiceToReturnValidAccount($this->accountModel);
        $this->setupCustomerRepositoryToReturnValidCustomer($this->customer);

        self::expectException(SubscriptionNotFound::class);
        ($this->action)($this->getTestAccountNumber());
    }

    public function test_action_throws_exception_when_plan_builder_service_throws_exception()
    {
        $subscription = SubscriptionData::getTestEntityData(1, [
            'subscriptionID' => $this->getTestSubscriptionId(),
            'officeID' => $this->accountModel->office_id,
            'customerID' => $this->accountModel->account_number,
            'serviceID' => $this->getTestServiceId(),
        ])->first();
        $this->customer->setRelated('subscriptions', new Collection([$subscription]));

        $this->setupAccountServiceToReturnValidAccount($this->accountModel);
        $this->setupCustomerRepositoryToReturnValidCustomer($this->customer);

        $this->planBuilderServiceMock
            ->shouldReceive('getServicePlan')
            ->withArgs([$this->getTestServiceId(), $this->getTestOfficeId()])
            ->andThrow(FieldNotFound::class)
            ->once();

        self::expectException(FieldNotFound::class);
        ($this->action)($this->getTestAccountNumber());
    }

    public function test_action_throws_exception_when_account_cervice_service_throws_exception()
    {
        $this->accountServiceMock
            ->shouldReceive('getAccountByAccountNumber')
            ->with($this->getTestAccountNumber())
            ->andThrow(new AccountNotFoundException())
            ->once();

        self::expectException(AccountNotFoundException::class);
        ($this->action)($this->getTestAccountNumber());
    }

    public function test_action_throws_exception_when_customer_repository_throws_exception()
    {
        $this->setupAccountServiceToReturnValidAccount($this->accountModel);

        $this->customerRepositoryMock
            ->shouldReceive('office')
            ->with($this->getTestOfficeId())
            ->andReturnSelf()
            ->once();
        $this->customerRepositoryMock
            ->shouldReceive('withRelated')
            ->withArgs([['subscriptions']])
            ->andReturn($this->customerRepositoryMock)
            ->once();

        $this->customerRepositoryMock
            ->shouldReceive('find')
            ->with($this->getTestAccountNumber())
            ->andThrow(new EntityNotFoundException())
            ->once();

        self::expectException(EntityNotFoundException::class);
        ($this->action)($this->getTestAccountNumber());
    }

    protected function setupAccountServiceToReturnValidAccount($accountModel): void
    {
        $this->accountServiceMock
            ->shouldReceive('getAccountByAccountNumber')
            ->with($this->getTestAccountNumber())
            ->andReturn($accountModel)
            ->once();
    }

    protected function setupCustomerRepositoryToReturnValidCustomer($customer): void
    {
        $this->customerRepositoryMock
            ->shouldReceive('office')
            ->with($this->getTestOfficeId())
            ->andReturnSelf()
            ->once();

        $this->customerRepositoryMock
            ->shouldReceive('withRelated')
            ->withArgs([['subscriptions']])
            ->andReturn($this->customerRepositoryMock)
            ->once();
        $this->customerRepositoryMock
            ->shouldReceive('find')
            ->with($this->getTestAccountNumber())
            ->andReturn($customer)
            ->once();
    }

    protected function getRecurringTickets(): Ticket
    {
        return Ticket::fromApiObject((object) [
            'ticketID' => 1,
            'customerID' => $this->accountModel->account_number,
            'billToAccountID' => 1,
            'officeID' => $this->accountModel->office_id,
            'dateCreated' => new DateTime(),
            'invoiceDate' => new DateTime(),
            'dateUpdated' => new DateTime(),
            'active' => 1,
            'subTotal' => 100,
            'taxAmount' => 10,
            'total' => 110,
            'serviceCharge' => 0,
            'serviceTaxable' => '1',
            'productionValue' => 0,
            'taxRate' => 1,
            'appointmentID' => null,
            'balance' => 50,
            'subscriptionID' => null,
            'autoGenerated' => null,
            'autoGeneratedType' => null,
            'renewalID' => null,
            'serviceID' => 155,
            'items' => [
                (object) [
                    'itemID' => 1,
                    'ticketID' => '1',
                    'description' => '',
                    'quantity' => '4',
                    'amount' => '50',
                    'isTaxable' => '1',
                    'creditTo' => '5',
                    'productID' => 244,
                    'taxable' => '1',
                    'unitID' => 1
                ],
                (object) [
                    'itemID' => 2,
                    'ticketID' => '1',
                    'description' => '',
                    'quantity' => '4',
                    'amount' => '50',
                    'isTaxable' => '1',
                    'creditTo' => '5',
                    'productID' => 245,
                    'taxable' => '1',
                    'unitID' => 1
                ],
            ],
            'invoiceNumber' => 5434,
            'templateType' => TicketTemplateType::Recurring->value,
            'glNumber' => null
        ]);
    }
}
