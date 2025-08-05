<?php

namespace Tests\Unit\Services;

use App\DTO\PaymentProfile\SearchPaymentProfilesDTO;
use App\DTO\UpdatePaymentProfileDTO;
use App\Exceptions\PaymentProfile\PaymentProfileNotUpdatedException;
use App\Interfaces\Repository\PaymentProfileRepository;
use App\Models\Account;
use App\Models\External\PaymentProfileModel;
use App\Services\PaymentProfileService;
use Illuminate\Support\Collection;
use Mockery;
use Mockery\MockInterface;
use Tests\Data\PaymentProfileData;
use Tests\TestCase;
use Tests\Traits\GetPestRoutesPaymentProfile;
use Tests\Traits\PestroutesSdkExceptionProvider;
use Tests\Traits\RandomIntTestData;

class PaymentProfileServiceTest extends TestCase
{
    use GetPestRoutesPaymentProfile;
    use PestroutesSdkExceptionProvider;
    use RandomIntTestData;

    protected PaymentProfileService $subject;
    protected MockInterface|PaymentProfileRepository $paymentProfileRepositoryMock;

    public function setUp(): void
    {
        parent::setUp();

        $this->paymentProfileRepositoryMock = Mockery::mock(PaymentProfileRepository::class);

        $this->subject = new PaymentProfileService(
            $this->paymentProfileRepositoryMock
        );
    }

    public function test_get_payment_profile_by_merchant_id_searches_payment_profile(): void
    {
        $merchantId = 'testMerchantId';

        $paymentProfiles = PaymentProfileData::getTestEntityData(
            2,
            ['merchantID' => $merchantId],
            ['merchantID' => 'OtherMerchantId']
        );

        /** @var Account $account */
        $account = Account::factory()->make([
            'office_id' => $this->getTestOfficeId(),
            'account_number' => $this->getTestAccountNumber(),
        ]);

        $this->paymentProfileRepositoryMock
            ->shouldReceive('office')
            ->with($account->office_id)
            ->once()
            ->andReturnSelf();

        $this->paymentProfileRepositoryMock
            ->shouldReceive('search')
            ->withArgs(fn (SearchPaymentProfilesDTO $dto) => $dto->officeId === $account->office_id
                && $dto->accountNumbers === [$account->account_number])
            ->once()
            ->andReturn($paymentProfiles);

        $result = $this->subject->getPaymentProfileByMerchantId($account, $merchantId);

        self::assertInstanceOf(PaymentProfileModel::class, $result);
        self::assertSame($paymentProfiles->first(), $result);
    }

    public function test_get_payment_profile_by_merchant_id_returns_null_on_empty_result(): void
    {
        /** @var Account $account */
        $account = Account::factory()->make();

        $this->paymentProfileRepositoryMock
            ->shouldReceive('office')
            ->andReturnSelf();

        $this->paymentProfileRepositoryMock
            ->shouldReceive('search')
            ->andReturn(new Collection());

        $result = $this->subject->getPaymentProfileByMerchantId($account, 'testMerchantId');

        self::assertNull($result);
    }

    public function test_update_payment_profile_updates_payment_profile(): void
    {
        $this->paymentProfileRepositoryMock
            ->expects('updatePaymentProfile')
            ->with(UpdatePaymentProfileDTO::class)
            ->once();

        $this->subject->updatePaymentProfile($this->getUpdatePaymentProfileDTO());
    }

    public function test_update_payment_profile_throws_exception_on_update_exception(): void
    {
        $this->paymentProfileRepositoryMock
            ->expects('updatePaymentProfile')
            ->andThrows(PaymentProfileNotUpdatedException::class)
            ->once();

        $this->expectException(PaymentProfileNotUpdatedException::class);

        $this->subject->updatePaymentProfile($this->getUpdatePaymentProfileDTO());
    }

    protected function getUpdatePaymentProfileDTO(): UpdatePaymentProfileDTO
    {
        return new UpdatePaymentProfileDTO(
            officeId: $this->getTestOfficeId(),
            paymentProfileID: $this->getTestPaymentProfileId(),
            billingFName: 'John',
            billingLName: 'Doe',
            billingCity: 'Testing',
            expMonth: 1,
            expYear: 25,
        );
    }
}
