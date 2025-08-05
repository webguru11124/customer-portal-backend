<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\PaymentProfile;

use App\Actions\PaymentProfile\InitializeCreditCardPaymentProfileActionV2;
use App\DTO\Payment\AutoPayStatus;
use App\DTO\Payment\AutoPayStatusRequestDTO;
use App\DTO\Payment\CreatePaymentProfileRequestDTO;
use App\DTO\Payment\PaymentProfile;
use App\DTO\Payment\ValidateCreditCardTokenRequestDTO;
use App\Enums\Models\Payment\PaymentGateway;
use App\Enums\Models\PaymentProfile\PaymentMethod;
use App\Enums\Models\PaymentProfile\CardType;
use App\Events\PaymentMethod\CcAdded;
use App\Exceptions\Payment\CreditCardTokenNotFoundException;
use App\Http\Requests\V2\InitializeCreditCardPaymentProfileRequest;
use App\Interfaces\Repository\CustomerRepository;
use App\Models\Account;
use App\Models\External\CustomerModel;
use App\Repositories\AptivePayment\AptivePaymentRepository;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Facades\Event;
use Mockery;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\Data\CustomerData;
use Tests\TestCase;
use Tests\Traits\RandomIntTestData;
use Tests\Traits\RandomStringTestData;

final class InitializeCreditCardPaymentProfileActionV2Test extends TestCase
{
    use RandomIntTestData;
    use RandomStringTestData;

    public const PAYMENT_PROFILE_DATA_CC = [
        'billing_name' => 'Test Test',
        'billing_address_line_1' => 'Test line 1',
        'billing_address_line_2' => 'Test line 2',
        'billing_city' => 'City',
        'billing_state' => 'UT',
        'billing_zip' => 11111,
        'card_type' => 'CC',
        'cc_token' => 'A95B9CA8-A975-4035-BAD4-91FF46492A31',
        'cc_expiration_month' => 4,
        'cc_expiration_year' => 2030,
        'cc_last_four' => "0123",
        'cc_type' => 'MASTERCARD',
        'auto_pay' => true,
        'description' => 'Test description',
    ];

    protected AptivePaymentRepository|MockObject $paymentRepository;
    protected CustomerRepository|MockObject $customerRepository;
    protected Account $account;
    protected array $requestData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->paymentRepository = Mockery::mock(AptivePaymentRepository::class);
        $this->customerRepository = Mockery::mock(CustomerRepository::class);
        $this->account = Account::factory()->make();
        $this->requestData = self::PAYMENT_PROFILE_DATA_CC;
    }


    public function test_it_initializes_credit_card_profile_without_auto_pay(): void
    {
        $paymentProfile = $this->setupPaymentProfile();
        $this->setupCustomerRepositoryMock();
        $customer = $this->setupCustomerData();

        $this->requestData['auto_pay'] = false;

        $this->setupValidateCreditCardToken(
            PaymentGateway::PAYMENT_GATEWAY_WORLDPAY_TOKENEX_TRANSPARENT_ID,
            $customer
        );

        $this->setupCreatePaymentProfileToReturnPaymentProfileWithCustomGateway(
            PaymentGateway::PAYMENT_GATEWAY_WORLDPAY_TOKENEX_TRANSPARENT_ID,
            $customer,
            $paymentProfile
        );

        $this->paymentRepository
            ->shouldReceive('updateAutoPayStatus')
            ->withAnyArgs()
            ->never();

        $action = new InitializeCreditCardPaymentProfileActionV2(
            paymentRepository: $this->paymentRepository,
            customerRepository: $this->customerRepository,
            gateway: PaymentGateway::PAYMENT_GATEWAY_WORLDPAY_TOKENEX_TRANSPARENT_ID,
        );

        Event::fake();

        $this->assertSame(
            $paymentProfile,
            ($action)(new InitializeCreditCardPaymentProfileRequest($this->requestData),
            $this->account)
        );

        Event::assertDispatched(CcAdded::class);
    }

    public function test_it_initializes_credit_card_profile_with_auto_pay(): void
    {
        $paymentProfile = $this->setupPaymentProfile();
        $this->setupCustomerRepositoryMock();
        $customer = $this->setupCustomerData();

        $this->setupValidateCreditCardToken(PaymentGateway::PAYMENT_GATEWAY_WORLDPAY_ID, $customer);
        $this->setupCreatePaymentProfileToReturnPaymentProfileWithCustomGateway(
            PaymentGateway::PAYMENT_GATEWAY_WORLDPAY_ID,
            $customer,
            $paymentProfile
        );

        $this->paymentRepository
            ->shouldReceive('updateAutoPayStatus')
            ->withArgs(
                fn (AutoPayStatusRequestDTO $paymentProfileDTO) => $paymentProfileDTO->customerId === $customer->id &&
                    $paymentProfileDTO->autopayMethodId === $this->getTestPaymentMethodUuid()
            )
            ->andReturn(new AutoPayStatus(
                success: true,
                message: 'Test message',
            ));

        $action = new InitializeCreditCardPaymentProfileActionV2(
            paymentRepository: $this->paymentRepository,
            customerRepository: $this->customerRepository,
            gateway: PaymentGateway::PAYMENT_GATEWAY_WORLDPAY_ID,
        );

        Event::fake();

        $this->assertSame(
            $paymentProfile,
            ($action)(new InitializeCreditCardPaymentProfileRequest($this->requestData),
            $this->account)
        );

        Event::assertDispatched(CcAdded::class);
    }

    public function test_initializes_credit_card_profile_returns_create_payment_profile_exception(): void
    {
        $this->setupCustomerRepositoryMock();

        $this->setupValidateCreditCardToken(
            PaymentGateway::PAYMENT_GATEWAY_WORLDPAY_TOKENEX_TRANSPARENT_ID,
            $this->setupCustomerData()
        );

        $this->paymentRepository
            ->shouldReceive('createPaymentProfile')
            ->withAnyArgs()
            ->once()
            ->andThrows(Mockery::mock(ClientException::class));

        $this->paymentRepository
            ->shouldReceive('updateAutoPayStatus')
            ->withAnyArgs()
            ->never();

        $action = new InitializeCreditCardPaymentProfileActionV2($this->paymentRepository, $this->customerRepository);

        $this->expectException(ClientException::class);

        Event::fake();

        ($action)(new InitializeCreditCardPaymentProfileRequest($this->requestData), $this->account);

        Event::assertNotDispatched(CcAdded::class);
    }

    public function test_initializes_credit_card_profile_returns_autopay_exception(): void
    {
        $paymentProfile = $this->setupPaymentProfile();
        $this->setupCustomerRepositoryMock();
        $customer = $this->setupCustomerData();

        $this->setupValidateCreditCardToken(PaymentGateway::PAYMENT_GATEWAY_WORLDPAY_ID, $customer);
        $this->setupCreatePaymentProfileToReturnPaymentProfileWithCustomGateway(
            PaymentGateway::PAYMENT_GATEWAY_WORLDPAY_ID,
            $customer,
            $paymentProfile,
        );

        $this->paymentRepository
            ->shouldReceive('updateAutoPayStatus')
            ->withArgs(
                fn (AutoPayStatusRequestDTO $paymentProfileDTO) => $paymentProfileDTO->customerId === $customer->id &&
                    $paymentProfileDTO->autopayMethodId === $this->getTestPaymentMethodUuid()
            )
            ->once()
            ->andThrows(Mockery::mock(ClientException::class));

        $action = new InitializeCreditCardPaymentProfileActionV2(
            paymentRepository: $this->paymentRepository,
            customerRepository: $this->customerRepository,
            gateway: PaymentGateway::PAYMENT_GATEWAY_WORLDPAY_ID,
        );

        $this->expectException(ClientException::class);

        Event::fake();

        ($action)(new InitializeCreditCardPaymentProfileRequest($this->requestData), $this->account);

        Event::assertNotDispatched(CcAdded::class);
    }

    public function test_initializes_credit_card_profile_cc_token_validation_throw_an_exception(): void
    {
        $this->setupCustomerRepositoryMock();

        $this->paymentRepository
            ->shouldReceive('isValidCreditCardToken')
            ->withAnyArgs()
            ->once()
            ->andThrows(Mockery::mock(CreditCardTokenNotFoundException::class));

        $this->paymentRepository
            ->shouldReceive('createPaymentProfile')
            ->withAnyArgs()
            ->never();

        $this->paymentRepository
            ->shouldReceive('updateAutoPayStatus')
            ->withAnyArgs()
            ->never();

        $action = new InitializeCreditCardPaymentProfileActionV2(
            paymentRepository: $this->paymentRepository,
            customerRepository: $this->customerRepository,
            gateway: PaymentGateway::PAYMENT_GATEWAY_WORLDPAY_ID,
        );

        $this->expectException(CreditCardTokenNotFoundException::class);

        Event::fake();

        ($action)(new InitializeCreditCardPaymentProfileRequest($this->requestData), $this->account);

        Event::assertNotDispatched(CcAdded::class);
    }

    protected function setupCustomerRepositoryMock(): void
    {
        $this->customerRepository
            ->shouldReceive('office')
            ->with($this->account->office_id)
            ->andReturnSelf();

        $this->customerRepository
            ->shouldReceive('find')
            ->with($this->account->account_number)
            ->once()
            ->andReturn($this->setupCustomerData());
    }

    protected function setupCustomerData(): CustomerModel
    {
        return CustomerData::getTestEntityData(1, [
            'customerID' => $this->account->account_number,
            'officeID' => $this->account->office_id,
        ])->first();
    }

    protected function setupPaymentProfile(): PaymentProfile
    {
        return new PaymentProfile(
            paymentMethodId: $this->getTestPaymentMethodUuid(),
            message: 'Test message'
        );
    }

    protected function setupCreatePaymentProfileToReturnPaymentProfileWithCustomGateway(
        PaymentGateway $gateway,
        CustomerModel $customerModel,
        PaymentProfile $paymentProfile
    ): void {
        $customerName = explode(' ', $this->requestData['billing_name'], 2);

        $this->paymentRepository
            ->shouldReceive('createPaymentProfile')
            ->withArgs(
                fn (CreatePaymentProfileRequestDTO $paymentProfileDTO) =>
                    $paymentProfileDTO->customerId === $customerModel->id &&
                    $paymentProfileDTO->gatewayId == $gateway &&
                    $paymentProfileDTO->type == PaymentMethod::from(strtoupper($this->requestData['card_type'])) &&
                    $paymentProfileDTO->firstName === current($customerName) &&
                    $paymentProfileDTO->lastName === end($customerName) &&
                    $paymentProfileDTO->addressLine1 === $this->requestData['billing_address_line_1'] &&
                    $paymentProfileDTO->email === $customerModel->email &&
                    $paymentProfileDTO->city === $this->requestData['billing_city'] &&
                    $paymentProfileDTO->province === $this->requestData['billing_state'] &&
                    $paymentProfileDTO->postalCode === (string) $this->requestData['billing_zip'] &&
                    $paymentProfileDTO->countryCode === $customerModel->billingInformation->address->countryCode &&
                    $paymentProfileDTO->isPrimary === false &&
                    $paymentProfileDTO->isAutoPay === $this->requestData['auto_pay']  &&
                    $paymentProfileDTO->ccToken === $this->requestData['cc_token'] &&
                    $paymentProfileDTO->ccType === CardType::from($this->requestData['cc_type']) &&
                    $paymentProfileDTO->ccExpirationMonth === $this->requestData['cc_expiration_month'] &&
                    $paymentProfileDTO->ccExpirationYear === $this->requestData['cc_expiration_year'] &&
                    $paymentProfileDTO->ccLastFour === $this->requestData['cc_last_four'] &&
                    $paymentProfileDTO->description === $this->requestData['description'] &&
                    $paymentProfileDTO->shouldSkipGatewayValidation === true
            )
            ->once()
            ->andReturn($paymentProfile);
    }

    protected function setupValidateCreditCardToken(
        PaymentGateway $gateway,
        CustomerModel $customerModel
    ): void {
        $this->paymentRepository
            ->shouldReceive('isValidCreditCardToken')
            ->withArgs(
                fn (ValidateCreditCardTokenRequestDTO $requestDTO) =>
                    $requestDTO->gateway === $gateway &&
                    $requestDTO->officeId == $customerModel->officeId &&
                    $requestDTO->ccToken == $this->requestData['cc_token'] &&
                    $requestDTO->ccExpirationMonth === $this->requestData['cc_expiration_month'] &&
                    $requestDTO->ccExpirationYear === $this->requestData['cc_expiration_year']
            )
            ->once()
            ->andReturn(true);
    }
}
