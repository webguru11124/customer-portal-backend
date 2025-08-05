<?php

namespace Tests\Unit\Services;

use App\DTO\PlanBuilder\Plan;
use App\DTO\PlanBuilder\PlanServiceFrequency;
use App\DTO\PlanBuilder\Product;
use App\DTO\PlanBuilder\SearchPlansDTO;
use App\Exceptions\PlanBuilder\FieldNotFound;
use App\Repositories\PlanBuilder\CachedPlanBuilderRepository;
use App\Services\PlanBuilderService;
use Mockery;
use Mockery\MockInterface;
use Tests\Data\PlanBuilderResponseData;
use Tests\TestCase;

class PlanBuilderServiceTest extends TestCase
{
    public const OFFICE_ID = 1;

    public MockInterface|CachedPlanBuilderRepository $repositoryMock;

    public function setUp(): void
    {
        parent::setUp();
        $this->repositoryMock = Mockery::mock(CachedPlanBuilderRepository::class);
    }

    public function test_plan_builder_service_throws_exception_on_invalid_settings(): void
    {
        $settings = PlanBuilderResponseData::getSettingsResponse();
        $settings['planCategories'] = [];

        $this->repositoryMock->shouldReceive('getSettings')
            ->andReturn($settings);
        $this->setupRepositoryToReturnValidSearchPlans();
        $this->setupRepositoryToReturnPlanWithProducts();
        $this->setupRepositoryToSearchPlanWithProducts();

        $this->expectException(FieldNotFound::class);
        (new PlanBuilderService($this->repositoryMock))->getProducts(self::OFFICE_ID);
    }

    public function test_get_products_returns_products(): void
    {
        $this->setupRepositoryToReturnPlansAreaPlansAndProducts();
        $service = new PlanBuilderService($this->repositoryMock);
        $products = $service->getProducts(self::OFFICE_ID);
        self::assertIsArray($products);
        self::assertInstanceOf(Product::class, array_pop($products));
    }

    /**
     * @dataProvider provideGetServicePlanData
     */
    public function test_get_service_plan_returns_plan(int $serviceId, int $officeId, int $areaPlanId): void
    {
        $this->setupRepositoryToReturnPlansAreaPlansAndProducts();

        $service = new PlanBuilderService($this->repositoryMock);
        $plan = $service->getServicePlan($serviceId, $officeId);

        self::assertInstanceOf(Plan::class, $plan);
        self::assertEquals($serviceId, $plan->extReferenceId);
        self::assertEquals($areaPlanId, $plan->defaultAreaPlan->id);
        foreach ($plan->defaultAreaPlan->addons as $addon) {
            self::assertTrue($addon->isRecurring);
        }
    }

    public function provideGetServicePlanData(): array
    {
        return [
            'for office with area plan return it' => [
                'serviceId' => 2828,
                'officeId' => 1,
                'areaPlanId' => 670,
            ],
            'for office without area plan return default' => [
                'serviceId' => 2828,
                'officeId' => 999,
                'areaPlanId' => 668,
            ],
        ];
    }

    public function test_get_service_plan_throws_exception_on_invalid_plan_id(): void
    {
        $this->setupRepositoryToReturnPlansAreaPlansAndProducts();
        $service = new PlanBuilderService($this->repositoryMock);

        $this->expectException(FieldNotFound::class);
        $service->getServicePlan(0, 1);
    }

    public function test_get_service_plan_throws_exception_on_no_valid_pricing_level(): void
    {
        $settings = PlanBuilderResponseData::getSettingsResponse();
        $settings['planPricingLevels'] = [];
        $this->repositoryMock->shouldReceive('getSettings')
            ->andReturn($settings);
        $this->setupRepositoryToReturnValidSearchPlans();
        $this->setupRepositoryToReturnPlanWithProducts();
        $this->setupRepositoryToSearchPlanWithProducts();

        $service = new PlanBuilderService($this->repositoryMock);

        $this->expectException(FieldNotFound::class);
        $service->getServicePlan(2828, 1);
    }

    /**
     * @dataProvider provideGetUpgradesData
     */
    public function test_get_upgrades_for_service_plan_returns_plans(
        int $serviceId,
        int $officeId,
        int $areaPlanId
    ): void {
        $this->setupRepositoryToReturnPlansAreaPlansAndProducts();
        $this->setupRepositoryToReturnUpgradePaths();

        $service = new PlanBuilderService($this->repositoryMock);
        $upgrades = $service->getUpgradesForServicePlan($serviceId, $officeId);

        $previousOrderValue = 0;
        foreach ($upgrades as $upgrade) {
            self::assertInstanceOf(Plan::class, $upgrade);
            self::assertEquals($areaPlanId, $upgrade->defaultAreaPlan->id);
            self::assertGreaterThan($previousOrderValue, $upgrade->order);
            $previousOrderValue = $upgrade->order;
            foreach ($upgrade->defaultAreaPlan->addons as $addon) {
                self::assertTrue($addon->isRecurring);
            }
        }
    }

    public function provideGetUpgradesData(): array
    {
        return [
            'for office with area plan return it' => [
                'serviceId' => 2827,
                'officeId' => 1,
                'areaPlanId' => 670,
            ],
            'for office without area plan return default' => [
                'serviceId' => 2827,
                'officeId' => 999,
                'areaPlanId' => 668,
            ],
        ];
    }

    public function test_get_upgrades_for_service_plan_returns_empty_array_for_plan_without_upgrades(): void
    {
        $this->setupRepositoryToReturnPlansAreaPlansAndProducts();
        $this->repositoryMock->shouldReceive('getPlanUpgradePaths')
            ->andReturn(PlanBuilderResponseData::getInvalidUpgradePathsResponse());

        $service = new PlanBuilderService($this->repositoryMock);
        $upgrades = $service->getUpgradesForServicePlan(1799, 1);

        self::assertEmpty($upgrades);
    }

    public function test_get_upgrades_for_service_plan_throws_exception_on_invalid_plan_id(): void
    {
        $this->setupRepositoryToReturnPlansAreaPlansAndProducts();
        $service = new PlanBuilderService($this->repositoryMock);

        $this->expectException(FieldNotFound::class);
        $service->getUpgradesForServicePlan(0, 1);
    }

    public function test_get_upgrades_for_service_plan_throws_exception_on_missing_area_plan_pricing(): void
    {
        $this->setupRepositoryToReturnValidSettings();
        $this->setupRepositoryToReturnValidSearchPlans();
        $this->setupRepositoryToReturnUpgradePaths();
        $plans = [4, 22, 28];

        foreach ($plans as $planId) {
            $plan = PlanBuilderResponseData::getPlanWithProductsResponse($planId);
            foreach ($plan["plan"]->areaPlans as $key => $areaPlan) {
                $plan["plan"]->areaPlans[$key]->areaPlanPricings = [];
            }

            $this->repositoryMock->shouldReceive('getPlanWithProducts')
                ->with($planId)
                ->andReturn($plan);
        }
        $this->repositoryMock->shouldReceive('searchPlansWithProducts')
            ->with(SearchPlansDTO::class)
            ->andReturnUsing(
                function (SearchPlansDTO $dto) {
                    return PlanBuilderResponseData::searchPlanWithProductsResponse($dto->officeId, $dto->extReferenceId);
                }
            );

        $service = new PlanBuilderService($this->repositoryMock);

        $this->expectException(FieldNotFound::class);
        $service->getServicePlan(2827, 1);
    }

    public function test_get_upgrades_for_service_plan_throws_exception_on_missing_status_id(): void
    {
        $settings = PlanBuilderResponseData::getSettingsResponse();
        $settings["planStatuses"] = [];
        $this->repositoryMock->shouldReceive('getSettings')
            ->andReturn($settings);

        $this->expectException(FieldNotFound::class);
        $service = new PlanBuilderService($this->repositoryMock);
        $service->getServicePlan(2827, 1);
    }

    public function test_get_service_frequencies_returns_frequencies(): void
    {
        $this->setupRepositoryToReturnPlansAreaPlansAndProducts();
        $service = new PlanBuilderService($this->repositoryMock);

        $frequencies = $service->getServiceFrequencies();
        foreach ($frequencies as $frequency) {
            self::assertInstanceOf(PlanServiceFrequency::class, $frequency);
        }
    }

    protected function setupRepositoryToReturnPlansAreaPlansAndProducts(): void
    {
        $this->setupRepositoryToReturnValidSettings();
        $this->setupRepositoryToReturnValidSearchPlans();
        $this->setupRepositoryToReturnPlanWithProducts();
        $this->setupRepositoryToSearchPlanWithProducts();
    }

    protected function setupRepositoryToReturnValidSettings(): void
    {
        $this->repositoryMock->shouldReceive('getSettings')
            ->andReturn(PlanBuilderResponseData::getSettingsResponse());
    }

    protected function setupRepositoryToReturnValidSearchPlans(): void
    {
        $this->repositoryMock->shouldReceive('searchPlans')
            ->with(SearchPlansDTO::class)
            ->andReturn(PlanBuilderResponseData::getCustomerPortalPlansResponse());
    }

    protected function setupRepositoryToReturnPlanWithProducts(): void
    {
        $plans = [4, 22, 28];
        foreach ($plans as $plan) {
            $this->repositoryMock->shouldReceive('getPlanWithProducts')
                ->with($plan)
                ->andReturn(PlanBuilderResponseData::getPlanWithProductsResponse($plan));
        }
    }

    protected function setupRepositoryToSearchPlanWithProducts(): void
    {
        $this->repositoryMock->shouldReceive('searchPlansWithProducts')
            ->with(SearchPlansDTO::class)
            ->andReturnUsing(
                function (SearchPlansDTO $dto) {
                    return PlanBuilderResponseData::searchPlanWithProductsResponse($dto->officeId, $dto->extReferenceId);
                }
            );
    }

    protected function setupRepositoryToReturnUpgradePaths(): void
    {
        $this->repositoryMock->shouldReceive('getPlanUpgradePaths')
            ->andReturn(PlanBuilderResponseData::getUpgradePathsResponse());
    }
}
