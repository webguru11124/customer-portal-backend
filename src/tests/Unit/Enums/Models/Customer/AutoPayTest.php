<?php

namespace Tests\Unit\Enums\Models\Customer;

use App\Enums\Models\Customer\AutoPay;
use Tests\TestCase;

class AutoPayTest extends TestCase
{
    public function test_enum_is_not_AutoPay()
    {
        $enum = AutoPay::NO;
        $this->assertFalse($enum->isAutoPay());
    }

    /**
     * @dataProvider provideAutoPay
     */
    public function test_enum_is_AutoPay(AutoPay $enum)
    {
        $this->assertTrue($enum->isAutoPay());
    }

    public function provideAutoPay(): array
    {
        return [
            [AutoPay::ACH],
            [AutoPay::CREDIT_CARD],
        ];
    }
}
