<?php

declare(strict_types=1);

namespace Tests\Unit\Http\API\V2\Controller;

use App\DTO\AddPaymentDTO;
use App\Exceptions\Account\AccountFrozenException;
use App\Exceptions\Entity\EntityNotFoundException;
use App\Exceptions\Payment\PaymentNotCreatedException;
use App\Exceptions\PaymentProfile\CreditCardAuthorizationException;
use Aptive\PestRoutesSDK\Resources\PaymentProfiles\PaymentProfilePaymentMethod;
use Aptive\PestRoutesSDK\Resources\PaymentProfiles\PaymentProfileStatus;
use Illuminate\Support\Collection;
use Illuminate\Testing\Fluent\AssertableJson;
use Symfony\Component\HttpFoundation\Response;
use Tests\Data\PaymentProfileData;
use Tests\Traits\TestAuthorizationMiddleware;

class PaymentControllerCreate extends PaymentController
{
    use TestAuthorizationMiddleware;

    public function test_create_payment_forbids_unauthorized_access(): void
    {
        $this->checkAuthorizationMiddleware(
            fn () => $this->getCreatePaymentJsonResponse($this->accountNumber)
        );
    }

    public function test_create_payment_shows_error_for_not_authorised_user(): void
    {
        $this->createAndLogInAuth0User();

        $this->getCreatePaymentJsonResponse($this->accountNumber)
            ->assertNotFound();
    }

    public function test_create_payment_creates_and_searches_for_payment(): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $this->givenCustomerRepositoryReturnsCustomer();
        $payment = $this->getValidPayment();
        $this->paymentServiceMock->shouldReceive('addPayment')
            ->with($this->customer, AddPaymentDTO::class)
            ->andReturn($payment)
            ->once();

        $this->getCreatePaymentJsonResponse($this->accountNumber)
            ->assertOk()
            ->assertExactJson($payment->toArray());
    }

    public function test_create_payment_throws_payment_not_created_exception_and_returns_error(): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $this->givenCustomerRepositoryReturnsCustomer();

        $this->customer->setRelated('paymentProfiles', new Collection());

        $this->getCreatePaymentJsonResponse($this->accountNumber)
            ->assertStatus(Response::HTTP_INTERNAL_SERVER_ERROR)
            ->assertJson(fn (AssertableJson $json) => $json->has('message')->etc());
    }

    public function test_create_payment_throws_payment_not_created_exception_and_returns_error_on_expired_card(): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $this->givenCustomerRepositoryReturnsCustomer();

        $paymentProfiles = PaymentProfileData::getTestEntityData(
            2,
            [
                'customerID' => $this->customer->id,
                'paymentProfileID' => $this->paymentProfileId,
                'paymentMethod' => PaymentProfilePaymentMethod::AutoPayCC->value,
                'status' => PaymentProfileStatus::Valid->value,
                'expMonth' => '01',
                'expYear' => date('y', strtotime('+1 year')),
            ]
        );

        $this->customer->setRelated('paymentProfiles', $paymentProfiles);

        $this->getCreatePaymentJsonResponse($this->accountNumber)
            ->assertStatus(Response::HTTP_INTERNAL_SERVER_ERROR)
            ->assertJson(fn (AssertableJson $json) => $json->has('message')->etc());
    }

    public function test_create_payment_returns_422_on_invalid_request(): void
    {
        $this->createAndLogInAuth0UserWithAccount();
        $error = [
            'message' => 'The payment profile id field is required. (and 2 more errors)',
            'errors' => [
                'payment_profile_id' => [
                    'The payment profile id field is required.',
                ],
                'amount_cents' => [
                    'The amount cents field is required.',
                ],
                'payment_method' => [
                    'The payment method field is required.',
                ],
            ],
        ];

        $this->getCreatePaymentJsonResponse($this->accountNumber, [])
            ->assertUnprocessable()
            ->assertExactJson($error);
    }

    public function test_create_payment_returns404_for_existing_frozen_customer(): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $this->givenCustomerRepositoryThrowsException(new AccountFrozenException());

        $this->getCreatePaymentJsonResponse($this->accountNumber)
            ->assertNotFound()
            ->assertJsonPath('message', self::ERROR_ACCOUNT_FROZEN);
    }

    public function test_create_payment_handles_non_existing_customer(): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $this->givenCustomerRepositoryThrowsException(new EntityNotFoundException());

        $this->getCreatePaymentJsonResponse($this->accountNumber)
            ->assertNotFound()
            ->assertJsonPath('message', self::ERROR_ACCOUNT_NOT_FOUND);
    }

    public function test_create_payment_handles_payment_service_fatal_error(): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $this->givenCustomerRepositoryReturnsCustomer();
        $this->paymentServiceMock->shouldReceive('addPayment')
            ->with($this->customer, AddPaymentDTO::class)
            ->andThrow(PaymentNotCreatedException::class)
            ->once();

        $this->getCreatePaymentJsonResponse($this->accountNumber)
            ->assertStatus(Response::HTTP_INTERNAL_SERVER_ERROR)
            ->assertJson(fn (AssertableJson $json) => $json->has('message')->etc());
    }

    public function test_create_payment_handles_credit_card_authorization_exception(): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $this->givenCustomerRepositoryReturnsCustomer();
        $this->paymentServiceMock->shouldReceive('addPayment')
            ->with($this->customer, AddPaymentDTO::class)
            ->andThrow(CreditCardAuthorizationException::class)
            ->once();

        $this->getCreatePaymentJsonResponse($this->accountNumber)
            ->assertStatus(Response::HTTP_PAYMENT_REQUIRED)
            ->assertJson(fn (AssertableJson $json) => $json->has('message')->etc());
    }

}
