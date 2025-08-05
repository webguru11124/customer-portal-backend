<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Helpers\SubscriptionAddonsConfigHelper;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;
use Tests\Traits\RandomIntTestData;

final class SubscriptionAddonsConfigHelperTest extends TestCase
{
    use RandomIntTestData;

    /**
     * @dataProvider configValuesDataProvider
     */
    public function test_it_takes_value_from_config(
        string $configParameterName,
        string $methodName,
        int|float|string|bool|array|null $testValue
    ): void {
        $this->setUpConfig([$configParameterName], $testValue);

        $result = SubscriptionAddonsConfigHelper::$methodName();

        self::assertSame($testValue, $result);
    }

    public function configValuesDataProvider(): iterable
    {
        yield 'get_default_amount' => [
            'aptive.subscription.addons_default_values.amount',
            'getAddonDefaultAmount',
            199.0,
        ];

        yield 'get_default_taxable' => [
            'aptive.subscription.addons_default_values.taxable',
            'getAddonDefaultTaxable',
            false,
        ];

        yield 'get_default_name' => [
            'aptive.subscription.addons_default_values.name',
            'getAddonDefaultName',
            'Addon',
        ];

        yield 'get_default_quantity' => [
            'aptive.subscription.addons_default_values.quantity',
            'getAddonDefaultQuantity',
            1,
        ];

        yield 'get_default_service_id' => [
            'aptive.subscription.addons_default_values.service_id',
            'getAddonDefaultServiceId',
            0,
        ];

        yield 'get_default_credit_to' => [
            'aptive.subscription.addons_default_values.credit_to',
            'getAddonDefaultCreditTo',
            0,
        ];

        yield 'get_disallowed_addon_pests' => [
            'aptive.subscription.addons_exceptions.disallowed_pests',
            'getDisallowedAddonsPests',
            ['German Cockroach',],
        ];
    }

    private function setUpConfig(array $key, mixed $value): void
    {
        Config::expects('get')->once()
            ->withArgs($key)
            ->andReturn($value);
    }
}
