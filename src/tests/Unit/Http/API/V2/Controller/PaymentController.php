<?php

declare(strict_types=1);

namespace Tests\Unit\Http\API\V2\Controller;

use App\Enums\Models\Customer\AutoPay;
use App\Enums\Models\Payment\PaymentMethod;
use App\Exceptions\Account\AccountFrozenException;
use App\Exceptions\Entity\EntityNotFoundException;
use App\Interfaces\Repository\CustomerRepository;
use App\Models\External\CustomerModel;
use App\Models\External\PaymentModel;
use App\Services\PaymentService;
use Aptive\PestRoutesSDK\Resources\PaymentProfiles\PaymentProfilePaymentMethod;
use Aptive\PestRoutesSDK\Resources\Payments\PaymentMethod as PestRoutesPaymentMethod;
use Illuminate\Testing\TestResponse;
use Mockery;
use Mockery\MockInterface;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Tests\Data\CustomerData;
use Tests\Data\PaymentData;
use Tests\Data\PaymentProfileData;
use Tests\Traits\RandomIntTestData;
use Tests\Traits\TestAuthorizationMiddleware;
use Throwable;

class PaymentController extends ApiTestCase
{
    use RandomIntTestData;
    use TestAuthorizationMiddleware;

    protected const ERROR_ACCOUNT_FROZEN = 'Account frozen';
    protected const ERROR_ACCOUNT_NOT_FOUND = 'Entity not found';

    public int $accountNumber;
    public int $officeId;
    public int $paymentId;
    public int $paymentMethodId;
    public int $paymentProfileId;
    public CustomerModel $customer;

    public MockInterface|CustomerRepository $customerRepositoryMock;
    public MockInterface $paymentServiceMock;
    public MockInterface $paymentProfileServiceMock;

    public function setUp(): void
    {
        parent::setUp();

        $this->customerRepositoryMock = Mockery::mock(CustomerRepository::class);
        $this->paymentServiceMock = Mockery::mock(PaymentService::class);
        $this->instance(CustomerRepository::class, $this->customerRepositoryMock);
        $this->instance(PaymentService::class, $this->paymentServiceMock);

        $this->accountNumber = $this->getTestAccountNumber();
        $this->officeId = $this->getTestOfficeId();
        $this->paymentId = $this->getTestPaymentId();
        $this->paymentMethodId = $this->getTestPaymentMethodId();
        $this->paymentProfileId = $this->getTestPaymentProfileId();
        $this->customer = CustomerData::getTestEntityData(1, [
            'customerID' => $this->accountNumber,
            'aPay' => AutoPay::CREDIT_CARD->value,
        ])->first();

        $paymentProfiles = PaymentProfileData::getTestEntityData(
            2,
            ['customerID' => $this->customer->id],
            [
                'customerID' => $this->customer->id,
                'paymentProfileID' => $this->paymentProfileId,
                'paymentMethod' => PaymentProfilePaymentMethod::AutoPayACH->value,
            ]
        );

        $this->customer->setRelated('paymentProfiles', $paymentProfiles);
    }

    /**
     * @return iterable<array{Throwable, int}>
     */
    public function paymentServiceExceptionProvider(): iterable
    {
        yield [new RuntimeException('Test'), Response::HTTP_INTERNAL_SERVER_ERROR];
        yield [new EntityNotFoundException('Test'), Response::HTTP_NOT_FOUND];
        yield [new AccountFrozenException('Test'), Response::HTTP_NOT_FOUND];
    }


    protected function getPaymentsJsonResponse(): TestResponse
    {
        return $this->getJson(route(
            'api.v2.customer.payments.get',
            ['accountNumber' => $this->accountNumber]
        ));
    }

    protected function getPaymentJsonResponse(): TestResponse
    {
        return $this->getJson(route(
            'api.v2.customer.payments.getpayment',
            [
                'accountNumber' => $this->accountNumber,
                'paymentId' => $this->paymentId,
            ]
        ));
    }

    protected function getCreatePaymentJsonResponse($accountNumber, $postData = null): TestResponse
    {
        $postData = $postData ?? [
            'payment_profile_id' => $this->paymentProfileId,
            'amount_cents' => 12345,
            'payment_method' => PaymentMethod::CREDIT_CARD,
        ];

        return $this->postJson(
            route('api.v2.customer.payments.add', ['accountNumber' => $accountNumber]),
            $postData
        );
    }

    protected function givenCustomerRepositoryReturnsCustomer(): void
    {
        $this->customerRepositoryMock->shouldReceive('office')
            ->with($this->officeId)
            ->once()
            ->andReturnSelf();

        $this->customerRepositoryMock->shouldReceive('withRelated')
            ->with(['paymentProfiles'])
            ->andReturnSelf();

        $this->customerRepositoryMock->shouldReceive('find')
            ->with($this->accountNumber)
            ->andReturn($this->customer)
            ->once();
    }

    protected function givenCustomerRepositoryThrowsException($exception): void
    {
        $this->customerRepositoryMock->shouldReceive('office')->andReturnSelf();
        $this->customerRepositoryMock->shouldReceive('withRelated')->andReturnSelf();
        $this->customerRepositoryMock->shouldReceive('find')
            ->with($this->accountNumber)
            ->andThrow($exception)
            ->once();
    }

    protected function getValidPayment(): PaymentModel
    {
        return PaymentData::getTestEntityData(1, [
            'paymentID' => $this->paymentId,
            'officeID' => $this->getTestOfficeId(),
            'customerID' => $this->accountNumber,
            'paymentMethod' => PestRoutesPaymentMethod::CreditCard->value,
            'date' => '2022-07-01 06:30:55',
            'amount' => 123.45,
        ])->firstOrFail();
    }
}
