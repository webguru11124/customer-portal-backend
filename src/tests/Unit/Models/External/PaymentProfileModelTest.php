<?php

namespace Tests\Unit\Models\External;

use App\Interfaces\Repository\PaymentProfileRepository;
use App\Models\External\PaymentProfileModel;
use Aptive\PestRoutesSDK\Resources\PaymentProfiles\PaymentProfilePaymentMethod;
use Aptive\PestRoutesSDK\Resources\PaymentProfiles\PaymentProfileStatus;
use Illuminate\Support\Carbon;
use Tests\Data\PaymentProfileData;
use Tests\TestCase;

class PaymentProfileModelTest extends TestCase
{
    public function test_it_returns_proper_repository_class(): void
    {
        self::assertSame(PaymentProfileRepository::class, PaymentProfileModel::getRepositoryClass());
    }

    /**
     * @dataProvider dataProvider
     */
    public function test_is_valid(array $data, bool $expectedResult): void
    {
        $paymentProfile = PaymentProfileData::getTestEntityData(1, $data)->first();

        self::assertEquals($expectedResult, $paymentProfile->isValid);
    }

    /**
     * @return iterable<int, array<int, mixed>>
     */
    public function dataProvider(): iterable
    {
        yield [[
            'status' => PaymentProfileStatus::Valid->value,
            'paymentMethod' => PaymentProfilePaymentMethod::AutoPayACH->value,
        ], true];
        yield [[
            'status' => PaymentProfileStatus::LastTransactionFailed->value,
            'paymentMethod' => PaymentProfilePaymentMethod::AutoPayACH->value,
        ], true];
        yield [[
            'status' => PaymentProfileStatus::Empty->value,
            'paymentMethod' => PaymentProfilePaymentMethod::AutoPayACH->value,
        ], false];
        yield [[
            'status' => PaymentProfileStatus::Expired->value,
            'paymentMethod' => PaymentProfilePaymentMethod::AutoPayACH->value,
        ], false];
        yield [[
            'status' => PaymentProfileStatus::Invalid->value,
            'paymentMethod' => PaymentProfilePaymentMethod::AutoPayACH->value,
        ], false];
        yield [[
            'status' => PaymentProfileStatus::SoftDeleted->value,
            'paymentMethod' => PaymentProfilePaymentMethod::AutoPayACH->value,
        ], false];
        yield [[
            'status' => PaymentProfileStatus::Valid->value,
            'paymentMethod' => PaymentProfilePaymentMethod::AutoPayCC->value,
            'cardExpirationYear' => '',
        ], true];
    }

    /**
     * @dataProvider cardExpirationDateDataProvider
     */
    public function test_is_expired_flag_is_correct(
        bool|null $expectedFlagValue,
        string|null $expMonth,
        string|null $expYear
    ): void {
        Carbon::setTestNow('2020-06-01');
        $paymentProfile = PaymentProfileData::getTestEntityData(
            1,
            ['expMonth' => $expMonth, 'expYear' => $expYear]
        )->first();

        self::assertEquals($expectedFlagValue, $paymentProfile->isExpired);
    }

    /**
     * @return iterable<string, array{bool, string, string}>
     */
    public function cardExpirationDateDataProvider(): iterable
    {
        yield '05/19' => [true, '05', '19'];
        yield '06/19' => [true, '06', '19'];
        yield '07/19' => [true, '07', '2019'];
        yield '05/20' => [true, '05', '20'];
        yield '06/20' => [false, '06', '20'];
        yield '07/20' => [false, '07', '2020'];
        yield '05/21' => [false, '05', '21'];
        yield '06/21' => [false, '06', '21'];
        yield '07/21' => [false, '07', '2021'];
        yield '00/01' => [null, null, '2021'];
        yield '00/02' => [null, '07', null];
        yield '00/03' => [null, null, null];
        yield '00/04' => [null, '', '23'];
        yield '00/05' => [null, '12', ''];
    }

    public function test_it_returns_magic_properties_in_array(): void
    {
        $paymentProfile = PaymentProfileData::getTestEntityData(
            1,
            [
                'expMonth' => '12',
                'expYear' => '2022',
                'status' => PaymentProfileStatus::SoftDeleted->value,
                'paymentMethod' => PaymentProfilePaymentMethod::AutoPayACH->value,
            ]
        )->first();
        $array = $paymentProfile->toArray();
        self::assertEquals(true, $array['isExpired']);
        self::assertEquals(false, $array['isValid']);
    }
}
