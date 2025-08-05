<?php

declare(strict_types=1);

namespace Tests\Unit\Http\API\V2\Controller;

use App\Actions\Upgrade\ShowUpgradesAction;
use App\DTO\PlanBuilder\Plan;
use App\DTO\PlanBuilder\PlanServiceFrequency;
use App\Exceptions\Account\AccountNotFoundException;
use App\Exceptions\Entity\EntityNotFoundException;
use App\Exceptions\PlanBuilder\FieldNotFound;
use App\Exceptions\Subscription\SubscriptionNotFound;
use Aptive\Component\Http\HttpStatus;
use Aptive\PestRoutesSDK\Resources\Tickets\Ticket;
use Mockery;
use Mockery\MockInterface;
use Tests\Data\SubscriptionData;
use Tests\Traits\ExpectedV2ResponseData;
use Tests\Traits\RandomIntTestData;
use Tests\Traits\TestAuthorizationMiddleware;
use Tests\Unit\Http\API\V1\Controller\ApiTestCase;
use Throwable;

class UpgradeControllerTest extends ApiTestCase
{
    use ExpectedV2ResponseData;
    use RandomIntTestData;
    use TestAuthorizationMiddleware;

    public ShowUpgradesAction|MockInterface $actionMock;

    public function setUp(): void
    {
        parent::setUp();

        $this->actionMock = Mockery::mock(ShowUpgradesAction::class);
        $this->instance(ShowUpgradesAction::class, $this->actionMock);
    }

    public function test_search_requires_authentication(): void
    {
        $this->checkAuthorizationMiddleware(
            fn () => $this->getJson($this->getRoute())
        );
    }

    public function test_search_requires_authorization(): void
    {
        $this->createAndLogInAuth0User();

        $this->getJson($this->getRoute())
            ->assertNotFound();
    }

    /**
     * @param array<string, scalar|scalar[]> $queryParams
     *
     * @return string
     */
    private function getRoute(): string
    {
        return route('api.v2.customer.upgrades.get', ['accountNumber' => $this->getTestAccountNumber()]);
    }

    public function test_get_returns_upgrades(): void
    {
        $currentPlanData = '{"id":22,"ext_reference_id":2827,"name":"Pro","start_on":"2023-01-01","end_on":"2080-01-01","plan_service_frequency_id":2,"plan_status_id":2,"bill_monthly":false,"initial_discount":10,"recurring_discount":10,"created_at":"2023-08-24T23:15:12.000000Z","updated_at":"2024-02-21T16:49:36.000000Z","order":1,"area_plan_pricings":{"8":{"id":62639,"plan_pricing_level_id":8,"area_plan_id":290,"initial_min":242,"initial_max":243,"recurring_min":222,"recurring_max":223,"created_at":null,"updated_at":"2024-03-20T11:47:26.000000Z"}},"plan_category_ids":[8],"default_area_plan":{"id":290,"area_id":24,"plan_id":22,"created_at":"2023-08-24T23:15:12.000000Z","updated_at":"2023-08-24T23:15:12.000000Z","can_sell_percentage_threshold":null,"service_product_ids":[19,20],"area_plan_pricings":[{"id":62639,"plan_pricing_level_id":8,"area_plan_id":290,"initial_min":242,"initial_max":243,"recurring_min":222,"recurring_max":223,"created_at":null,"updated_at":"2024-03-20T11:47:26.000000Z"}],"addons":{"1":{"id":229016,"area_plan_id":290,"product_id":2,"is_recurring":true,"initial_min":149,"initial_max":149,"recurring_min":59,"recurring_max":99,"created_at":null,"updated_at":null},"2":{"id":229017,"area_plan_id":290,"product_id":4,"is_recurring":true,"initial_min":0,"initial_max":0,"recurring_min":24,"recurring_max":15,"created_at":null,"updated_at":null},"3":{"id":229018,"area_plan_id":290,"product_id":5,"is_recurring":true,"initial_min":0,"initial_max":0,"recurring_min":16,"recurring_max":45,"created_at":null,"updated_at":null},"4":{"id":229019,"area_plan_id":290,"product_id":9,"is_recurring":true,"initial_min":0,"initial_max":0,"recurring_min":49,"recurring_max":99,"created_at":null,"updated_at":null},"8":{"id":229023,"area_plan_id":290,"product_id":13,"is_recurring":true,"initial_min":0,"initial_max":0,"recurring_min":5,"recurring_max":15,"created_at":null,"updated_at":null}}},"area_plans":[],"agreement_length_ids":[1,2,3]}';
        $planUpgradesData = '{"2-25":{"id":25,"ext_reference_id":1800,"name":"Pro +","start_on":"2023-01-01","end_on":"2080-01-01","plan_service_frequency_id":2,"plan_status_id":2,"bill_monthly":false,"initial_discount":0,"recurring_discount":10,"created_at":"2023-08-24T23:15:52.000000Z","updated_at":"2024-03-15T16:33:24.000000Z","order":2,"area_plan_pricings":{"8":{"id":63215,"plan_pricing_level_id":8,"area_plan_id":482,"initial_min":342,"initial_max":343,"recurring_min":322,"recurring_max":323,"created_at":null,"updated_at":"2024-03-20T11:49:48.000000Z"}},"plan_category_ids":[1,2,3,5,7,8],"default_area_plan":{"id":482,"area_id":24,"plan_id":25,"created_at":"2023-08-24T23:15:52.000000Z","updated_at":"2023-08-24T23:15:52.000000Z","can_sell_percentage_threshold":null,"service_product_ids":[5,13,4,19,20],"area_plan_pricings":[{"id":63215,"plan_pricing_level_id":8,"area_plan_id":482,"initial_min":342,"initial_max":343,"recurring_min":322,"recurring_max":323,"created_at":null,"updated_at":"2024-03-20T11:49:48.000000Z"}],"addons":{"1":{"id":231495,"area_plan_id":482,"product_id":9,"is_recurring":true,"initial_min":0,"initial_max":0,"recurring_min":49,"recurring_max":99,"created_at":null,"updated_at":null},"9":{"id":231503,"area_plan_id":482,"product_id":2,"is_recurring":true,"initial_min":149,"initial_max":149,"recurring_min":59,"recurring_max":99,"created_at":null,"updated_at":null}}},"area_plans":[],"agreement_length_ids":[1,3,2]},"4-28":{"id":28,"ext_reference_id":2828,"name":"Premium","start_on":"2023-01-01","end_on":"2080-01-01","plan_service_frequency_id":6,"plan_status_id":2,"bill_monthly":false,"initial_discount":0,"recurring_discount":10,"created_at":"2023-08-24T23:16:58.000000Z","updated_at":"2024-03-18T13:25:59.000000Z","order":4,"area_plan_pricings":{"8":{"id":63779,"plan_pricing_level_id":8,"area_plan_id":670,"initial_min":442,"initial_max":443,"recurring_min":422,"recurring_max":423,"created_at":null,"updated_at":"2024-03-20T11:51:35.000000Z"}},"plan_category_ids":[8],"default_area_plan":{"id":670,"area_id":24,"plan_id":28,"created_at":"2023-08-24T23:16:58.000000Z","updated_at":"2023-08-24T23:16:58.000000Z","can_sell_percentage_threshold":null,"service_product_ids":[13,4,5,9,20,19],"area_plan_pricings":[{"id":63779,"plan_pricing_level_id":8,"area_plan_id":670,"initial_min":442,"initial_max":443,"recurring_min":422,"recurring_max":423,"created_at":null,"updated_at":"2024-03-20T11:51:35.000000Z"}],"addons":{"1":{"id":233373,"area_plan_id":670,"product_id":2,"is_recurring":true,"initial_min":149,"initial_max":149,"recurring_min":0,"recurring_max":99,"created_at":null,"updated_at":null}}},"area_plans":[],"agreement_length_ids":[2,3,1]}}';
        $ticketData = '{"ticketID":"26339137","customerID":"2875303","billToAccountID":"2875303","officeID":"1","dateCreated":"2024-02-29 04:55:26","invoiceDate":"2024-02-29 00:00:00","dateUpdated":"2024-03-05 06:23:20","active":"-1","subTotal":"290.00","taxAmount":"0.00","total":"290.00","serviceCharge":"229.00","serviceTaxable":"1","productionValue":"-1.00","taxRate":"0.000000","appointmentID":"0","balance":"290.00","subscriptionID":"2966227","autoGenerated":"0","autoGeneratedType":"NA","renewalID":"0","serviceID":"2827","invoiceNumber":"26339137","templateType":"R","glNumber":"","createdBy":"527941","items":[{"itemID":"9746185","ticketID":"26339137","officeID":"1","description":"Slug, Snail and Aphid","quantity":"1","amount":"10.00","productID":"2381","serviceID":"0","taxable":"1","creditTo":"-1","unitID":null,"glNumber":"","measurementSF":"0","measurementLF":"0","prepaymentAmount":"0.00","category":"Specialty Pest","code":"","dateCreated":"2024-03-04 02:41:28","dateUpdated":"2024-03-04 03:30:46"},{"itemID":"9746590","ticketID":"26339137","officeID":"1","description":"Mosquito","quantity":"1","amount":"51.00","productID":"1615","serviceID":"0","taxable":"1","creditTo":"-1","unitID":null,"glNumber":"","measurementSF":"0","measurementLF":"0","prepaymentAmount":"0.00","category":"Specialty Pest","code":"Mosquito","dateCreated":"2024-03-05 06:02:42","dateUpdated":"2024-03-05 06:23:20"}]}';
        $purchasedAddons = [4 => 2381, 9 => 1615];
        $subscriptionData = ['initialServiceTotal' => 249];

        $this->createAndLogInAuth0UserWithAccount();
        $this->setupActionToReturnResponseData(
            $currentPlanData,
            $planUpgradesData,
            $ticketData,
            $purchasedAddons,
            $subscriptionData,
            false
        );

        $response = $this->getJson($this->getRoute());
        $response->assertOk()
            ->assertJsonPath('data.0.id', '22')
            ->assertJsonPath('data.0.type', 'Plan')
            ->assertJsonPath('data.0.attributes.name', 'Pro')
            ->assertJsonPath('data.0.attributes.frequency', '6-7')
            ->assertJsonPath('data.0.attributes.initial_price', 249)
            ->assertJsonPath('data.0.attributes.recurring_price', 229)
            ->assertJsonPath('data.0.attributes.addons.0.id', '229016')
            ->assertJsonPath('data.0.attributes.addons.0.attributes.addon_id', 229016)
            ->assertJsonPath('data.0.attributes.addons.1.attributes.product_id', 4)
            ->assertJsonPath('data.0.attributes.addons.1.attributes.purchased', true)
            ->assertJsonPath('data.0.attributes.addons.3.attributes.initial_price', 0)
            ->assertJsonPath('data.0.attributes.addons.2.attributes.recurring_price', 16)
            ->assertJsonPath('data.1.id', '25')
            ->assertJsonPath('data.1.type', 'Plan')
            ->assertJsonPath('data.1.attributes.name', 'Pro +')
            ->assertJsonPath('data.1.attributes.initial_price', 342)
            ->assertJsonPath('data.1.attributes.recurring_price', 322)
            ->assertJsonPath('data.1.attributes.products', [5, 13, 4, 19, 20])
            ->assertJsonPath('data.2.id', '28')
            ->assertJsonPath('data.2.type', 'Plan')
            ->assertJsonPath('data.2.attributes.name', 'Premium')
            ->assertJsonPath('data.2.attributes.frequency', '8-9')
            ->assertJsonPath('data.2.attributes.order', 4)
            ->assertJsonPath('data.2.attributes.initial_price', 442)
            ->assertJsonPath('data.2.attributes.recurring_price', 422)
            ->assertJsonPath('data.2.attributes.products', [13, 4, 5, 9, 20, 19])
            ->assertJsonPath('data.2.attributes.addons.0.type', 'Addon')
            ->assertJsonPath('data.2.attributes.addons.0.attributes.addon_id', 233373)
            ->assertJsonPath('data.2.attributes.addons.0.attributes.product_id', 2)
            ->assertJsonPath('data.2.attributes.addons.0.attributes.initial_price', 149)
            ->assertJsonPath('data.2.attributes.addons.0.attributes.recurring_price', 0);
    }

    public function test_get_returns_upgrades_with_discount_when_pro_plus_found(): void
    {
        $currentPlanData = '{"id":4,"ext_reference_id":1799,"name":"Basic","start_on":"2023-02-01","end_on":"2080-09-01","plan_service_frequency_id":9,"plan_status_id":2,"bill_monthly":false,"initial_discount":20,"recurring_discount":10,"created_at":"2023-01-24T22:00:05.000000Z","updated_at":"2024-03-18T17:52:18.000000Z","order":1,"area_plan_pricings":{"8":{"id":61301,"plan_pricing_level_id":8,"area_plan_id":27,"initial_min":142,"initial_max":143,"recurring_min":122,"recurring_max":123,"created_at":"2023-06-08T15:04:40.000000Z","updated_at":"2024-03-20T11:44:28.000000Z"}},"plan_category_ids":[1,2,3,5,8],"default_area_plan":{"id":27,"area_id":24,"plan_id":4,"created_at":"2023-03-27T21:52:14.000000Z","updated_at":"2023-05-15T18:33:54.000000Z","can_sell_percentage_threshold":25,"service_product_ids":[],"area_plan_pricings":[{"id":61301,"plan_pricing_level_id":8,"area_plan_id":27,"initial_min":142,"initial_max":143,"recurring_min":122,"recurring_max":123,"created_at":"2023-06-08T15:04:40.000000Z","updated_at":"2024-03-20T11:44:28.000000Z"}],"addons":{"1":{"id":224447,"area_plan_id":27,"product_id":4,"is_recurring":true,"initial_min":54.98,"initial_max":54.98,"recurring_min":12.99,"recurring_max":14.99,"created_at":"2023-06-08T15:04:40.000000Z","updated_at":"2023-06-08T15:04:40.000000Z"},"2":{"id":224448,"area_plan_id":27,"product_id":5,"is_recurring":true,"initial_min":49.98,"initial_max":49.98,"recurring_min":14.99,"recurring_max":44.99,"created_at":"2023-06-08T15:04:40.000000Z","updated_at":"2023-06-08T15:04:40.000000Z"},"6":{"id":224452,"area_plan_id":27,"product_id":13,"is_recurring":true,"initial_min":49.98,"initial_max":49.98,"recurring_min":4.99,"recurring_max":14.99,"created_at":"2023-06-08T15:04:40.000000Z","updated_at":"2023-06-08T15:04:40.000000Z"},"11":{"id":224457,"area_plan_id":27,"product_id":2,"is_recurring":true,"initial_min":148.99,"initial_max":148.99,"recurring_min":57.99,"recurring_max":98.99,"created_at":"2023-06-08T15:04:40.000000Z","updated_at":"2023-06-08T15:04:40.000000Z"}}},"area_plans":[],"agreement_length_ids":[2,3,1]}';
        $planUpgradesData = '{"1-22":{"id":22,"ext_reference_id":2827,"name":"Pro","start_on":"2023-01-01","end_on":"2080-01-01","plan_service_frequency_id":2,"plan_status_id":2,"bill_monthly":false,"initial_discount":10,"recurring_discount":10,"created_at":"2023-08-24T23:15:12.000000Z","updated_at":"2024-02-21T16:49:36.000000Z","order":1,"area_plan_pricings":{"8":{"id":62639,"plan_pricing_level_id":8,"area_plan_id":290,"initial_min":242,"initial_max":243,"recurring_min":222,"recurring_max":223,"created_at":null,"updated_at":"2024-03-20T11:47:26.000000Z"}},"plan_category_ids":[8],"default_area_plan":{"id":290,"area_id":24,"plan_id":22,"created_at":"2023-08-24T23:15:12.000000Z","updated_at":"2023-08-24T23:15:12.000000Z","can_sell_percentage_threshold":null,"service_product_ids":[19,20],"area_plan_pricings":[{"id":62639,"plan_pricing_level_id":8,"area_plan_id":290,"initial_min":242,"initial_max":243,"recurring_min":222,"recurring_max":223,"created_at":null,"updated_at":"2024-03-20T11:47:26.000000Z"}],"addons":{"1":{"id":229016,"area_plan_id":290,"product_id":2,"is_recurring":true,"initial_min":149,"initial_max":149,"recurring_min":59,"recurring_max":99,"created_at":null,"updated_at":null},"2":{"id":229017,"area_plan_id":290,"product_id":4,"is_recurring":true,"initial_min":0,"initial_max":0,"recurring_min":24,"recurring_max":15,"created_at":null,"updated_at":null},"3":{"id":229018,"area_plan_id":290,"product_id":5,"is_recurring":true,"initial_min":0,"initial_max":0,"recurring_min":16,"recurring_max":45,"created_at":null,"updated_at":null},"4":{"id":229019,"area_plan_id":290,"product_id":9,"is_recurring":true,"initial_min":0,"initial_max":0,"recurring_min":49,"recurring_max":99,"created_at":null,"updated_at":null},"8":{"id":229023,"area_plan_id":290,"product_id":13,"is_recurring":true,"initial_min":0,"initial_max":0,"recurring_min":5,"recurring_max":15,"created_at":null,"updated_at":null}}},"area_plans":[],"agreement_length_ids":[1,2,3]},"2-25":{"id":25,"ext_reference_id":1800,"name":"Pro +","start_on":"2023-01-01","end_on":"2080-01-01","plan_service_frequency_id":2,"plan_status_id":2,"bill_monthly":false,"initial_discount":0,"recurring_discount":10,"created_at":"2023-08-24T23:15:52.000000Z","updated_at":"2024-03-15T16:33:24.000000Z","order":2,"area_plan_pricings":{"8":{"id":63215,"plan_pricing_level_id":8,"area_plan_id":482,"initial_min":342,"initial_max":343,"recurring_min":322,"recurring_max":323,"created_at":null,"updated_at":"2024-03-20T11:49:48.000000Z"}},"plan_category_ids":[1,2,3,5,7,8],"default_area_plan":{"id":482,"area_id":24,"plan_id":25,"created_at":"2023-08-24T23:15:52.000000Z","updated_at":"2023-08-24T23:15:52.000000Z","can_sell_percentage_threshold":null,"service_product_ids":[5,13,4,19,20],"area_plan_pricings":[{"id":63215,"plan_pricing_level_id":8,"area_plan_id":482,"initial_min":342,"initial_max":343,"recurring_min":322,"recurring_max":323,"created_at":null,"updated_at":"2024-03-20T11:49:48.000000Z"}],"addons":{"1":{"id":231495,"area_plan_id":482,"product_id":9,"is_recurring":true,"initial_min":0,"initial_max":0,"recurring_min":49,"recurring_max":99,"created_at":null,"updated_at":null},"9":{"id":231503,"area_plan_id":482,"product_id":2,"is_recurring":true,"initial_min":149,"initial_max":149,"recurring_min":59,"recurring_max":99,"created_at":null,"updated_at":null}}},"area_plans":[],"agreement_length_ids":[1,3,2]},"4-28":{"id":28,"ext_reference_id":2828,"name":"Premium","start_on":"2023-01-01","end_on":"2080-01-01","plan_service_frequency_id":6,"plan_status_id":2,"bill_monthly":false,"initial_discount":0,"recurring_discount":10,"created_at":"2023-08-24T23:16:58.000000Z","updated_at":"2024-03-18T13:25:59.000000Z","order":4,"area_plan_pricings":{"8":{"id":63779,"plan_pricing_level_id":8,"area_plan_id":670,"initial_min":442,"initial_max":443,"recurring_min":422,"recurring_max":423,"created_at":null,"updated_at":"2024-03-20T11:51:35.000000Z"}},"plan_category_ids":[8],"default_area_plan":{"id":670,"area_id":24,"plan_id":28,"created_at":"2023-08-24T23:16:58.000000Z","updated_at":"2023-08-24T23:16:58.000000Z","can_sell_percentage_threshold":null,"service_product_ids":[13,4,5,9,20,19],"area_plan_pricings":[{"id":63779,"plan_pricing_level_id":8,"area_plan_id":670,"initial_min":442,"initial_max":443,"recurring_min":422,"recurring_max":423,"created_at":null,"updated_at":"2024-03-20T11:51:35.000000Z"}],"addons":{"1":{"id":233373,"area_plan_id":670,"product_id":2,"is_recurring":true,"initial_min":149,"initial_max":149,"recurring_min":0,"recurring_max":99,"created_at":null,"updated_at":null}}},"area_plans":[],"agreement_length_ids":[2,3,1]}}';
        $ticketData = '{"ticketID":"26340201","customerID":"2873695","billToAccountID":"2873695","officeID":"1","dateCreated":"2024-03-14 07:59:36","invoiceDate":"2024-03-14 00:00:00","dateUpdated":"2024-03-19 01:38:49","active":"-1","subTotal":"180.00","taxAmount":"0.00","total":"180.00","serviceCharge":"170.00","serviceTaxable":"1","productionValue":"-1.00","taxRate":"0.000000","appointmentID":"0","balance":"240.00","subscriptionID":"2966532","autoGenerated":"0","autoGeneratedType":"NA","renewalID":"0","serviceID":"1799","invoiceNumber":"26340201","templateType":"R","glNumber":"","createdBy":"527941","items":[{"itemID":"9748199","ticketID":"26340201","officeID":"1","description":"Aphid Quarterly","quantity":"1","amount":"10.00","productID":"5","serviceID":"0","taxable":"1","creditTo":"-1","unitID":null,"glNumber":"","measurementSF":"0","measurementLF":"0","prepaymentAmount":null,"category":"Specialty Pests","code":"Aphids Q","dateCreated":"2024-03-14 07:59:36","dateUpdated":"2024-03-14 07:59:36"}]}';
        $purchasedAddons = [4 => 2381];
        $subscriptionData = ['initialServiceTotal' => 190];

        $this->createAndLogInAuth0UserWithAccount();
        $this->setupActionToReturnResponseData(
            $currentPlanData,
            $planUpgradesData,
            $ticketData,
            $purchasedAddons,
            $subscriptionData,
            true
        );

        $response = $this->getJson($this->getRoute());
        $response->assertOk()
            ->assertJsonPath('data.1.attributes.initial_price', 180)
            ->assertJsonPath('data.1.attributes.recurring_price', 160);
    }

    protected function setupActionToReturnResponseData(
        string $currentPlanData,
        string $planUpgradesData,
        string $ticketData,
        array $purchasedAddons,
        array $subscriptionData,
        bool $hasDiscountToPro,
    ): void {
        $actionData = $this->getActionResponseData(
            $currentPlanData,
            $planUpgradesData,
            $ticketData,
            $purchasedAddons,
            $subscriptionData,
            $hasDiscountToPro
        );

        $this->actionMock
            ->shouldReceive('__invoke')
            ->with($this->getTestAccountNumber())
            ->andReturn($actionData)
            ->once();
    }

    protected function getActionResponseData(
        string $currentPlanData,
        string $planUpgradesData,
        string $ticketData,
        array $purchasedAddons,
        array $subscriptionData,
        bool $hasDiscountToPro,
    ): array {
        $serviceFrequenciesData = '{"2":{"id":2,"frequency":6,"company_id":1,"order":1,"created_at":"2023-01-19T15:06:28.000000Z","updated_at":"2023-06-27T08:27:57.000000Z","frequency_display":"6-7"},"6":{"id":6,"frequency":8,"company_id":1,"order":1,"created_at":"2023-06-01T09:11:40.000000Z","updated_at":"2023-06-27T08:28:09.000000Z","frequency_display":"8-9"},"9":{"id":9,"frequency":4,"company_id":1,"order":1,"created_at":"2023-06-01T09:14:28.000000Z","updated_at":"2023-06-26T07:09:55.000000Z","frequency_display":"4-5"}}';

        $serviceFrequencies =  array_map(
            static fn (object $serviceFrequency) => PlanServiceFrequency::fromApiResponse($serviceFrequency),
            (array) json_decode(json: $serviceFrequenciesData, associative: false, flags: JSON_THROW_ON_ERROR)
        );
        $currentPlan = Plan::fromApiResponse(
            json_decode(json: $currentPlanData, associative: false, flags: JSON_THROW_ON_ERROR)
        );
        $upgrades = array_map(
            static fn (object $product) => Plan::fromApiResponse($product),
            (array) json_decode(json: $planUpgradesData, associative: false, flags: JSON_THROW_ON_ERROR)
        );
        $ticket = Ticket::fromApiObject(
            (object) json_decode(json: $ticketData, associative: false, flags: JSON_THROW_ON_ERROR)
        );
        $subscription = SubscriptionData::getTestEntityData(1, $subscriptionData)->first();
        $subscription->recurringTicket = $ticket;

        return [
            'current' => $currentPlan,
            'upgrades' => $upgrades,
            'purchasedAddons' => $purchasedAddons,
            'serviceFrequencies' => $serviceFrequencies,
            'subscription' => $subscription,
            'hasDiscountToPro' => $hasDiscountToPro,
        ];
    }

    /**
     * @dataProvider getUpgradesExceptionProvider
     */
    public function test_get_returns_proper_error_on_exception(Throwable $exception, int $expectedStatusCode): void
    {
        $this->createAndLogInAuth0UserWithAccount();
        $this->actionMock
            ->shouldReceive('__invoke')
            ->with($this->getTestAccountNumber())
            ->andThrow($exception)
            ->once();

        $response = $this->getJson($this->getRoute());
        $response->assertStatus($expectedStatusCode);
    }

    public static function getUpgradesExceptionProvider(): iterable
    {
        yield 'no account or invalid credentials' => [
            new AccountNotFoundException(),
            HttpStatus::NOT_FOUND,
        ];
        yield 'no active subscription' => [
            new SubscriptionNotFound(),
            HttpStatus::NOT_FOUND,
        ];
        yield 'no entity' => [
            new EntityNotFoundException(),
            HttpStatus::NOT_FOUND,
        ];
        yield 'FieldNotFound' => [
            new FieldNotFound(),
            HttpStatus::INTERNAL_SERVER_ERROR,
        ];
    }
}
