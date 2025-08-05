<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories\PlanBuilder;

use App\DTO\PlanBuilder\Addon;
use App\DTO\PlanBuilder\AreaPlan;
use App\DTO\PlanBuilder\AreaPlanPricing;
use App\DTO\PlanBuilder\Plan;
use App\DTO\PlanBuilder\Product;
use App\DTO\PlanBuilder\SearchPlansDTO;
use App\Repositories\PlanBuilder\PlanBuilderRepository;
use Exception;

class PlanBuilderRepositoryPlansTest extends PlanBuilderRepositoryBase
{
    public function test_get_plans_returns_valid_data(): void
    {
        $clientMock = $this->mockHttpGetRequest(
            url: self::API_URL . '/plans',
            responseContent: '[{"id":1,"ext_reference_id":"2827","name":"Pro","start_on":"2023-01-01","end_on":"2080-01-01","plan_service_frequency_id":2,"plan_status_id":2,"bill_monthly":false,"initial_discount":10,"recurring_discount":10,"created_at":"2023-01-21T16:59:16.000000Z","updated_at":"2023-06-27T14:15:10.000000Z","order":0,"plan_category_ids":[1,2,3]},{"id":2,"ext_reference_id":"1800","name":"Pro +","start_on":"2023-01-01","end_on":"2080-01-01","plan_service_frequency_id":2,"plan_status_id":2,"bill_monthly":true,"initial_discount":0,"recurring_discount":10,"created_at":"2023-01-21T16:59:25.000000Z","updated_at":"2023-06-27T14:15:27.000000Z","order":0,"plan_category_ids":[1,2,3]}]',
        );
        $configMock = $this->getConfigMock();
        $loggerMock = $this->getLoggerMockLoggingRequestAndResponse();

        $repository = new PlanBuilderRepository($clientMock, $configMock, $loggerMock);
        $result = $repository->getPlans();

        $this->assertInstanceOf(Plan::class, $result[0]);
        $this->assertInstanceOf(Plan::class, $result[1]);
        $this->assertEquals(1, $result[0]->id);
        $this->assertEquals(2827, $result[0]->extReferenceId);
        $this->assertEquals('Pro', $result[0]->name);
        $this->assertEquals('2023-01-01', $result[0]->startOn);
        $this->assertEquals('2080-01-01', $result[0]->endOn);
        $this->assertEquals(2, $result[0]->planServiceFrequencyId);
        $this->assertEquals(2, $result[0]->planStatusId);
        $this->assertEquals(false, $result[0]->billMonthly);
        $this->assertEquals(0, $result[1]->initialDiscount);
        $this->assertEquals(10, $result[1]->recurringDiscount);
        $this->assertEquals('2023-01-21T16:59:25.000000Z', $result[1]->createdAt);
        $this->assertEquals('2023-06-27T14:15:27.000000Z', $result[1]->updatedAt);
        $this->assertEquals(0, $result[1]->order);
        $this->assertEquals([1, 2, 3], $result[1]->planCategoryIds);
    }

    public function test_get_plans_returns_exception(): void
    {
        $clientMock = $this->mockHttpGetRequestToThrowException(self::API_URL . '/plans');
        $configMock = $this->getConfigMock();
        $loggerMock = $this->getLoggerMockLoggingRequestOnly();

        $this->expectException(Exception::class);

        $repository = new PlanBuilderRepository($clientMock, $configMock, $loggerMock);
        $repository->getPlans();
    }

    public function test_get_plan_returns_valid_data(): void
    {
        $planId = 25;
        $clientMock = $this->mockHttpGetRequest(
            url: sprintf('%s/plans/%d', self::API_URL, $planId),
            responseContent: '{"id":25,"ext_reference_id":"1800","name":"Pro +","start_on":"2023-01-02","end_on":"2080-01-02","plan_service_frequency_id":2,"plan_status_id":3,"bill_monthly":true,"initial_discount":0,"recurring_discount":10,"created_at":"2023-08-24T23:15:52.000000Z","updated_at":"2023-08-24T23:15:52.000000Z","order":0,"plan_category_ids":[8],"default_area_plan":{"id":479,"plan_id":25,"area_id":null,"created_at":"2023-08-24T23:15:52.000000Z","updated_at":"2023-08-24T23:15:52.000000Z","can_sell_percentage_threshold":null,"service_product_ids":[13, 4, 19, 20, 5],"area_plan_pricings":[{"id":63204,"plan_pricing_level_id":3,"area_plan_id":479,"initial_min":29,"initial_max":399,"recurring_min":125,"recurring_max":219,"created_at":null,"updated_at":null}, {"id":63205,"plan_pricing_level_id":9,"area_plan_id":479,"initial_min":29,"initial_max":399,"recurring_min":125,"recurring_max":189,"created_at":null,"updated_at":null}, {"id":63206,"plan_pricing_level_id":8,"area_plan_id":479,"initial_min":29,"initial_max":399,"recurring_min":125,"recurring_max":169,"created_at":null,"updated_at":null}],"addons":[{"id":231464,"area_plan_id":479,"product_id":1,"is_recurring":false,"initial_min":0,"initial_max":50,"recurring_min":12.3,"recurring_max":0,"created_at":null,"updated_at":null}, {"id":231465,"area_plan_id":479,"product_id":9,"is_recurring":true,"initial_min":0,"initial_max":99,"recurring_min":49,"recurring_max":99,"created_at":null,"updated_at":null}]},"area_plans":[	{"id":480,"plan_id":25,"area_id":18,"created_at":"2023-08-24T23:15:52.000000Z","updated_at":"2023-08-24T23:15:52.000000Z","can_sell_percentage_threshold":null,"service_product_ids":[5, 13, 4, 19, 20],"area_plan_pricings":[{"id":63207,"plan_pricing_level_id":3,"area_plan_id":480,"initial_min":29,"initial_max":399,"recurring_min":139,"recurring_max":219,"created_at":null,"updated_at":null}, {"id":63208,"plan_pricing_level_id":9,"area_plan_id":480,"initial_min":29,"initial_max":399,"recurring_min":139,"recurring_max":189,"created_at":null,"updated_at":null}, {"id":63209,"plan_pricing_level_id":8,"area_plan_id":480,"initial_min":29,"initial_max":100,"recurring_min":139,"recurring_max":169,"created_at":null,"updated_at":null}],"addons":[{"id":231474,"area_plan_id":480,"product_id":1,"is_recurring":false,"initial_min":0,"initial_max":50,"recurring_min":12.3,"recurring_max":0,"created_at":null,"updated_at":null}, {"id":231475,"area_plan_id":480,"product_id":9,"is_recurring":true,"initial_min":0,"initial_max":99,"recurring_min":49,"recurring_max":99,"created_at":null,"updated_at":null}]}, {"id":481,"plan_id":25,"area_id":86,"created_at":"2023-08-24T23:15:52.000000Z","updated_at":"2023-08-24T23:15:52.000000Z","can_sell_percentage_threshold":null,"service_product_ids":[5, 13, 4, 19, 20],"area_plan_pricings":[{"id":63210,"plan_pricing_level_id":3,"area_plan_id":481,"initial_min":69,"initial_max":399,"recurring_min":149,"recurring_max":219,"created_at":null,"updated_at":null}, {"id":63211,"plan_pricing_level_id":9,"area_plan_id":481,"initial_min":69,"initial_max":100,"recurring_min":149,"recurring_max":189,"created_at":null,"updated_at":null}, {"id":63212,"plan_pricing_level_id":8,"area_plan_id":481,"initial_min":69,"initial_max":399,"recurring_min":149,"recurring_max":169,"created_at":null,"updated_at":null}],"addons":[{"id":231486,"area_plan_id":481,"product_id":10,"is_recurring":false,"initial_min":0,"initial_max":30,"recurring_min":0,"recurring_max":0,"created_at":null,"updated_at":null}, {"id":231487,"area_plan_id":481,"product_id":11,"is_recurring":false,"initial_min":0,"initial_max":30,"recurring_min":0,"recurring_max":0,"created_at":null,"updated_at":null}]}],"agreement_length_ids":[1, 3, 2]}',
        );
        $configMock = $this->getConfigMock();
        $loggerMock = $this->getLoggerMockLoggingRequestAndResponse();

        $repository = new PlanBuilderRepository($clientMock, $configMock, $loggerMock);
        $result = $repository->getPlan($planId);

        $this->assertInstanceOf(Plan::class, $result);

        $this->assertEquals($planId, $result->id);
        $this->assertEquals(1800, $result->extReferenceId);
        $this->assertEquals('Pro +', $result->name);
        $this->assertEquals('2023-01-02', $result->startOn);
        $this->assertEquals('2080-01-02', $result->endOn);
        $this->assertEquals(2, $result->planServiceFrequencyId);
        $this->assertEquals(3, $result->planStatusId);
        $this->assertEquals(true, $result->billMonthly);
        $this->assertEquals(0, $result->initialDiscount);
        $this->assertEquals(10, $result->recurringDiscount);
        $this->assertEquals('2023-08-24T23:15:52.000000Z', $result->createdAt);
        $this->assertEquals('2023-08-24T23:15:52.000000Z', $result->updatedAt);
        $this->assertEquals(0, $result->order);
        $this->assertEquals([8], $result->planCategoryIds);
        $this->assertEquals([1, 3, 2], $result->agreementLengthIds);

        $this->assertInstanceOf(AreaPlan::class, $result->defaultAreaPlan);
        $this->assertInstanceOf(AreaPlanPricing::class, $result->defaultAreaPlan->areaPlanPricings[0]);
        $this->assertInstanceOf(Addon::class, $result->defaultAreaPlan->addons[0]);
        $this->assertInstanceOf(AreaPlan::class, $result->areaPlans[0]);
        $this->assertInstanceOf(AreaPlanPricing::class, $result->areaPlans[0]->areaPlanPricings[1]);
        $this->assertInstanceOf(Addon::class, $result->areaPlans[0]->addons[1]);
        $this->assertInstanceOf(AreaPlan::class, $result->areaPlans[1]);
        $this->assertInstanceOf(AreaPlanPricing::class, $result->areaPlans[1]->areaPlanPricings[0]);
        $this->assertInstanceOf(Addon::class, $result->areaPlans[1]->addons[0]);

        $this->assertEquals(231464, $result->defaultAreaPlan->addons[0]->id);
        $this->assertEquals(479, $result->defaultAreaPlan->addons[0]->areaPlanId);
        $this->assertEquals(1, $result->defaultAreaPlan->addons[0]->productId);
        $this->assertEquals(false, $result->defaultAreaPlan->addons[0]->isRecurring);
        $this->assertEquals(0.0, $result->areaPlans[0]->addons[1]->initialMin);
        $this->assertEquals(0.0, $result->areaPlans[0]->addons[1]->initialMax);
        $this->assertEquals(49.0, $result->areaPlans[0]->addons[1]->recurringMin);
        $this->assertEquals(0.0, $result->areaPlans[1]->addons[0]->recurringMax);
        $this->assertEquals(null, $result->areaPlans[1]->addons[0]->createdAt);
        $this->assertEquals(null, $result->areaPlans[1]->addons[0]->updatedAt);
    }

    public function test_get_plan_returns_exception(): void
    {
        $planId = 25;
        $clientMock = $this->mockHttpGetRequestToThrowException(
            sprintf('%s/plans/%d', self::API_URL, $planId)
        );
        $configMock = $this->getConfigMock();
        $loggerMock = $this->getLoggerMockLoggingRequestOnly();

        $this->expectException(Exception::class);

        $repository = new PlanBuilderRepository($clientMock, $configMock, $loggerMock);
        $repository->getPlan($planId);
    }

    public function test_get_plan_with_products_returns_valid_data(): void
    {
        $planId = 25;
        $clientMock = $this->mockHttpGetRequest(
            url: sprintf('%s/plans_with_products/%d', self::API_URL, $planId),
            responseContent: '{"plan":{"id":25,"ext_reference_id":"1800","name":"Pro +","start_on":"2023-01-03","end_on":"2080-01-03","plan_service_frequency_id":2,"plan_status_id":3,"bill_monthly":true,"initial_discount":0,"recurring_discount":10,"created_at":"2023-08-24T23:25:52.000000Z","updated_at":"2023-08-24T23:25:52.000000Z","order":0,"plan_category_ids":[8],"default_area_plan":{"id":479,"plan_id":25,"area_id":null,"created_at":"2023-08-24T23:25:52.000000Z","updated_at":"2023-08-24T23:25:52.000000Z","can_sell_percentage_threshold":null,"service_product_ids":[13, 4, 19, 20, 5],"area_plan_pricings":[{"id":63204,"plan_pricing_level_id":3,"area_plan_id":479,"initial_min":29,"initial_max":399,"recurring_min":125,"recurring_max":219,"created_at":null,"updated_at":null}, {"id":63205,"plan_pricing_level_id":9,"area_plan_id":479,"initial_min":29,"initial_max":399,"recurring_min":125,"recurring_max":189,"created_at":null,"updated_at":null}, {"id":63206,"plan_pricing_level_id":8,"area_plan_id":479,"initial_min":29,"initial_max":399,"recurring_min":125,"recurring_max":169,"created_at":null,"updated_at":null}],"addons":[{"id":231464,"area_plan_id":479,"product_id":1,"is_recurring":false,"initial_min":0,"initial_max":50,"recurring_min":12.3,"recurring_max":0,"created_at":null,"updated_at":null}, {"id":231465,"area_plan_id":479,"product_id":9,"is_recurring":true,"initial_min":0,"initial_max":99,"recurring_min":49,"recurring_max":99,"created_at":null,"updated_at":null}, {"id":231466,"area_plan_id":479,"product_id":10,"is_recurring":false,"initial_min":0,"initial_max":30,"recurring_min":0,"recurring_max":0,"created_at":null,"updated_at":null}, {"id":231467,"area_plan_id":479,"product_id":11,"is_recurring":false,"initial_min":0,"initial_max":30,"recurring_min":0,"recurring_max":0,"created_at":null,"updated_at":null}, {"id":231468,"area_plan_id":479,"product_id":12,"is_recurring":false,"initial_min":0,"initial_max":30,"recurring_min":0,"recurring_max":0,"created_at":null,"updated_at":null}, {"id":231469,"area_plan_id":479,"product_id":14,"is_recurring":false,"initial_min":0,"initial_max":40,"recurring_min":0,"recurring_max":0,"created_at":null,"updated_at":null}, {"id":231470,"area_plan_id":479,"product_id":15,"is_recurring":false,"initial_min":0,"initial_max":50,"recurring_min":0,"recurring_max":0,"created_at":null,"updated_at":null}, {"id":231471,"area_plan_id":479,"product_id":16,"is_recurring":false,"initial_min":0,"initial_max":40,"recurring_min":0,"recurring_max":0,"created_at":null,"updated_at":null}, {"id":231472,"area_plan_id":479,"product_id":17,"is_recurring":false,"initial_min":0,"initial_max":50,"recurring_min":0,"recurring_max":0,"created_at":null,"updated_at":null}, {"id":231473,"area_plan_id":479,"product_id":2,"is_recurring":true,"initial_min":149,"initial_max":399,"recurring_min":58,"recurring_max":99,"created_at":null,"updated_at":null}]},"area_plans":[{"id":480,"plan_id":25,"area_id":18,"created_at":"2023-08-24T23:25:52.000000Z","updated_at":"2023-08-24T23:25:52.000000Z","can_sell_percentage_threshold":null,"service_product_ids":[5, 13, 4, 19, 20],"area_plan_pricings":[{"id":63207,"plan_pricing_level_id":3,"area_plan_id":480,"initial_min":29,"initial_max":399,"recurring_min":139,"recurring_max":219,"created_at":null,"updated_at":null}, {"id":63208,"plan_pricing_level_id":9,"area_plan_id":480,"initial_min":29,"initial_max":399,"recurring_min":139,"recurring_max":189,"created_at":null,"updated_at":null}, {"id":63209,"plan_pricing_level_id":8,"area_plan_id":480,"initial_min":29,"initial_max":100,"recurring_min":139,"recurring_max":169,"created_at":null,"updated_at":null}],"addons":[{"id":231474,"area_plan_id":480,"product_id":1,"is_recurring":false,"initial_min":0,"initial_max":50,"recurring_min":12.3,"recurring_max":0,"created_at":null,"updated_at":null}, {"id":231475,"area_plan_id":480,"product_id":9,"is_recurring":true,"initial_min":0,"initial_max":99,"recurring_min":49,"recurring_max":99,"created_at":null,"updated_at":null}]}, {"id":481,"plan_id":25,"area_id":86,"created_at":"2023-08-24T23:25:52.000000Z","updated_at":"2023-08-24T23:25:52.000000Z","can_sell_percentage_threshold":null,"service_product_ids":[5, 13, 4, 19, 20],"area_plan_pricings":[{"id":63210,"plan_pricing_level_id":3,"area_plan_id":481,"initial_min":69,"initial_max":399,"recurring_min":149,"recurring_max":219,"created_at":null,"updated_at":null}, {"id":63211,"plan_pricing_level_id":9,"area_plan_id":481,"initial_min":69,"initial_max":100,"recurring_min":149,"recurring_max":189,"created_at":null,"updated_at":null}, {"id":63212,"plan_pricing_level_id":8,"area_plan_id":481,"initial_min":69,"initial_max":399,"recurring_min":149,"recurring_max":169,"created_at":null,"updated_at":null}],"addons":[{"id":231484,"area_plan_id":481,"product_id":1,"is_recurring":false,"initial_min":0,"initial_max":50,"recurring_min":12.3,"recurring_max":0,"created_at":null,"updated_at":null}, {"id":231485,"area_plan_id":481,"product_id":9,"is_recurring":true,"initial_min":0,"initial_max":99,"recurring_min":49,"recurring_max":99,"created_at":null,"updated_at":null}]}],"agreement_length_ids":[1, 3, 2]},"products":[{"id":1,"product_sub_category_id":2,"ext_reference_id":"1617","name":"Accessory Structure","order":2,"image":"https:\/\/s3.amazonaws.com\/aptive.staging-01.product-manager-api.bucket\/product_images\/1\/Accessory%20Structure.png?X-Amz-Content-Sha256=UNSIGNED-PAYLOAD&X-Amz-Security-Token=IQoJb3JpZ2luX2VjEAsaCXVzLWVhc3QtMSJGMEQCIFz9OyG6dsuXVUqL%2FZ%2BEVAHl1bcqHrGny7tmJXaeeyRMAiAoj6bSJo3e99poUtqVL62LpPS8IT%2FSV5hVKotGkpud3SqXBQgUEAMaDDgyNTc0NDkyMzk4NCIMgvlcKtcfYLVnbkNzKvQE3GhfLIPJbFqN763WA0jZEjAm9qzs6qd37Y3lBw6piLxSdYTvH3miAo67KQkGWEocM51NykcV%2Bb3MimYvIYQgsE0cMU%2FG0InzqrXAaBCWZyVKVTd6VsiyJCeK26ttz0zJ1Y0ckA6YE4EawObUOmFevSO3Hbd2DC2mc8ipW1hMWEL%2FymMpRVJG8roW5Hx6K7zJA1Lxp3okX976Olv6qfp62uT1HymqNH3h1mXhXBUXHHOAvw%2FpSWW9rRXrzVUslQxQFJs4KBi2jBI07mJDOcPbV3nbYz2%2B1UZvSDX45xtJF8FJKBisOsIGSl7K%2BteAA2b8KkB8Kb8%2BLot0LHsHbM42hjcJshRtIu8oIgKsjSRCsSea7C%2FNE5bIz0j9hjq4aTZ1Rmu5Mgw4SfBgZnidwab0hryUaQ9eCYKY5BPi1DmZ7Qi3X6Ci4DEpWREd%2FYJJ0MbrkrZ8hbfnQKu9lkaFGmWvIanTB7I9P59UfHbqkbrIqM1zG57Yb8vPaZCz3CzXYV7UHJx7QNDDLuNHXuFZViZ6SK00L75dPqE1D7IlFFiy9vnUNvtyaCLtIF5%2BHc4ggBqzNl0UkmheM1nlTHOBt8QTKgejiBAVZNDr12YqS2Df5MNsuQybWDTuvrwY45XvHRjoaOQutgprXJaVmDvGBlWvNc1oLFsZXc9WyuLZ7Khx4hSSdap0PZb%2F3TvYI%2BJMp4OtVid9L8fGeBBNCJEziOTs4KZ8XRHsgKCsSsICpEgnY5fRwsk%2FrqrKjha44%2FCFRNj2PLCWNO4nxQqb9zNv2grK4jtHmQ6PTPQ3biUSrK8%2FsWGXMhaU20GRWdgICOQVQ%2BiwgFrZ8jD9ueqoBjqcAcDlIU4J415FtCWQFOz5Pe1iQvJADDP3ojtGLl5CWgYbtrpE0s95u40uChgBxhlm70h6Tf26P2L9d4BvjmtGCSCwMP6%2FaCK3tRVBIRdd0iZIqD4MYTylQmMoEoHcDq5%2BOgKvAwJWEG%2FFbwI9LcRLIi5VrpCs0eCMGUMpSWEzap84%2FgborKlHKtb3bz1XwlCs4tlp5c0CBmr2J15%2Fbg%3D%3D&X-Amz-Algorithm=AWS4-HMAC-SHA256&X-Amz-Credential=ASIA4AQR3TVIFWGLSUQU%2F20231002%2Fus-east-1%2Fs3%2Faws4_request&X-Amz-Date=20231002T103541Z&X-Amz-SignedHeaders=host&X-Amz-Expires=604800&X-Amz-Signature=a2c1d6f77505c033d6193b847e05c90742e2d25f20270c09da92710b9ab79a42","is_recurring":false,"initial_min":0,"initial_max":50,"recurring_min":12.3,"recurring_max":0,"created_at":"2022-12-01T23:03:44.000000Z","updated_at":"2023-06-13T16:08:30.000000Z","needs_customer_support":false,"description":null,"image_name":"Accessory Structure.png"}, {"id":9,"product_sub_category_id":2,"ext_reference_id":"1615","name":"Mosquitoes","order":0,"image":"https:\/\/s3.amazonaws.com\/aptive.staging-01.product-manager-api.bucket\/product_images\/9\/mosquito.png?X-Amz-Content-Sha256=UNSIGNED-PAYLOAD&X-Amz-Security-Token=IQoJb3JpZ2luX2VjEAsaCXVzLWVhc3QtMSJGMEQCIFz9OyG6dsuXVUqL%2FZ%2BEVAHl1bcqHrGny7tmJXaeeyRMAiAoj6bSJo3e99poUtqVL62LpPS8IT%2FSV5hVKotGkpud3SqXBQgUEAMaDDgyNTc0NDkyMzk4NCIMgvlcKtcfYLVnbkNzKvQE3GhfLIPJbFqN763WA0jZEjAm9qzs6qd37Y3lBw6piLxSdYTvH3miAo67KQkGWEocM51NykcV%2Bb3MimYvIYQgsE0cMU%2FG0InzqrXAaBCWZyVKVTd6VsiyJCeK26ttz0zJ1Y0ckA6YE4EawObUOmFevSO3Hbd2DC2mc8ipW1hMWEL%2FymMpRVJG8roW5Hx6K7zJA1Lxp3okX976Olv6qfp62uT1HymqNH3h1mXhXBUXHHOAvw%2FpSWW9rRXrzVUslQxQFJs4KBi2jBI07mJDOcPbV3nbYz2%2B1UZvSDX45xtJF8FJKBisOsIGSl7K%2BteAA2b8KkB8Kb8%2BLot0LHsHbM42hjcJshRtIu8oIgKsjSRCsSea7C%2FNE5bIz0j9hjq4aTZ1Rmu5Mgw4SfBgZnidwab0hryUaQ9eCYKY5BPi1DmZ7Qi3X6Ci4DEpWREd%2FYJJ0MbrkrZ8hbfnQKu9lkaFGmWvIanTB7I9P59UfHbqkbrIqM1zG57Yb8vPaZCz3CzXYV7UHJx7QNDDLuNHXuFZViZ6SK00L75dPqE1D7IlFFiy9vnUNvtyaCLtIF5%2BHc4ggBqzNl0UkmheM1nlTHOBt8QTKgejiBAVZNDr12YqS2Df5MNsuQybWDTuvrwY45XvHRjoaOQutgprXJaVmDvGBlWvNc1oLFsZXc9WyuLZ7Khx4hSSdap0PZb%2F3TvYI%2BJMp4OtVid9L8fGeBBNCJEziOTs4KZ8XRHsgKCsSsICpEgnY5fRwsk%2FrqrKjha44%2FCFRNj2PLCWNO4nxQqb9zNv2grK4jtHmQ6PTPQ3biUSrK8%2FsWGXMhaU20GRWdgICOQVQ%2BiwgFrZ8jD9ueqoBjqcAcDlIU4J415FtCWQFOz5Pe1iQvJADDP3ojtGLl5CWgYbtrpE0s95u40uChgBxhlm70h6Tf26P2L9d4BvjmtGCSCwMP6%2FaCK3tRVBIRdd0iZIqD4MYTylQmMoEoHcDq5%2BOgKvAwJWEG%2FFbwI9LcRLIi5VrpCs0eCMGUMpSWEzap84%2FgborKlHKtb3bz1XwlCs4tlp5c0CBmr2J15%2Fbg%3D%3D&X-Amz-Algorithm=AWS4-HMAC-SHA256&X-Amz-Credential=ASIA4AQR3TVIFWGLSUQU%2F20231002%2Fus-east-1%2Fs3%2Faws4_request&X-Amz-Date=20231002T103541Z&X-Amz-SignedHeaders=host&X-Amz-Expires=604800&X-Amz-Signature=2b860e42f8368d5c5463020a4f1a7c4edf23efc48182e0d48af8deabf5378bc6","is_recurring":true,"initial_min":0,"initial_max":99,"recurring_min":49,"recurring_max":99,"created_at":"2023-02-06T11:41:43.000000Z","updated_at":"2023-09-25T10:54:11.000000Z","needs_customer_support":false,"description":null,"image_name":"mosquito.png"}]}',
        );
        $configMock = $this->getConfigMock();
        $loggerMock = $this->getLoggerMockLoggingRequestAndResponse();

        $repository = new PlanBuilderRepository($clientMock, $configMock, $loggerMock);
        $result = $repository->getPlanWithProducts($planId);

        $this->assertInstanceOf(Plan::class, $result['plan']);

        $this->assertEquals($planId, $result['plan']->id);
        $this->assertEquals(1800, $result['plan']->extReferenceId);
        $this->assertEquals('Pro +', $result['plan']->name);
        $this->assertEquals('2023-01-03', $result['plan']->startOn);
        $this->assertEquals('2080-01-03', $result['plan']->endOn);
        $this->assertEquals(2, $result['plan']->planServiceFrequencyId);
        $this->assertEquals(3, $result['plan']->planStatusId);
        $this->assertEquals(true, $result['plan']->billMonthly);
        $this->assertEquals(0, $result['plan']->initialDiscount);
        $this->assertEquals(10, $result['plan']->recurringDiscount);
        $this->assertEquals('2023-08-24T23:25:52.000000Z', $result['plan']->createdAt);
        $this->assertEquals('2023-08-24T23:25:52.000000Z', $result['plan']->updatedAt);
        $this->assertEquals(0, $result['plan']->order);
        $this->assertEquals([8], $result['plan']->planCategoryIds);
        $this->assertEquals([1, 3, 2], $result['plan']->agreementLengthIds);

        $this->assertInstanceOf(AreaPlan::class, $result['plan']->defaultAreaPlan);
        $this->assertInstanceOf(AreaPlanPricing::class, $result['plan']->defaultAreaPlan->areaPlanPricings[0]);
        $this->assertInstanceOf(Addon::class, $result['plan']->defaultAreaPlan->addons[0]);
        $this->assertInstanceOf(AreaPlan::class, $result['plan']->areaPlans[0]);
        $this->assertInstanceOf(AreaPlanPricing::class, $result['plan']->areaPlans[0]->areaPlanPricings[1]);
        $this->assertInstanceOf(Addon::class, $result['plan']->areaPlans[0]->addons[1]);
        $this->assertInstanceOf(AreaPlan::class, $result['plan']->areaPlans[1]);
        $this->assertInstanceOf(AreaPlanPricing::class, $result['plan']->areaPlans[1]->areaPlanPricings[0]);
        $this->assertInstanceOf(Addon::class, $result['plan']->areaPlans[1]->addons[0]);

        $this->assertEquals(231464, $result['plan']->defaultAreaPlan->addons[0]->id);
        $this->assertEquals(479, $result['plan']->defaultAreaPlan->addons[0]->areaPlanId);
        $this->assertEquals(1, $result['plan']->defaultAreaPlan->addons[0]->productId);
        $this->assertEquals(false, $result['plan']->defaultAreaPlan->addons[0]->isRecurring);
        $this->assertEquals(0.0, $result['plan']->areaPlans[0]->addons[1]->initialMin);
        $this->assertEquals(0.0, $result['plan']->areaPlans[0]->addons[1]->initialMax);
        $this->assertEquals(49.0, $result['plan']->areaPlans[0]->addons[1]->recurringMin);
        $this->assertEquals(0.0, $result['plan']->areaPlans[1]->addons[0]->recurringMax);
        $this->assertEquals(null, $result['plan']->areaPlans[1]->addons[0]->createdAt);
        $this->assertEquals(null, $result['plan']->areaPlans[1]->addons[0]->updatedAt);

        $this->assertInstanceOf(Product::class, $result['products'][0]);
        $this->assertInstanceOf(Product::class, $result['products'][1]);

        $this->assertEquals(1, $result['products'][0]->id);
        $this->assertEquals(2, $result['products'][0]->productSubCategoryId);
        $this->assertEquals(1617, $result['products'][0]->extReferenceId);
        $this->assertEquals('Accessory Structure', $result['products'][0]->name);
        $this->assertEquals(2, $result['products'][0]->order);
        $this->assertEquals('https://s3.amazonaws.com/aptive.staging-01.product-manager-api.bucket/product_images/1/Accessory%20Structure.png?X-Amz-Content-Sha256=UNSIGNED-PAYLOAD&X-Amz-Security-Token=IQoJb3JpZ2luX2VjEAsaCXVzLWVhc3QtMSJGMEQCIFz9OyG6dsuXVUqL%2FZ%2BEVAHl1bcqHrGny7tmJXaeeyRMAiAoj6bSJo3e99poUtqVL62LpPS8IT%2FSV5hVKotGkpud3SqXBQgUEAMaDDgyNTc0NDkyMzk4NCIMgvlcKtcfYLVnbkNzKvQE3GhfLIPJbFqN763WA0jZEjAm9qzs6qd37Y3lBw6piLxSdYTvH3miAo67KQkGWEocM51NykcV%2Bb3MimYvIYQgsE0cMU%2FG0InzqrXAaBCWZyVKVTd6VsiyJCeK26ttz0zJ1Y0ckA6YE4EawObUOmFevSO3Hbd2DC2mc8ipW1hMWEL%2FymMpRVJG8roW5Hx6K7zJA1Lxp3okX976Olv6qfp62uT1HymqNH3h1mXhXBUXHHOAvw%2FpSWW9rRXrzVUslQxQFJs4KBi2jBI07mJDOcPbV3nbYz2%2B1UZvSDX45xtJF8FJKBisOsIGSl7K%2BteAA2b8KkB8Kb8%2BLot0LHsHbM42hjcJshRtIu8oIgKsjSRCsSea7C%2FNE5bIz0j9hjq4aTZ1Rmu5Mgw4SfBgZnidwab0hryUaQ9eCYKY5BPi1DmZ7Qi3X6Ci4DEpWREd%2FYJJ0MbrkrZ8hbfnQKu9lkaFGmWvIanTB7I9P59UfHbqkbrIqM1zG57Yb8vPaZCz3CzXYV7UHJx7QNDDLuNHXuFZViZ6SK00L75dPqE1D7IlFFiy9vnUNvtyaCLtIF5%2BHc4ggBqzNl0UkmheM1nlTHOBt8QTKgejiBAVZNDr12YqS2Df5MNsuQybWDTuvrwY45XvHRjoaOQutgprXJaVmDvGBlWvNc1oLFsZXc9WyuLZ7Khx4hSSdap0PZb%2F3TvYI%2BJMp4OtVid9L8fGeBBNCJEziOTs4KZ8XRHsgKCsSsICpEgnY5fRwsk%2FrqrKjha44%2FCFRNj2PLCWNO4nxQqb9zNv2grK4jtHmQ6PTPQ3biUSrK8%2FsWGXMhaU20GRWdgICOQVQ%2BiwgFrZ8jD9ueqoBjqcAcDlIU4J415FtCWQFOz5Pe1iQvJADDP3ojtGLl5CWgYbtrpE0s95u40uChgBxhlm70h6Tf26P2L9d4BvjmtGCSCwMP6%2FaCK3tRVBIRdd0iZIqD4MYTylQmMoEoHcDq5%2BOgKvAwJWEG%2FFbwI9LcRLIi5VrpCs0eCMGUMpSWEzap84%2FgborKlHKtb3bz1XwlCs4tlp5c0CBmr2J15%2Fbg%3D%3D&X-Amz-Algorithm=AWS4-HMAC-SHA256&X-Amz-Credential=ASIA4AQR3TVIFWGLSUQU%2F20231002%2Fus-east-1%2Fs3%2Faws4_request&X-Amz-Date=20231002T103541Z&X-Amz-SignedHeaders=host&X-Amz-Expires=604800&X-Amz-Signature=a2c1d6f77505c033d6193b847e05c90742e2d25f20270c09da92710b9ab79a42', $result['products'][0]->image);
        $this->assertEquals(false, $result['products'][0]->isRecurring);
        $this->assertEquals(0.0, $result['products'][0]->initialMin);
        $this->assertEquals(50.0, $result['products'][0]->initialMax);

        $this->assertEquals(49.0, $result['products'][1]->recurringMin);
        $this->assertEquals(99.0, $result['products'][1]->recurringMax);
        $this->assertEquals('2023-02-06T11:41:43.000000Z', $result['products'][1]->createdAt);
        $this->assertEquals('2023-02-06T11:41:43.000000Z', $result['products'][1]->updatedAt);
        $this->assertEquals(false, $result['products'][1]->needsCustomerSupport);
        $this->assertEquals(null, $result['products'][1]->description);
        $this->assertEquals('mosquito.png', $result['products'][1]->imageName);
    }

    public function test_get_plan_with_products_returns_exception(): void
    {
        $planId = 25;
        $clientMock = $this->mockHttpGetRequestToThrowException(
            sprintf('%s/plans_with_products/%d', self::API_URL, $planId)
        );
        $configMock = $this->getConfigMock();
        $loggerMock = $this->getLoggerMockLoggingRequestOnly();

        $this->expectException(Exception::class);

        $repository = new PlanBuilderRepository($clientMock, $configMock, $loggerMock);
        $repository->getPlanWithProducts($planId);
    }


    public function test_search_plans_returns_valid_data(): void
    {
        $dto = new SearchPlansDTO(
            planStatusId: 3,
            planCategoryId: 8,
            extReferenceId: '2827',
            officeId:39,
        );

        $clientMock = $this->mockHttpGetRequest(
            url: sprintf('%s/plans/filter', self::API_URL),
            query: $dto->toArray(),
            responseContent: '[{"id":22,"ext_reference_id":"2827","name":"Pro","start_on":"2023-01-04","end_on":"2080-01-04","plan_service_frequency_id":2,"plan_status_id":3,"bill_monthly":false,"created_at":"2023-08-24T23:15:12.000000Z","updated_at":"2023-08-24T23:15:12.000000Z","order":0,"area_plan_pricings":[{"id":62817,"plan_pricing_level_id":3,"area_plan_id":350,"initial_min":39,"initial_max":399,"recurring_min":105,"recurring_max":199,"created_at":null,"updated_at":null}, {"id":62818,"plan_pricing_level_id":9,"area_plan_id":350,"initial_min":39,"initial_max":399,"recurring_min":105,"recurring_max":169,"created_at":null,"updated_at":null}, {"id":62819,"plan_pricing_level_id":8,"area_plan_id":350,"initial_min":39,"initial_max":399,"recurring_min":105,"recurring_max":149,"created_at":null,"updated_at":null}],"services":[19, 20],"addons":[{"id":229793,"area_plan_id":350,"product_id":1,"is_recurring":false,"initial_min":0,"initial_max":50,"recurring_min":12.3,"recurring_max":0,"created_at":null,"updated_at":null}, {"id":229794,"area_plan_id":350,"product_id":2,"is_recurring":true,"initial_min":149,"initial_max":399,"recurring_min":59,"recurring_max":99,"created_at":null,"updated_at":null}],"area_id":49,"area_plan_id":350,"plan_category_ids":[8],"agreement_length_ids":[1, 2, 3]}]',
        );
        $configMock = $this->getConfigMock();
        $loggerMock = $this->getLoggerMockLoggingRequestAndResponse();

        $repository = new PlanBuilderRepository($clientMock, $configMock, $loggerMock);
        $result = $repository->searchPlans($dto);

        $this->assertInstanceOf(Plan::class, $result[0]);

        $this->assertEquals(22, $result[0]->id);
        $this->assertEquals(2827, $result[0]->extReferenceId);
        $this->assertEquals('Pro', $result[0]->name);
        $this->assertEquals('2023-01-04', $result[0]->startOn);
        $this->assertEquals('2080-01-04', $result[0]->endOn);
        $this->assertEquals(2, $result[0]->planServiceFrequencyId);
        $this->assertEquals(3, $result[0]->planStatusId);
        $this->assertEquals(false, $result[0]->billMonthly);
        $this->assertEquals(null, $result[0]->initialDiscount);
        $this->assertEquals(null, $result[0]->recurringDiscount);
        $this->assertEquals('2023-08-24T23:15:12.000000Z', $result[0]->createdAt);
        $this->assertEquals('2023-08-24T23:15:12.000000Z', $result[0]->updatedAt);
        $this->assertEquals(0, $result[0]->order);
        $this->assertEquals([8], $result[0]->planCategoryIds);

        $this->assertNull($result[0]->defaultAreaPlan);
        $this->assertEquals([], $result[0]->areaPlans);
        $this->assertEquals([1, 2, 3], $result[0]->agreementLengthIds);
    }

    public function test_search_plans_returns_exception(): void
    {
        $dto = new SearchPlansDTO(
            planStatusId: 3,
            planCategoryId: 8,
            extReferenceId: '2827',
            officeId:39
        );

        $clientMock = $this->mockHttpGetRequestToThrowException(
            url: sprintf('%s/plans/filter', self::API_URL),
            query: $dto->toArray(),
        );
        $configMock = $this->getConfigMock();
        $loggerMock = $this->getLoggerMockLoggingRequestOnly();

        $this->expectException(Exception::class);

        $repository = new PlanBuilderRepository($clientMock, $configMock, $loggerMock);
        $repository->searchPlans($dto);
    }

    public function test_search_plans_with_products_returns_valid_data(): void
    {
        $dto = new SearchPlansDTO(
            planStatusId: 3,
            planCategoryId: 8,
            extReferenceId: '2827',
            officeId:39,
        );

        $clientMock = $this->mockHttpGetRequest(
            url: sprintf('%s/plans_with_products/filter', self::API_URL),
            query: $dto->toArray(),
            responseContent: '{"plans":[{"id":22,"ext_reference_id":"2827","name":"Pro","start_on":"2023-01-05","end_on":"2080-01-05","plan_service_frequency_id":2,"plan_status_id":3,"bill_monthly":false,"created_at":"2023-08-24T23:15:22.000000Z","updated_at":"2023-08-24T23:15:22.000000Z","order":0,"area_plan_pricings":[{"id":62817,"plan_pricing_level_id":3,"area_plan_id":350,"initial_min":39,"initial_max":399,"recurring_min":105,"recurring_max":199,"created_at":null,"updated_at":null}, {"id":62818,"plan_pricing_level_id":9,"area_plan_id":350,"initial_min":39,"initial_max":399,"recurring_min":105,"recurring_max":169,"created_at":null,"updated_at":null}, {"id":62819,"plan_pricing_level_id":8,"area_plan_id":350,"initial_min":39,"initial_max":399,"recurring_min":105,"recurring_max":149,"created_at":null,"updated_at":null}],"services":[19, 20],"addons":[{"id":229793,"area_plan_id":350,"product_id":1,"is_recurring":false,"initial_min":0,"initial_max":50,"recurring_min":12.3,"recurring_max":0,"created_at":null,"updated_at":null}, {"id":229794,"area_plan_id":350,"product_id":2,"is_recurring":true,"initial_min":149,"initial_max":399,"recurring_min":59,"recurring_max":99,"created_at":null,"updated_at":null}],"area_id":49,"area_plan_id":350,"plan_category_ids":[8],"agreement_length_ids":[1, 2, 3]}],"products":[{"id":1,"product_sub_category_id":2,"ext_reference_id":"1617","name":"Accessory Structure","order":2,"image":"https:\/\/s3.amazonaws.com\/aptive.staging-01.product-manager-api.bucket\/product_images\/1\/Accessory%20Structure.png?X-Amz-Content-Sha256=UNSIGNED-PAYLOAD&X-Amz-Security-Token=IQoJb3JpZ2luX2VjEA4aCXVzLWVhc3QtMSJHMEUCIQDaltJeNO3WVVkA5ur%2BZEud6LIgdbQo51v3exYmnJ8oJQIgPP%2BzmT74ePuRS3xZzkHNB%2BQ4Bx02FwONrLGaonmgf6kqlwUIFxADGgw4MjU3NDQ5MjM5ODQiDDiOiGkPdhkloufTmSr0BJx0IuyBxMzhPgd5EwDa%2FU0VPOcRkVvYvzstBiG4jR6QMIde6cf7xZzKq1U5bJsZiJLgU4EoAuU1mRBqO4YWdi2yi7D6zCLHKeJtPzM17cN%2BwE2CATC%2FXL1%2FqJnbIH3FeIl%2BqaBpWQaaFzntb3byM16KSUWzjdExrvEXEdyY%2ByApfeUveBg1oaURt9YZHsJCcp%2FUyAEk5zA3J0oumlKmXbiS0eJvwAlvOqy7nRsKQAv37buhtvD4YDjdp7KKxUs65h%2FqAiB6Fa116673K7ollogOaJvpfizPo6p2dgEt6%2FG28Cd%2Bc7flY057Tzp8EtA5%2BbrcYEEoDFfivGT7ehD%2BN7XBASMXesJIepGuR9TAkdiFVZb3tZFJwBqlhmLvhjFNxANhLQtyETbzOR3M5Gb45ab1AoF4piuSxvjQMoJa8%2By3jcODQDXftka8S6IO2xV%2FrUwDfr1S2dO30JM%2Fx4C3LOV9z%2Bx%2F1TxbU0Zf5pGlRZQMXyB%2FyCKpwK%2Fs4J%2FhqRiI9KnH6TWhRhbBTkIhXM5GS6XabpYn6yPZORVriwKfH8GqHUZq2qqIsxuHqRzJ617%2FoNoKg9ZJ7b8qmz3Ki3fqGPelFfKwta22jsiAyVgQB9IsOhTUzGZnKHrgA6Kf9mBKe5axjlrvjRmiHxqe2%2BxplHrHZatYGPpRHYJPZTkAxy9DOPrU7z2MjFdNuzpx0n9QGR12WW4QuQJqwUugeoSz2wPm3fb8d3x96cA57NpC9pkIgGDP%2FEvqW7ekh0lnIMfV2Ns%2Btmn38C2Qw5tHe2OpP4Ddf0PAJyAkOBl5IRYUwLg%2F1FquAbwgGj7nnQI4LR7GsNaOyXAwlZfrqAY6mwHWBy2vUD1Q2EwngO2k4dtt6wuuFbkolvtUlIQSn%2FJtGlTfhFyQ%2FcLxUtXiBm7L7J9m5YTd7p6LWheSmOs0WcTKAEP9lWVmXs4fMPFKTHii1CNyatSWc5oKF6j5%2BQEG%2F9I3wb6sGWuonfN09WzcnqrDbR4HvzzflzmlxzI3JpR93G%2B%2FIDWEQRmgJQtBJ5rzlwsE8MYLR%2F60hHxIbg%3D%3D&X-Amz-Algorithm=AWS4-HMAC-SHA256&X-Amz-Credential=ASIA4AQR3TVIDZQGAFXU%2F20231002%2Fus-east-1%2Fs3%2Faws4_request&X-Amz-Date=20231002T135429Z&X-Amz-SignedHeaders=host&X-Amz-Expires=604800&X-Amz-Signature=af684cadaa7c717137f06536f0747b709ca806b0ae265eb7a3d37957901012ba","is_recurring":false,"initial_min":0,"initial_max":50,"recurring_min":12.3,"recurring_max":0,"created_at":"2022-12-01T23:03:44.000000Z","updated_at":"2023-06-13T16:08:30.000000Z","needs_customer_support":false,"description":null,"image_name":"Accessory Structure.png"}, {"id":2,"product_sub_category_id":5,"ext_reference_id":"1960","name":"German Roach","order":1,"image":"https:\/\/s3.amazonaws.com\/aptive.staging-01.product-manager-api.bucket\/product_images\/2\/German%20Roach.png?X-Amz-Content-Sha256=UNSIGNED-PAYLOAD&X-Amz-Security-Token=IQoJb3JpZ2luX2VjEA4aCXVzLWVhc3QtMSJHMEUCIQDaltJeNO3WVVkA5ur%2BZEud6LIgdbQo51v3exYmnJ8oJQIgPP%2BzmT74ePuRS3xZzkHNB%2BQ4Bx02FwONrLGaonmgf6kqlwUIFxADGgw4MjU3NDQ5MjM5ODQiDDiOiGkPdhkloufTmSr0BJx0IuyBxMzhPgd5EwDa%2FU0VPOcRkVvYvzstBiG4jR6QMIde6cf7xZzKq1U5bJsZiJLgU4EoAuU1mRBqO4YWdi2yi7D6zCLHKeJtPzM17cN%2BwE2CATC%2FXL1%2FqJnbIH3FeIl%2BqaBpWQaaFzntb3byM16KSUWzjdExrvEXEdyY%2ByApfeUveBg1oaURt9YZHsJCcp%2FUyAEk5zA3J0oumlKmXbiS0eJvwAlvOqy7nRsKQAv37buhtvD4YDjdp7KKxUs65h%2FqAiB6Fa116673K7ollogOaJvpfizPo6p2dgEt6%2FG28Cd%2Bc7flY057Tzp8EtA5%2BbrcYEEoDFfivGT7ehD%2BN7XBASMXesJIepGuR9TAkdiFVZb3tZFJwBqlhmLvhjFNxANhLQtyETbzOR3M5Gb45ab1AoF4piuSxvjQMoJa8%2By3jcODQDXftka8S6IO2xV%2FrUwDfr1S2dO30JM%2Fx4C3LOV9z%2Bx%2F1TxbU0Zf5pGlRZQMXyB%2FyCKpwK%2Fs4J%2FhqRiI9KnH6TWhRhbBTkIhXM5GS6XabpYn6yPZORVriwKfH8GqHUZq2qqIsxuHqRzJ617%2FoNoKg9ZJ7b8qmz3Ki3fqGPelFfKwta22jsiAyVgQB9IsOhTUzGZnKHrgA6Kf9mBKe5axjlrvjRmiHxqe2%2BxplHrHZatYGPpRHYJPZTkAxy9DOPrU7z2MjFdNuzpx0n9QGR12WW4QuQJqwUugeoSz2wPm3fb8d3x96cA57NpC9pkIgGDP%2FEvqW7ekh0lnIMfV2Ns%2Btmn38C2Qw5tHe2OpP4Ddf0PAJyAkOBl5IRYUwLg%2F1FquAbwgGj7nnQI4LR7GsNaOyXAwlZfrqAY6mwHWBy2vUD1Q2EwngO2k4dtt6wuuFbkolvtUlIQSn%2FJtGlTfhFyQ%2FcLxUtXiBm7L7J9m5YTd7p6LWheSmOs0WcTKAEP9lWVmXs4fMPFKTHii1CNyatSWc5oKF6j5%2BQEG%2F9I3wb6sGWuonfN09WzcnqrDbR4HvzzflzmlxzI3JpR93G%2B%2FIDWEQRmgJQtBJ5rzlwsE8MYLR%2F60hHxIbg%3D%3D&X-Amz-Algorithm=AWS4-HMAC-SHA256&X-Amz-Credential=ASIA4AQR3TVIDZQGAFXU%2F20231002%2Fus-east-1%2Fs3%2Faws4_request&X-Amz-Date=20231002T135429Z&X-Amz-SignedHeaders=host&X-Amz-Expires=604800&X-Amz-Signature=ed75e917a2e204eec3826024f143c39e39f2a980c36f1273fb23e44f53bb7253","is_recurring":true,"initial_min":149,"initial_max":399,"recurring_min":58,"recurring_max":99,"created_at":"2022-12-02T18:05:50.000000Z","updated_at":"2023-06-13T16:08:37.000000Z","needs_customer_support":true,"description":null,"image_name":"German Roach.png"}]}',
        );
        $configMock = $this->getConfigMock();
        $loggerMock = $this->getLoggerMockLoggingRequestAndResponse();

        $repository = new PlanBuilderRepository($clientMock, $configMock, $loggerMock);
        $result = $repository->searchPlansWithProducts($dto);

        $this->assertInstanceOf(Plan::class, $result['plans'][0]);

        $this->assertEquals(22, $result['plans'][0]->id);
        $this->assertEquals(2827, $result['plans'][0]->extReferenceId);
        $this->assertEquals('Pro', $result['plans'][0]->name);
        $this->assertEquals('2023-01-05', $result['plans'][0]->startOn);
        $this->assertEquals('2080-01-05', $result['plans'][0]->endOn);
        $this->assertEquals(2, $result['plans'][0]->planServiceFrequencyId);
        $this->assertEquals(3, $result['plans'][0]->planStatusId);
        $this->assertEquals(false, $result['plans'][0]->billMonthly);
        $this->assertEquals(null, $result['plans'][0]->initialDiscount);
        $this->assertEquals(null, $result['plans'][0]->recurringDiscount);
        $this->assertEquals('2023-08-24T23:15:22.000000Z', $result['plans'][0]->createdAt);
        $this->assertEquals('2023-08-24T23:15:22.000000Z', $result['plans'][0]->updatedAt);
        $this->assertEquals(0, $result['plans'][0]->order);
        $this->assertEquals([8], $result['plans'][0]->planCategoryIds);
        $this->assertNull($result['plans'][0]->defaultAreaPlan);
        $this->assertEquals([], $result['plans'][0]->areaPlans);
        $this->assertEquals([1, 2, 3], $result['plans'][0]->agreementLengthIds);

        $this->assertInstanceOf(Product::class, $result['products'][0]);
        $this->assertInstanceOf(Product::class, $result['products'][1]);

        $this->assertEquals(2, $result['products'][1]->id);
        $this->assertEquals(5, $result['products'][1]->productSubCategoryId);
        $this->assertEquals(1960, $result['products'][1]->extReferenceId);
        $this->assertEquals('German Roach', $result['products'][1]->name);
        $this->assertEquals(1, $result['products'][1]->order);
        $this->assertEquals('https://s3.amazonaws.com/aptive.staging-01.product-manager-api.bucket/product_images/2/German%20Roach.png?X-Amz-Content-Sha256=UNSIGNED-PAYLOAD&X-Amz-Security-Token=IQoJb3JpZ2luX2VjEA4aCXVzLWVhc3QtMSJHMEUCIQDaltJeNO3WVVkA5ur%2BZEud6LIgdbQo51v3exYmnJ8oJQIgPP%2BzmT74ePuRS3xZzkHNB%2BQ4Bx02FwONrLGaonmgf6kqlwUIFxADGgw4MjU3NDQ5MjM5ODQiDDiOiGkPdhkloufTmSr0BJx0IuyBxMzhPgd5EwDa%2FU0VPOcRkVvYvzstBiG4jR6QMIde6cf7xZzKq1U5bJsZiJLgU4EoAuU1mRBqO4YWdi2yi7D6zCLHKeJtPzM17cN%2BwE2CATC%2FXL1%2FqJnbIH3FeIl%2BqaBpWQaaFzntb3byM16KSUWzjdExrvEXEdyY%2ByApfeUveBg1oaURt9YZHsJCcp%2FUyAEk5zA3J0oumlKmXbiS0eJvwAlvOqy7nRsKQAv37buhtvD4YDjdp7KKxUs65h%2FqAiB6Fa116673K7ollogOaJvpfizPo6p2dgEt6%2FG28Cd%2Bc7flY057Tzp8EtA5%2BbrcYEEoDFfivGT7ehD%2BN7XBASMXesJIepGuR9TAkdiFVZb3tZFJwBqlhmLvhjFNxANhLQtyETbzOR3M5Gb45ab1AoF4piuSxvjQMoJa8%2By3jcODQDXftka8S6IO2xV%2FrUwDfr1S2dO30JM%2Fx4C3LOV9z%2Bx%2F1TxbU0Zf5pGlRZQMXyB%2FyCKpwK%2Fs4J%2FhqRiI9KnH6TWhRhbBTkIhXM5GS6XabpYn6yPZORVriwKfH8GqHUZq2qqIsxuHqRzJ617%2FoNoKg9ZJ7b8qmz3Ki3fqGPelFfKwta22jsiAyVgQB9IsOhTUzGZnKHrgA6Kf9mBKe5axjlrvjRmiHxqe2%2BxplHrHZatYGPpRHYJPZTkAxy9DOPrU7z2MjFdNuzpx0n9QGR12WW4QuQJqwUugeoSz2wPm3fb8d3x96cA57NpC9pkIgGDP%2FEvqW7ekh0lnIMfV2Ns%2Btmn38C2Qw5tHe2OpP4Ddf0PAJyAkOBl5IRYUwLg%2F1FquAbwgGj7nnQI4LR7GsNaOyXAwlZfrqAY6mwHWBy2vUD1Q2EwngO2k4dtt6wuuFbkolvtUlIQSn%2FJtGlTfhFyQ%2FcLxUtXiBm7L7J9m5YTd7p6LWheSmOs0WcTKAEP9lWVmXs4fMPFKTHii1CNyatSWc5oKF6j5%2BQEG%2F9I3wb6sGWuonfN09WzcnqrDbR4HvzzflzmlxzI3JpR93G%2B%2FIDWEQRmgJQtBJ5rzlwsE8MYLR%2F60hHxIbg%3D%3D&X-Amz-Algorithm=AWS4-HMAC-SHA256&X-Amz-Credential=ASIA4AQR3TVIDZQGAFXU%2F20231002%2Fus-east-1%2Fs3%2Faws4_request&X-Amz-Date=20231002T135429Z&X-Amz-SignedHeaders=host&X-Amz-Expires=604800&X-Amz-Signature=ed75e917a2e204eec3826024f143c39e39f2a980c36f1273fb23e44f53bb7253', $result['products'][1]->image);
        $this->assertEquals(true, $result['products'][1]->isRecurring);
        $this->assertEquals(149.0, $result['products'][1]->initialMin);
        $this->assertEquals(399.0, $result['products'][1]->initialMax);

        $this->assertEquals(12.3, $result['products'][0]->recurringMin);
        $this->assertEquals(0.0, $result['products'][0]->recurringMax);
        $this->assertEquals('2022-12-01T23:03:44.000000Z', $result['products'][0]->createdAt);
        $this->assertEquals('2022-12-01T23:03:44.000000Z', $result['products'][0]->updatedAt);
        $this->assertEquals(false, $result['products'][0]->needsCustomerSupport);
        $this->assertEquals(null, $result['products'][0]->description);
        $this->assertEquals('Accessory Structure.png', $result['products'][0]->imageName);
    }

    public function test_search_plans_with_products_returns_exception(): void
    {
        $dto = new SearchPlansDTO(
            planStatusId: 3,
            planCategoryId: 8,
            extReferenceId: '2827',
            officeId:39
        );

        $clientMock = $this->mockHttpGetRequestToThrowException(
            url: sprintf('%s/plans_with_products/filter', self::API_URL),
            query: $dto->toArray(),
        );
        $configMock = $this->getConfigMock();
        $loggerMock = $this->getLoggerMockLoggingRequestOnly();

        $this->expectException(Exception::class);

        $repository = new PlanBuilderRepository($clientMock, $configMock, $loggerMock);
        $repository->searchPlansWithProducts($dto);
    }
}
