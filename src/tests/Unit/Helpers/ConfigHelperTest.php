<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Helpers\ConfigHelper;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;
use Tests\Traits\RandomIntTestData;

final class ConfigHelperTest extends TestCase
{
    use RandomIntTestData;

    /**
     * @dataProvider configValuesDataProvider
     */
    public function test_it_takes_value_from_config(
        string $configParameterName,
        string $methodName,
        int|string|bool|array|null $testValue
    ): void {
        $this->setUpConfig([$configParameterName], $testValue);

        $result = ConfigHelper::$methodName();

        self::assertSame($testValue, $result);
    }

    public function configValuesDataProvider(): iterable
    {
        yield 'Global office id' => [
            'pestroutes.auth.global_office_id',
            'getGlobalOfficeId',
            $this->getTestOfficeId(),
        ];

        yield 'accounts sync countdown' => [
            'cache.custom_ttl.accounts_sync_countdown',
            'getAccountSyncCountdown',
            10000,
        ];

        yield 'office cache ttl' => [
            'cache.custom_ttl.repositories.office',
            'getOfficeRepositoryCacheTtl',
            100,
        ];

        yield 'basic reservice interval' => [
            'aptive.basic_reservice_interval',
            'getBasicReserviceInterval',
            39,
        ];

        yield 'summer interval service types' => [
            'aptive.summer_interval_service_types',
            'getSummerIntervalServiceTypes',
            [
                'Pro' => 24,
                'Pro Plus' => 24,
                'Basic' => 39,
                'Premium' => 14,
            ],
        ];

        yield 'plan builder customer portal category name' => [
            'planbuilder.customer_portal.category_name',
            'getPlanBuilderCategoryName',
            'Customer Portal',
        ];

        yield 'plan builder active status name' => [
            'planbuilder.customer_portal.active_status_name',
            'getPlanBuilderActiveStatusName',
            'Active',
        ];

        yield 'payment token scheme' => [
            'payment.api_token_scheme',
            'getPaymentServiceTokenScheme',
            'PCI',
        ];

        yield 'plan builder low pricing level name' => [
            'planbuilder.customer_portal.low_pricing_level_name',
            'getPlanBuilderLowPricingLevelName',
            'Low',
        ];

        yield 'plan builder cache ttl' => [
            'cache.custom_ttl.repositories.plan_builder',
            'getPlanBuilderRepositoryCacheTtl',
            3600,
        ];

        yield 'plan builder cp plans' => [
            'planbuilder.customer_portal.plans',
            'getCPPlans',
            [
                'Premium' => '',
                'Pro +' => '',
                'Pro' => '',
                'Basic' => '',
            ],
        ];

        yield 'cleo crm cache ttl' => [
            'cache.custom_ttl.repositories.cleo_crm',
            'getCleoCrmRepositoryCacheTtl',
            3600,
        ];
    }

    private function setUpConfig(array $key, mixed $value)
    {
        Config::expects('get')->once()
            ->withArgs($key)
            ->andReturn($value);
    }
}
