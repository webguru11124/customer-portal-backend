<?php

declare(strict_types=1);

namespace Tests\Unit\Http\API\V2\Controller;

use App\Exceptions\Account\AccountFrozenException;
use App\Exceptions\Entity\EntityNotFoundException;
use Illuminate\Testing\Fluent\AssertableJson;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Tests\Traits\TestAuthorizationMiddleware;
use Throwable;

class PaymentControllerGet extends PaymentController
{
    use TestAuthorizationMiddleware;
    public function test_get_payments_forbids_unauthorized_access(): void
    {
        $this->checkAuthorizationMiddleware(
            fn () => $this->getPaymentsJsonResponse()
        );
    }

    public function test_get_payments_shows_error_for_not_authorised_user(): void
    {
        $this->createAndLogInAuth0User();

        $this->getPaymentsJsonResponse()
            ->assertNotFound();
    }

    public function test_get_payments_searches_for_customer_payments(): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $paymentIds = [
            123,
            456,
            789,
        ];
        $this->givenCustomerRepositoryReturnsCustomer();
        $this->paymentServiceMock->shouldReceive('getPaymentIds')
            ->with($this->customer)
            ->andReturn($paymentIds)
            ->once();

        $this->getPaymentsJsonResponse()
            ->assertOK()
            ->assertJson($paymentIds);
    }

    public function test_get_payments_returns404_for_existing_frozen_customer(): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $this->givenCustomerRepositoryThrowsException(new AccountFrozenException());

        $this->getPaymentsJsonResponse()
            ->assertNotFound()
            ->assertJsonPath('message', self::ERROR_ACCOUNT_FROZEN);
    }

    public function test_get_payments_handles_non_existing_customer(): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $this->givenCustomerRepositoryThrowsException(new EntityNotFoundException());

        $this->getPaymentsJsonResponse()
            ->assertNotFound()
            ->assertJsonPath('message', self::ERROR_ACCOUNT_NOT_FOUND);
    }

    public function test_get_payments_handles_fatal_error(): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $this->givenCustomerRepositoryReturnsCustomer();
        $this->paymentServiceMock->shouldReceive('getPaymentIds')
            ->with($this->customer)
            ->andThrow(new RuntimeException('Test'))
            ->once();

        $this->getPaymentsJsonResponse()
            ->assertStatus(Response::HTTP_INTERNAL_SERVER_ERROR)
            ->assertJson(fn (AssertableJson $json) => $json->has('message')->etc());
    }

    public function test_get_payment_forbids_unauthorized_access(): void
    {
        $this->checkAuthorizationMiddleware(
            fn () => $this->getPaymentJsonResponse()
        );
    }

    public function test_get_payment_shows_error_for_not_authorised_user(): void
    {
        $this->createAndLogInAuth0User();

        $this->getPaymentJsonResponse()
            ->assertNotFound();
    }

    public function test_get_payment_searches_for_payment(): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $payment = $this->getValidPayment();
        $this->givenCustomerRepositoryReturnsCustomer();
        $this->paymentServiceMock->shouldReceive('getPayment')
            ->with($this->customer, $payment->id)
            ->andReturn($payment)
            ->once();

        $this->getPaymentJsonResponse()
            ->assertOK()
            ->assertExactJson($payment->toArray());
    }

    public function test_get_payment_returns404_for_existing_frozen_customer(): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $this->givenCustomerRepositoryThrowsException(new AccountFrozenException());

        $this->getPaymentJsonResponse()
            ->assertNotFound()
            ->assertJsonPath('message', self::ERROR_ACCOUNT_FROZEN);
    }

    public function test_get_payment_handles_non_existing_customer(): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $this->givenCustomerRepositoryThrowsException(new EntityNotFoundException());

        $this->getPaymentJsonResponse()
            ->assertNotFound()
            ->assertJsonPath('message', self::ERROR_ACCOUNT_NOT_FOUND);
    }

    /**
     * @dataProvider paymentServiceExceptionProvider
     */
    public function test_get_payment_handles_fatal_error(
        Throwable $exception,
        int $expectedStatusCode,
    ): void {
        $this->createAndLogInAuth0UserWithAccount();

        $this->givenCustomerRepositoryReturnsCustomer();
        $this->paymentServiceMock->shouldReceive('getPayment')
            ->with($this->customer, $this->paymentId)
            ->andThrow($exception)
            ->once();

        $this->getPaymentJsonResponse()
            ->assertStatus($expectedStatusCode)
            ->assertJson(fn (AssertableJson $json) => $json->has('message')->etc());
    }
}
