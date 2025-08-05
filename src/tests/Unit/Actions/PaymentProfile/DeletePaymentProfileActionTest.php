<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\PaymentProfile;

use App\Actions\PaymentProfile\DeletePaymentProfileAction;
use App\Exceptions\Account\AccountFrozenException;
use App\Exceptions\Authorization\UnauthorizedException;
use App\Exceptions\Entity\EntityNotFoundException;
use App\Exceptions\PaymentProfile\PaymentProfileNotDeletedException;
use App\Exceptions\PaymentProfile\PaymentProfileNotFoundException;
use App\Interfaces\Repository\CustomerRepository;
use App\Interfaces\Repository\PaymentProfileRepository;
use App\Models\Account;
use App\Models\External\CustomerModel;
use App\Models\External\PaymentProfileModel;
use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use Aptive\PestRoutesSDK\Resources\Customers\CustomerAutoPay;
use Aptive\PestRoutesSDK\Resources\PaymentProfiles\PaymentProfile;
use Mockery;
use Mockery\MockInterface;
use Tests\Data\CustomerData;
use Tests\Data\PaymentProfileData;
use Tests\TestCase;
use Tests\Traits\RandomIntTestData;
use Throwable;

final class DeletePaymentProfileActionTest extends TestCase
{
    use RandomIntTestData;

    protected Account $account;
    protected MockInterface|PaymentProfileRepository $paymentProfileRepositoryMock;
    protected MockInterface|CustomerRepository $customerRepositoryMock;
    protected DeletePaymentProfileAction $subject;

    public function setUp(): void
    {
        parent::setUp();

        $this->account = Account::factory()->makeOne([
            'account_number' => $this->getTestAccountNumber(),
            'office_id' => $this->getTestOfficeId(),
        ]);

        $this->paymentProfileRepositoryMock = Mockery::mock(PaymentProfileRepository::class);
        $this->customerRepositoryMock = Mockery::mock(CustomerRepository::class);

        $this->subject = new DeletePaymentProfileAction(
            $this->customerRepositoryMock,
            $this->paymentProfileRepositoryMock
        );
    }

    private function setUpPaymentProfileRepository(PaymentProfileModel|Throwable $result): void
    {
        $officeExpectation = $this->paymentProfileRepositoryMock
            ->shouldReceive('office')
            ->once();

        $expectation = $this->paymentProfileRepositoryMock
            ->shouldReceive('find')
            ->once();

        if ($result instanceof PaymentProfile) {
            $officeExpectation->with($result->officeId);
            $expectation->with($result->id)
                ->andReturn($result);
        } else {
            $expectation->andThrow($result);
        }

        $officeExpectation->andReturnSelf();
    }

    private function setUpCustomerRepository(CustomerModel|Throwable $result): void
    {
        $this->customerRepositoryMock
            ->shouldReceive('office')
            ->once()
            ->andReturnSelf();

        $expectation = $this->customerRepositoryMock
            ->shouldReceive('find')
            ->once();

        if ($result instanceof PaymentProfile) {
            $expectation->with($result->id)->andReturn($result);
        } else {
            $expectation->andThrow($result);
        }
    }

    public function test_it_deletes_payment_profile(): void
    {
        /** @var CustomerModel $customer */
        $customer = CustomerData::getTestEntityData(1, [
            'customerID' => $this->account->account_number,
            'officeID' => $this->account->office_id,
            'aPay' => CustomerAutoPay::AutoPayCC->value,
            'autoPayPaymentProfileID' => $this->getTestPaymentProfileId(),
        ])->first();

        /** @var PaymentProfileModel $paymentProfile */
        $paymentProfile = PaymentProfileData::getTestEntityData(1, [
            'paymentProfileID' => $this->getTestPaymentProfileId() + 1,
            'customerID' => $this->account->account_number,
            'officeID' => $this->account->office_id,
        ])->first();

        $this->setUpPaymentProfileRepository($paymentProfile);
        $this->setUpCustomerRepository($customer);

        $this->paymentProfileRepositoryMock
            ->shouldReceive('deletePaymentProfile')
            ->with($this->account->office_id, $paymentProfile->id)
            ->once()
            ->andReturnNull();

        ($this->subject)($this->account, $paymentProfile->id);
    }

    public function test_it_throws_unauthorized_exception_if_payment_profile_doesnt_belong_to_customer(): void
    {
        /** @var PaymentProfileModel $paymentProfile */
        $paymentProfile = PaymentProfileData::getTestEntityData(1, [
            'customerID' => $this->account->account_number + 1,
            'officeID' => $this->account->office_id,
        ])->first();

        $this->setUpPaymentProfileRepository($paymentProfile);

        $this->expectException(UnauthorizedException::class);

        ($this->subject)($this->account, $paymentProfile->id);
    }

    public function test_it_throws_payment_profile_not_deleted_exception_if_payment_profile_is_autopay(): void
    {
        /** @var CustomerModel $customer */
        $customer = CustomerData::getTestEntityData(1, [
            'customerID' => $this->account->account_number,
            'officeID' => $this->account->office_id,
            'autoPayPaymentProfileID' => $this->getTestPaymentProfileId(),
        ])->first();

        /** @var PaymentProfileModel $paymentProfile */
        $paymentProfile = PaymentProfileData::getTestEntityData(1, [
            'paymentProfileID' => $customer->autoPayPaymentProfileId,
            'customerID' => $this->account->account_number,
            'officeID' => $this->account->office_id,
        ])->first();

        $this->setUpPaymentProfileRepository($paymentProfile);
        $this->setUpCustomerRepository($customer);

        $this->paymentProfileRepositoryMock
            ->shouldReceive('deletePaymentProfile')
            ->never();

        $this->expectException(PaymentProfileNotDeletedException::class);
        $this->expectExceptionCode(PaymentProfileNotDeletedException::STATUS_LOCKED);

        ($this->subject)($this->account, $paymentProfile->id);
    }

    /**
     * @dataProvider paymentProfileRepositoryExceptionsDataProvider
     */
    public function test_it_passes_payment_profile_repository_exceptions(Throwable $exception): void
    {
        $this->setUpPaymentProfileRepository($exception);

        $this->paymentProfileRepositoryMock
            ->shouldReceive('deletePaymentProfile')
            ->never();

        $this->expectException($exception::class);

        ($this->subject)($this->account, $this->getTestPaymentProfileId());
    }

    /**
     * @return iterable<int, array<int, Throwable>>
     */
    public function paymentProfileRepositoryExceptionsDataProvider(): iterable
    {
        yield [new PaymentProfileNotFoundException()];
        yield [new InternalServerErrorHttpException()];
    }

    /**
     * @dataProvider customerRepositoryExceptionsDataProvider
     */
    public function test_it_passes_customer_repository_exceptions(Throwable $exception): void
    {
        /** @var PaymentProfileModel $paymentProfile */
        $paymentProfile = PaymentProfileData::getTestEntityData(1, [
            'customerID' => $this->account->account_number,
            'officeID' => $this->account->office_id,
        ])->first();

        $this->setUpPaymentProfileRepository($paymentProfile);
        $this->setUpCustomerRepository($exception);

        $this->paymentProfileRepositoryMock
            ->shouldReceive('deletePaymentProfile')
            ->never();

        $this->expectException($exception::class);

        ($this->subject)($this->account, $paymentProfile->id);
    }

    /**
     * @return iterable<int, array<int, Throwable>>
     */
    public function customerRepositoryExceptionsDataProvider(): iterable
    {
        yield [new EntityNotFoundException()];
        yield [new AccountFrozenException()];
        yield [new InternalServerErrorHttpException()];
    }
}
