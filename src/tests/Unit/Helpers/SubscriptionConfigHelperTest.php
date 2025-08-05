<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Helpers\SubscriptionConfigHelper;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;
use Tests\Traits\RandomIntTestData;

final class SubscriptionConfigHelperTest extends TestCase
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

        $result = SubscriptionConfigHelper::$methodName();

        self::assertSame($testValue, $result);
    }

    public function configValuesDataProvider(): iterable
    {
        yield 'get_frozen_subscription_folloup_delay' => [
            'aptive.subscription.frozen.followupDelay',
            'getFrozenSubscriptionFollowupDelay',
            -1,
        ];

        yield 'is_frozen_subscription_active' => [
            'aptive.subscription.frozen.isActive',
            'isFrozenSubscriptionActive',
            false,
        ];

        yield 'get_frozen_subscription_flag' => [
            'aptive.subscription.frozen.flag',
            'getFrozenSubscriptionFlag',
            12345,
        ];
    }

    private function setUpConfig(array $key, mixed $value): void
    {
        Config::expects('get')->once()
            ->withArgs($key)
            ->andReturn($value);
    }
}
