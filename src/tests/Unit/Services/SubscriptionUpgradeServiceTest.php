<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\DTO\PlanBuilder\Plan;
use App\DTO\PlanBuilder\PlanUpgradePaths;
use App\DTO\PlanBuilder\Product;
use App\Exceptions\PlanBuilder\FieldNotFound;
use App\Helpers\DateTimeHelper;
use App\Models\External\SubscriptionModel;
use App\Repositories\PlanBuilder\PlanBuilderRepository;
use App\Services\PlanBuilderService;
use App\Services\SubscriptionUpgradeService;
use Illuminate\Support\Facades\Config;
use Mockery;
use Mockery\MockInterface;
use Tests\Data\SubscriptionData;
use Tests\Data\TicketData;
use Tests\Data\TicketTemplateAddonData;
use Tests\TestCase;
use Tests\Traits\RandomIntTestData;

class SubscriptionUpgradeServiceTest extends TestCase
{
    use RandomIntTestData;

    private const PRODUCT_ID = 1620;
    private const PLAN_ID_PRO = 1800;
    private const PLAN_ID_PREMIUM = 2828;

    protected SubscriptionUpgradeService $subscriptionUpgradeService;
    protected PlanBuilderRepository|MockInterface $planBuilderRepositoryMock;
    protected PlanBuilderService|MockInterface $planBuilderServiceMock;

    public function setUp(): void
    {
        parent::setUp();

        $this->planBuilderRepositoryMock = Mockery::mock(PlanBuilderRepository::class);
        $this->planBuilderServiceMock = Mockery::mock(PlanBuilderService::class);
    }

    /**
     * @dataProvider providePlanBuilderDataForUpgradePaths
     */
    public function test_is_upgrade_available_based_on_plan_builder_plan_upgrade_paths(
        int $currentPlanId,
        string $currentPlanName,
        int|null $planBuilderUpgradePathsFromId,
        int|null $planBuilderUpgradePathsToId,
        bool $expectedResult
    ): void {
        $subscription = $this->setupSubscriptionWithMultipleAddons();

        $this->planBuilderServiceMock
            ->shouldReceive('getServicePlan')
            ->withArgs([$subscription->serviceId, $subscription->officeId])
            ->once()
            ->andReturn($this->setupPlanBuilderPlan($currentPlanId, $currentPlanName));

        $this->planBuilderServiceMock
            ->shouldReceive('getProducts')
            ->withArgs([$subscription->officeId])
            ->andReturn([]);

        $this->planBuilderRepositoryMock
            ->shouldReceive('getPlanUpgradePaths')
            ->withNoArgs()
            ->once()
            ->andReturn([
                $this->setupPlanBuilderUpgradePaths($planBuilderUpgradePathsFromId, $planBuilderUpgradePathsToId),
            ]);

        $this->assertEquals($expectedResult, $this->getSubscriptionUpgradeService()->isUpgradeAvailable($subscription));
    }

    /**
     * @dataProvider providePlanBuilderDataForUpgradeAddons
     */
    public function test_upgrade_available_based_on_plan_builder_addons(
        int $addonId,
        int $currentPlanId,
        string $currentPlanName,
        int $planBuilderProductsQty,
        string $planBuilderProductName,
        int $planBuilderProductId,
        bool $expectedResult
    ): void {
        Config::set('aptive.subscription.addons_exceptions.disallowed_pests', ['German Roach']);

        $subscription = $this->setupSubscriptionWithMultipleAddons($addonId);

        $this->planBuilderServiceMock
            ->shouldReceive('getServicePlan')
            ->withArgs([$subscription->serviceId, $subscription->officeId])
            ->once()
            ->andReturn($this->setupPlanBuilderPlan($currentPlanId, $currentPlanName, $planBuilderProductId));

        $this->planBuilderRepositoryMock
            ->shouldReceive('getPlanUpgradePaths')
            ->withNoArgs()
            ->once()
            ->andReturn([]);

        $this->planBuilderServiceMock
            ->shouldReceive('getProducts')
            ->withArgs([$subscription->officeId])
            ->once()
            ->andReturn($this->setupPlanBuilderProducts(
                extReferenceId: $planBuilderProductId,
                productName: $planBuilderProductName,
                qty: $planBuilderProductsQty
            ));

        $this->assertEquals($expectedResult, $this->getSubscriptionUpgradeService()->isUpgradeAvailable($subscription));
    }

    public function test_upgrade_available_due_to_empty_recurring_ticket(): void
    {
        $subscriptionsWithoutRecurringTicket = SubscriptionData::getTestEntityData(1, [
            'recurringTicket' => null,
        ]);

        $this->planBuilderServiceMock
            ->shouldReceive('getServicePlan')
            ->withAnyArgs()
            ->never();

        $this->planBuilderServiceMock
            ->shouldReceive('getProducts')
            ->never();

        $this->planBuilderRepositoryMock
            ->shouldReceive('getPlanUpgradePaths')
            ->withNoArgs()
            ->never();

        $this->assertTrue($this->getSubscriptionUpgradeService()->isUpgradeAvailable($subscriptionsWithoutRecurringTicket->first()));
    }

    public function test_upgrade_not_available_due_to_exception_from_plan_builder(): void
    {
        $subscription = $this->setupSubscriptionWithMultipleAddons();

        $this->planBuilderServiceMock
            ->shouldReceive('getServicePlan')
            ->withArgs([$subscription->serviceId, $subscription->officeId])
            ->once()
            ->andThrow(FieldNotFound::class);

        $this->planBuilderServiceMock
            ->shouldReceive('getProducts')
            ->never();

        $this->planBuilderRepositoryMock
            ->shouldReceive('getPlanUpgradePaths')
            ->withNoArgs()
            ->never();

        $this->assertFalse($this->getSubscriptionUpgradeService()->isUpgradeAvailable($subscription));
    }

    public function test_upgrade_not_available_due_to_exception_from_plan_builder_upgrade_paths(): void
    {
        $subscription = $this->setupSubscriptionWithMultipleAddons();

        $this->planBuilderServiceMock
            ->shouldReceive('getServicePlan')
            ->withArgs([$subscription->serviceId, $subscription->officeId])
            ->once()
            ->andReturn($this->setupPlanBuilderPlan(self::PLAN_ID_PREMIUM, 'Premium'));

        $this->planBuilderRepositoryMock
            ->shouldReceive('getPlanUpgradePaths')
            ->withNoArgs()
            ->andThrow(\Exception::class);

        $this->planBuilderServiceMock
            ->shouldReceive('getProducts')
            ->withArgs([$subscription->officeId])
            ->andReturn([]);

        $this->assertFalse($this->getSubscriptionUpgradeService()->isUpgradeAvailable($subscription));
    }

    public function test_plan_builder_specialty_pests_products_return_empty_products_on_pb_exception(): void
    {
        $subscription = $this->setupSubscriptionWithMultipleAddons();
        $this->planBuilderServiceMock
            ->shouldReceive('getServicePlan')
            ->withArgs([$subscription->serviceId, $subscription->officeId])
            ->once()
            ->andThrow(\Exception::class);

        $this->assertEmpty($this->getSubscriptionUpgradeService()->getPlanBuilderPlanSpecialtyPestsProducts($subscription));
    }

    public function test_plan_builder_specialty_pests_products_return_products(): void
    {
        $subscription = $this->setupSubscriptionWithMultipleAddons();
        $this->planBuilderServiceMock
            ->shouldReceive('getServicePlan')
            ->withArgs([$subscription->serviceId, $subscription->officeId])
            ->once()
            ->andReturn($this->setupPlanBuilderPlan(self::PLAN_ID_PRO, 'Premium'));

        $products = $this->setupPlanBuilderProducts(extReferenceId: self::PRODUCT_ID, productName: 'Rodent');
        $this->planBuilderServiceMock
            ->shouldReceive('getProducts')
            ->withArgs([$subscription->officeId])
            ->once()
            ->andReturn($products);

        $this->assertEquals(
            $products,
            $this->getSubscriptionUpgradeService()->getPlanBuilderPlanSpecialtyPestsProducts($subscription)
        );
    }

    protected function getSubscriptionUpgradeService(): SubscriptionUpgradeService
    {
        if (empty($this->subscriptionService)) {
            $this->subscriptionUpgradeService = new SubscriptionUpgradeService(
                $this->planBuilderRepositoryMock,
                $this->planBuilderServiceMock
            );
        }

        return $this->subscriptionUpgradeService;
    }

    protected function setupSubscriptionWithMultipleAddons(
        int $productId = self::PRODUCT_ID,
    ): SubscriptionModel {
        return SubscriptionData::getTestEntityData(1, [
            'recurringTicket' => (object) TicketData::getRawTestData(1, [
                'items' => TicketTemplateAddonData::getRawTestData(1, ['productID' => $productId])
                    ->map(static fn ($item) => (object) $item)->toArray(),
            ])->first(),
        ])->first();
    }

    protected function setupPlanBuilderPlan(
        int $id,
        string $name,
        int $productId = self::PRODUCT_ID,
    ): Plan {
        return Plan::fromApiResponse((object) [
            'id' => $id,
            'ext_reference_id' => random_int(1, PHP_INT_MAX),
            'name' => $name,
            'start_on' => null,
            'end_on' => null,
            'plan_service_frequency_id' => random_int(1, PHP_INT_MAX),
            'plan_status_id' => random_int(1, PHP_INT_MAX),
            'bill_monthly' => true,
            'initial_discount' => null,
            'recurring_discount' => null,
            'company_id' => random_int(1, PHP_INT_MAX),
            'created_at' => (new \DateTime())->format(DateTimeHelper::defaultDateFormat()),
            'updated_at' => (new \DateTime())->format(DateTimeHelper::defaultDateFormat()),
            'order' => random_int(1, PHP_INT_MAX),
            'plan_category_ids' => null,
            'area_plans' => [],
            'agreement_length_ids' => null,
            'default_area_plan' => (object)[
                'addons' => [(object)[
                    'id' => random_int(1, PHP_INT_MAX),
                    'area_plan_id' => random_int(1, PHP_INT_MAX),
                    'product_id' => $productId,
                    'is_recurring' => true,
                    'initial_min' => 0,
                    'recurring_min' => 0,
                    'recurring_max' => 1,
                    'company_id' => random_int(1, PHP_INT_MAX),
                    'created_at' => (new \DateTime())->format(DateTimeHelper::defaultDateFormat()),
                    'updated_at' => (new \DateTime())->format(DateTimeHelper::defaultDateFormat()),
                ]],
                'id' => random_int(1, PHP_INT_MAX),
                'area_id' => random_int(1, PHP_INT_MAX),
                'plan_id' => random_int(1, PHP_INT_MAX),
                'can_sell_percentage_threshold' => random_int(1, PHP_INT_MAX),
                'area_plan_pricings' => [],
            ],
        ]);
    }

    /**
     * @param int $extReferenceId
     * @param string $productName
     * @param int $qty
     *
     * @return array<int, Product>
     */
    protected function setupPlanBuilderProducts(
        int $extReferenceId,
        string $productName,
        int $qty = 1
    ): array {
        $products = [];
        for ($i = 1; $i <= $qty; $i++) {
            $products[] = Product::fromApiResponse((object) [
                'id' => $extReferenceId,
                'ext_reference_id' => $extReferenceId,
                'product_sub_category_id' => random_int(1, PHP_INT_MAX),
                'order' => random_int(1, PHP_INT_MAX),
                'name' => $productName,
                'image' => '',
                'is_recurring' => true,
                'initial_min' => 0.00,
                'initial_max' => 0.00,
                'recurring_min' => 0.00,
                'recurring_max' => 0.00,
                'company_id' => random_int(1, PHP_INT_MAX),
                'created_at' => (new \DateTime())->format(DateTimeHelper::defaultDateFormat()),
                'updated_at' => (new \DateTime())->format(DateTimeHelper::defaultDateFormat()),
                'needs_customer_support' => false,
                'description' => '',
                'image_name' => '',
            ]);
        }

        return $products;
    }

    protected function setupPlanBuilderUpgradePaths(
        int $upgradeFromPlanId,
        int $upgradeToPlanId
    ): PlanUpgradePaths {
        return PlanUpgradePaths::fromApiResponse((object) [
            'id' => random_int(1, PHP_INT_MAX),
            'upgrade_from_plan_id' => $upgradeFromPlanId,
            'upgrade_to_plan_id' => $upgradeToPlanId,
            'price_discount' => 0,
            'use_to_plan_price' => false,
            'company_id' => random_int(1, PHP_INT_MAX),
            'created_at' => (new \DateTime())->format(DateTimeHelper::defaultDateFormat()),
            'updated_at' => (new \DateTime())->format(DateTimeHelper::defaultDateFormat()),
        ]);
    }

    protected function providePlanBuilderDataForUpgradePaths(): iterable
    {
        yield 'UPGRADE_AVAILABLE_based_on_existing_plan_upgrade' => [
            'currentPlanId' => $this->getTestPlanBuilderUpgradePathFromId(),
            'currentPlanName' => 'Pro+',
            'planBuilderUpgradePathsFromId' => $this->getTestPlanBuilderUpgradePathFromId(),
            'planBuilderUpgradePathsToId' => $this->getTestPlanBuilderUpgradePathFromId() + 1,
            'expectedResult' => true,
        ];

        yield 'UPGRADE_NOT_AVAILABLE_due_no_upgrades_for_plan_in_plan_builder' => [
            'currentPlanId' => $this->getTestPlanBuilderUpgradePathFromId(),
            'currentPlanName' => 'Premium',
            'planBuilderUpgradePathsFromId' => $this->getTestPlanBuilderUpgradePathFromId() - 1,
            'planBuilderUpgradePathsToId' => $this->getTestPlanBuilderUpgradePathFromId() - 2,
            'expectedResult' => false,
        ];
    }

    protected function providePlanBuilderDataForUpgradeAddons(): iterable
    {
        yield 'UPGRADE_NOT_AVAILABLE_due_to_empty_plan_builder_available_addons' => [
            'addonId' => self::PRODUCT_ID,
            'currentPlanId' => $this->getTestPlanBuilderUpgradePathFromId(),
            'currentPlanName' => 'Premium',
            'planBuilderProductsQty' => 0,
            'planBuilderProductName' => 'Rodent',
            'planBuilderProductId' => self::PRODUCT_ID,
            'expectedResult' => false,
        ];

        yield 'UPGRADE_NOT_AVAILABLE_due_to_all_addons_included_in_subscription' => [
            'addonId' => self::PRODUCT_ID,
            'currentPlanId' => $this->getTestPlanBuilderUpgradePathFromId(),
            'currentPlanName' => 'Premium',
            'planBuilderProductsQty' => 1,
            'planBuilderProductName' => 'Rodent',
            'planBuilderProductId' => self::PRODUCT_ID,
            'expectedResult' => false,
        ];

        yield 'UPGRADE_NOT_AVAILABLE_due_to_all_addons_included_in_subscription_and_skip_german_roach' => [
            'addonId' => self::PRODUCT_ID,
            'currentPlanId' => $this->getTestPlanBuilderUpgradePathFromId(),
            'currentPlanName' => 'Premium',
            'planBuilderProductsQty' => 1,
            'planBuilderProductName' => 'German Roach',
            'planBuilderProductId' => self::PRODUCT_ID + 1,
            'expectedResult' => false,
        ];

        yield 'UPGRADE_AVAILABLE_due_to_not_all_addons_included_in_subscription' => [
            'addonId' => self::PRODUCT_ID,
            'currentPlanId' => $this->getTestPlanBuilderUpgradePathFromId(),
            'currentPlanName' => 'Premium',
            'planBuilderProductsQty' => 1,
            'planBuilderProductName' => 'Rodent',
            'planBuilderProductId' => self::PRODUCT_ID + 1,
            'expectedResult' => true,
        ];
    }
}
