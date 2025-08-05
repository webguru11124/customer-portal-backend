<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\DTO\Payment\PaymentMethod;
use App\Enums\Models\Payment\PaymentMethod as PaymentMethodEnum;
use App\Helpers\PaymentMethodValidator;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;
use Tests\Traits\RandomIntTestData;
use Tests\Traits\RandomStringTestData;

class PaymentMethodValidatorTest extends TestCase
{
    use RandomIntTestData;
    use RandomStringTestData;

    private const DATE_FORMAT = 'Y-m-d';
    private const DATE_ADDED = '2023-06-28 23:45:00';

    protected PaymentMethodValidator $paymentMethodValidator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->paymentMethodValidator = new PaymentMethodValidator();
    }

    /**
     * @dataProvider providePaymentMethodsForValidation
     */
    public function test_it_validate_payment_method(
        PaymentMethod $paymentMethod,
        bool $isExpired
    ): void {
        Config::set('aptive.default_date_format', self::DATE_FORMAT);

        $this->assertEquals($isExpired, $this->paymentMethodValidator->isPaymentMethodExpired($paymentMethod));
    }

    protected function providePaymentMethodsForValidation(): iterable
    {
        yield 'valid_ach_with_empty_exp_month_year' => [
            PaymentMethod::fromApiResponse((object) [
                'payment_method_id' => $this->getTestPaymentMethodUuid(),
                'account_id' => $this->getTestCrmAccountUuid(),
                'type' => PaymentMethodEnum::ACH->value,
                'date_added' => self::DATE_ADDED,
                'is_primary' => false,
                'description' => null,
                'ach_account_last_four' => '1111',
                'ach_routing_number' => '985612814',
                'ach_account_type' => 'personal_checking',
                'ach_bank_name' => 'Universal Bank',
            ]),
            false,
        ];

        yield 'valid_ach_with_correct_exp_month_year' => [
            PaymentMethod::fromApiResponse((object) [
                'payment_method_id' => $this->getTestPaymentMethodUuid(),
                'account_id' => $this->getTestCrmAccountUuid(),
                'type' => PaymentMethodEnum::ACH->value,
                'date_added' => self::DATE_ADDED,
                'is_primary' => false,
                'ach_account_last_four' => '1111',
                'ach_routing_number' => '985612814',
                'ach_account_type' => 'personal_checking',
                'ach_bank_name' => 'Universal Bank',
                'description' => null,
            ]),
            false,
        ];

        yield 'valid_ach_with_incorrect_exp_month_year' => [
            PaymentMethod::fromApiResponse((object) [
                'payment_method_id' => $this->getTestPaymentMethodUuid(),
                'account_id' => $this->getTestCrmAccountUuid(),
                'type' => PaymentMethodEnum::ACH->value,
                'date_added' => self::DATE_ADDED,
                'is_primary' => false,
                'ach_account_last_four' => '1111',
                'ach_routing_number' => '985612814',
                'ach_account_type' => 'personal_checking',
                'ach_bank_name' => 'Universal Bank',
                'description' => null,
            ]),
            false,
        ];

        yield 'valid_cc_with_correct_exp_month_year' => [
            PaymentMethod::fromApiResponse((object) [
                'payment_method_id' => $this->getTestPaymentMethodUuid(),
                'account_id' => $this->getTestCrmAccountUuid(),
                'type' => PaymentMethodEnum::CREDIT_CARD->value,
                'date_added' => self::DATE_ADDED,
                'is_primary' => false,
                'cc_last_four' => "1234",
                'cc_expiration_month' => 11,
                'cc_expiration_year' => 2050,
                'description' => null,
            ]),
            false,
        ];

        yield 'valid_cc_with_empty_exp_month' => [
            PaymentMethod::fromApiResponse((object) [
                'payment_method_id' => $this->getTestPaymentMethodUuid(),
                'account_id' => $this->getTestCrmAccountUuid(),
                'type' => PaymentMethodEnum::CREDIT_CARD->value,
                'date_added' => self::DATE_ADDED,
                'is_primary' => false,
                'cc_last_four' => "1234",
                'cc_expiration_month' => null,
                'cc_expiration_year' => 2050,
                'description' => null,
            ]),
            true,
        ];

        yield 'invalid_cc_with_empty_exp_year' => [
            PaymentMethod::fromApiResponse((object) [
                'payment_method_id' => $this->getTestPaymentMethodUuid(),
                'account_id' => $this->getTestCrmAccountUuid(),
                'type' => PaymentMethodEnum::CREDIT_CARD->value,
                'date_added' => self::DATE_ADDED,
                'is_primary' => false,
                'cc_last_four' => "1234",
                'cc_expiration_month' => 7,
                'cc_expiration_year' => null,
                'description' => null,
            ]),
            true,
        ];

        yield 'invalid_cc_with_incorrect_exp_month_year' => [
            PaymentMethod::fromApiResponse((object) [
                'payment_method_id' => $this->getTestPaymentMethodUuid(),
                'account_id' => $this->getTestCrmAccountUuid(),
                'type' => PaymentMethodEnum::CREDIT_CARD->value,
                'date_added' => self::DATE_ADDED,
                'is_primary' => false,
                'cc_last_four' => "1111",
                'cc_expiration_month' => 11,
                'cc_expiration_year' => 2000,
                'description' => null,
            ]),
            true,
        ];
    }
}
