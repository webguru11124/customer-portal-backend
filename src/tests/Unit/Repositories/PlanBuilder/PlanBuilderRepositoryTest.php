<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories\PlanBuilder;

use App\DTO\PlanBuilder\AgreementLength;
use App\DTO\PlanBuilder\AreaPlan;
use App\DTO\PlanBuilder\AreaPlanPricing;
use App\DTO\PlanBuilder\Category;
use App\DTO\PlanBuilder\PlanPricingLevel;
use App\DTO\PlanBuilder\PlanServiceFrequency;
use App\DTO\PlanBuilder\PlanUpgradePaths;
use App\DTO\PlanBuilder\Status;
use App\DTO\PlanBuilder\TargetContractValue;
use App\Repositories\PlanBuilder\PlanBuilderRepository;
use Exception;

class PlanBuilderRepositoryTest extends PlanBuilderRepositoryBase
{
    public function test_get_area_plan_pricings_returns_valid_data(): void
    {
        $clientMock = $this->mockHttpGetRequest(
            url: self::API_URL . '/area_plan_pricings',
            responseContent: '[{"id":61670,"area_plan_id":275,"plan_pricing_level_id":8,"initial_min":39,"initial_max":399,"recurring_min":105,"recurring_max":149},{"id":61669,"area_plan_id":275,"plan_pricing_level_id":9,"initial_min":39,"initial_max":399,"recurring_min":105,"recurring_max":169}]',
        );
        $configMock = $this->getConfigMock();
        $loggerMock = $this->getLoggerMockLoggingRequestAndResponse();

        $repository = new PlanBuilderRepository($clientMock, $configMock, $loggerMock);
        $result = $repository->getAreaPlanPricings();

        $this->assertInstanceOf(AreaPlanPricing::class, $result[0]);
        $this->assertInstanceOf(AreaPlanPricing::class, $result[1]);
        $this->assertEquals(61670, $result[0]->id);
        $this->assertEquals(8, $result[0]->planPricingLevelId);
        $this->assertEquals(275, $result[0]->areaPlanId);
        $this->assertEquals(39, $result[1]->initialMin);
        $this->assertEquals(399, $result[1]->initialMax);
        $this->assertEquals(105, $result[1]->recurringMin);
        $this->assertEquals(169, $result[1]->recurringMax);
        $this->assertNull($result[0]->createdAt);
        $this->assertNull($result[0]->updatedAt);
    }

    public function test_get_area_plan_pricings_returns_exception(): void
    {
        $clientMock = $this->mockHttpGetRequestToThrowException(self::API_URL . '/area_plan_pricings');
        $configMock = $this->getConfigMock();
        $loggerMock = $this->getLoggerMockLoggingRequestOnly();

        $this->expectException(Exception::class);

        $repository = new PlanBuilderRepository($clientMock, $configMock, $loggerMock);
        $repository->getAreaPlanPricings();
    }

    public function test_get_plan_upgrade_paths_returns_valid_data(): void
    {
        $clientMock = $this->mockHttpGetRequest(
            url: self::API_URL . '/plan_upgrade_paths',
            responseContent: '[{"id":1,"upgrade_from_plan_id":4,"upgrade_to_plan_id":1,"price_discount":200,"use_to_plan_price":false,"created_at":"2023-04-28T12:00:59.000000Z","updated_at":"2023-06-01T17:47:15.000000Z"},{"id":2,"upgrade_from_plan_id":4,"upgrade_to_plan_id":2,"price_discount":100,"use_to_plan_price":true,"created_at":"2023-04-28T12:01:01.000000Z","updated_at":"2023-06-01T16:37:00.000000Z"}]',
        );
        $configMock = $this->getConfigMock();
        $loggerMock = $this->getLoggerMockLoggingRequestAndResponse();

        $repository = new PlanBuilderRepository($clientMock, $configMock, $loggerMock);
        $result = $repository->getPlanUpgradePaths();

        $this->assertInstanceOf(PlanUpgradePaths::class, $result[0]);
        $this->assertInstanceOf(PlanUpgradePaths::class, $result[1]);
        $this->assertEquals(1, $result[0]->id);
        $this->assertEquals(4, $result[0]->upgradeFromPlanId);
        $this->assertEquals(1, $result[0]->upgradeToPlanId);
        $this->assertEquals(200, $result[0]->priceDiscount);
        $this->assertEquals(true, $result[1]->useToPlanPrice);
        $this->assertEquals('2023-04-28T12:01:01.000000Z', $result[1]->createdAt);
        $this->assertEquals('2023-06-01T16:37:00.000000Z', $result[1]->updatedAt);
    }

    public function test_get_plan_upgrade_paths_returns_exception(): void
    {
        $clientMock = $this->mockHttpGetRequestToThrowException(self::API_URL . '/plan_upgrade_paths');
        $configMock = $this->getConfigMock();
        $loggerMock = $this->getLoggerMockLoggingRequestOnly();

        $this->expectException(Exception::class);

        $repository = new PlanBuilderRepository($clientMock, $configMock, $loggerMock);
        $repository->getPlanUpgradePaths();
    }

    public function test_get_plan_pricing_levels_returns_valid_data(): void
    {
        $clientMock = $this->mockHttpGetRequest(
            url: self::API_URL . '/plan_pricing_levels',
            responseContent: '[{"id":3,"name":"High","order":1,"created_at":"2023-01-19T15:05:44.000000Z","updated_at":"2023-01-19T15:05:44.000000Z"},{"id":8,"name":"Low","order":3,"created_at":"2023-03-16T11:35:00.000000Z","updated_at":"2023-03-16T11:35:39.000000Z"}]',
        );
        $configMock = $this->getConfigMock();
        $loggerMock = $this->getLoggerMockLoggingRequestAndResponse();

        $repository = new PlanBuilderRepository($clientMock, $configMock, $loggerMock);
        $result = $repository->getPlanPricingLevels();

        $this->assertInstanceOf(PlanPricingLevel::class, $result[0]);
        $this->assertInstanceOf(PlanPricingLevel::class, $result[1]);
        $this->assertEquals(3, $result[0]->id);
        $this->assertEquals('High', $result[0]->name);
        $this->assertEquals(3, $result[1]->order);
        $this->assertEquals('2023-03-16T11:35:00.000000Z', $result[1]->createdAt);
        $this->assertEquals('2023-03-16T11:35:39.000000Z', $result[1]->updatedAt);
    }

    public function test_get_plan_pricing_levels_returns_exception(): void
    {
        $clientMock = $this->mockHttpGetRequestToThrowException(self::API_URL . '/plan_pricing_levels');
        $configMock = $this->getConfigMock();
        $loggerMock = $this->getLoggerMockLoggingRequestOnly();

        $this->expectException(Exception::class);

        $repository = new PlanBuilderRepository($clientMock, $configMock, $loggerMock);
        $repository->getPlanPricingLevels();
    }

    public function test_get_target_contract_values_returns_valid_data(): void
    {
        $clientMock = $this->mockHttpGetRequest(
            url: self::API_URL . '/target_contract_values',
            responseContent: '[{"id":5,"area_id":18,"value":893.2,"created_at":"2023-03-02T21:41:28.000000Z","updated_at":"2023-03-29T22:25:25.000000Z"},{"id":7,"area_id":24,"value":893,"created_at":"2023-03-02T21:42:04.000000Z","updated_at":"2023-04-05T15:25:00.000000Z"}]',
        );
        $configMock = $this->getConfigMock();
        $loggerMock = $this->getLoggerMockLoggingRequestAndResponse();

        $repository = new PlanBuilderRepository($clientMock, $configMock, $loggerMock);
        $result = $repository->getTargetContractValues();

        $this->assertInstanceOf(TargetContractValue::class, $result[0]);
        $this->assertInstanceOf(TargetContractValue::class, $result[1]);
        $this->assertEquals(5, $result[0]->id);
        $this->assertEquals(18, $result[0]->areaId);
        $this->assertEquals(893.2, $result[0]->value);
        $this->assertEquals('2023-03-02T21:42:04.000000Z', $result[1]->createdAt);
        $this->assertEquals('2023-04-05T15:25:00.000000Z', $result[1]->updatedAt);
    }

    public function test_get_target_contract_values_returns_exception(): void
    {
        $clientMock = $this->mockHttpGetRequestToThrowException(self::API_URL . '/target_contract_values');
        $configMock = $this->getConfigMock();
        $loggerMock = $this->getLoggerMockLoggingRequestOnly();

        $this->expectException(Exception::class);

        $repository = new PlanBuilderRepository($clientMock, $configMock, $loggerMock);
        $repository->getTargetContractValues();
    }

    public function test_get_area_plans_returns_valid_data(): void
    {
        $clientMock = $this->mockHttpGetRequest(
            url: self::API_URL . '/area_plans',
            responseContent: '[{"id":1,"area_id":null,"plan_id":1,"can_sell_percentage_threshold":null},{"id":3,"area_id":null,"plan_id":2,"can_sell_percentage_threshold":null}]',
        );
        $configMock = $this->getConfigMock();
        $loggerMock = $this->getLoggerMockLoggingRequestAndResponse();

        $repository = new PlanBuilderRepository($clientMock, $configMock, $loggerMock);
        $result = $repository->getAreaPlans();

        $this->assertInstanceOf(AreaPlan::class, $result[0]);
        $this->assertInstanceOf(AreaPlan::class, $result[1]);
        $this->assertEquals(1, $result[0]->id);
        $this->assertEquals(null, $result[0]->areaId);
        $this->assertEquals(1, $result[0]->planId);
        $this->assertEquals(null, $result[0]->createdAt);
        $this->assertEquals(null, $result[0]->updatedAt);
        $this->assertEquals(null, $result[1]->canSellPercentageThreshold);
        $this->assertEquals([], $result[1]->serviceProductIds);
        $this->assertEquals([], $result[1]->areaPlanPricings);
        $this->assertEquals([], $result[1]->addons);
    }

    public function test_get_area_plans_returns_exception(): void
    {
        $clientMock = $this->mockHttpGetRequestToThrowException(self::API_URL . '/area_plans');
        $configMock = $this->getConfigMock();
        $loggerMock = $this->getLoggerMockLoggingRequestOnly();

        $this->expectException(Exception::class);

        $repository = new PlanBuilderRepository($clientMock, $configMock, $loggerMock);
        $repository->getAreaPlans();
    }

    public function test_get_plan_categories_returns_valid_data(): void
    {
        $clientMock = $this->mockHttpGetRequest(
            url: self::API_URL . '/plan_categories',
            responseContent: '[{"id":1,"name":"Direct to Home","order":2,"created_at":"2023-01-19T15:05:21.000000Z","updated_at":"2023-01-19T15:05:21.000000Z"},{"id":2,"name":"Inside Sales","order":1,"created_at":"2023-01-19T15:05:23.000000Z","updated_at":"2023-09-08T16:02:31.000000Z"}]',
        );
        $configMock = $this->getConfigMock();
        $loggerMock = $this->getLoggerMockLoggingRequestAndResponse();

        $repository = new PlanBuilderRepository($clientMock, $configMock, $loggerMock);
        $result = $repository->getPlanCategories();

        $this->assertInstanceOf(Category::class, $result[0]);
        $this->assertInstanceOf(Category::class, $result[1]);
        $this->assertEquals(1, $result[0]->id);
        $this->assertEquals('Direct to Home', $result[0]->name);
        $this->assertEquals(1, $result[1]->order);
        $this->assertEquals('2023-01-19T15:05:23.000000Z', $result[1]->createdAt);
        $this->assertEquals('2023-09-08T16:02:31.000000Z', $result[1]->updatedAt);
    }

    public function test_get_plan_categories_returns_exception(): void
    {
        $clientMock = $this->mockHttpGetRequestToThrowException(self::API_URL . '/plan_categories');
        $configMock = $this->getConfigMock();
        $loggerMock = $this->getLoggerMockLoggingRequestOnly();

        $this->expectException(Exception::class);

        $repository = new PlanBuilderRepository($clientMock, $configMock, $loggerMock);
        $repository->getPlanCategories();
    }

    public function test_get_settings_returns_valid_data(): void
    {
        $clientMock = $this->mockHttpGetRequest(
            url: self::API_URL . '/settings',
            responseContent: '{"plan_pricing_levels":[{"id":3,"name":"High","order":1,"created_at":"2023-01-19T15:05:44.000000Z","updated_at":"2023-01-19T15:05:44.000000Z"}],"plan_service_frequencies":[{"id":2,"frequency":6,"order":1,"created_at":"2023-01-19T15:06:28.000000Z","updated_at":"2023-06-27T08:27:57.000000Z","frequency_display":"6-7"}],"plan_categories":[{"id":1,"name":"Direct to Home","order":2,"created_at":"2023-01-19T15:05:21.000000Z","updated_at":"2023-01-19T15:05:21.000000Z"}],"plan_statuses":[{"id":1,"name":"Ready","order":2,"created_at":"2023-01-19T15:06:49.000000Z","updated_at":"2023-01-19T15:06:49.000000Z"}],"agreement_lengths":[{"id":1,"name":"12 months","length":12,"unit":"month","order":1,"created_at":"2023-01-19T15:04:58.000000Z","updated_at":"2023-03-15T11:36:13.000000Z"}]}',
        );
        $configMock = $this->getConfigMock();
        $loggerMock = $this->getLoggerMockLoggingRequestAndResponse();

        $repository = new PlanBuilderRepository($clientMock, $configMock, $loggerMock);
        $result = $repository->getSettings();

        $this->assertInstanceOf(PlanPricingLevel::class, $result['planPricingLevels'][0]);
        $this->assertEquals(3, $result['planPricingLevels'][0]->id);
        $this->assertEquals('2023-01-19T15:05:44.000000Z', $result['planPricingLevels'][0]->updatedAt);
        $this->assertInstanceOf(PlanServiceFrequency::class, $result['planServiceFrequencies'][0]);
        $this->assertEquals(2, $result['planServiceFrequencies'][0]->id);
        $this->assertEquals(6, $result['planServiceFrequencies'][0]->frequency);
        $this->assertEquals(1, $result['planServiceFrequencies'][0]->order);
        $this->assertEquals('2023-01-19T15:06:28.000000Z', $result['planServiceFrequencies'][0]->createdAt);
        $this->assertEquals('2023-06-27T08:27:57.000000Z', $result['planServiceFrequencies'][0]->updatedAt);
        $this->assertEquals('6-7', $result['planServiceFrequencies'][0]->frequencyDisplay);
        $this->assertInstanceOf(Category::class, $result['planCategories'][0]);
        $this->assertEquals('Direct to Home', $result['planCategories'][0]->name);
        $this->assertInstanceOf(Status::class, $result['planStatuses'][0]);
        $this->assertEquals(1, $result['planStatuses'][0]->id);
        $this->assertEquals('Ready', $result['planStatuses'][0]->name);
        $this->assertEquals(2, $result['planStatuses'][0]->order);
        $this->assertEquals('2023-01-19T15:06:49.000000Z', $result['planStatuses'][0]->createdAt);
        $this->assertEquals('2023-01-19T15:06:49.000000Z', $result['planStatuses'][0]->updatedAt);
        $this->assertInstanceOf(AgreementLength::class, $result['agreementLengths'][0]);
        $this->assertEquals(1, $result['agreementLengths'][0]->id);
        $this->assertEquals('12 months', $result['agreementLengths'][0]->name);
        $this->assertEquals(12, $result['agreementLengths'][0]->length);
        $this->assertEquals('month', $result['agreementLengths'][0]->unit);
        $this->assertEquals(1, $result['agreementLengths'][0]->order);
        $this->assertEquals('2023-01-19T15:04:58.000000Z', $result['agreementLengths'][0]->createdAt);
        $this->assertEquals('2023-03-15T11:36:13.000000Z', $result['agreementLengths'][0]->updatedAt);
    }

    public function test_get_settings_returns_exception(): void
    {
        $clientMock = $this->mockHttpGetRequestToThrowException(self::API_URL . '/settings');
        $configMock = $this->getConfigMock();
        $loggerMock = $this->getLoggerMockLoggingRequestOnly();

        $this->expectException(Exception::class);

        $repository = new PlanBuilderRepository($clientMock, $configMock, $loggerMock);
        $repository->getSettings();
    }
}
