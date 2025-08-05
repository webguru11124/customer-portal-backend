<?php

declare(strict_types=1);

namespace Tests\Unit\Http\API\V2\Controller;

use App\Actions\PaymentProfile\DeletePaymentProfileActionV2;
use App\Actions\PaymentProfile\ShowCustomerPaymentProfilesActionV2;
use App\DTO\Payment\AchPaymentMethod;
use App\DTO\Payment\BasePaymentMethod;
use App\DTO\Payment\CreditCardPaymentMethod;
use App\DTO\UpdatePaymentProfileDTO;
use App\Enums\Models\PaymentProfile\PaymentMethod;
use App\Enums\Models\PaymentProfile\StatusType;
use App\Enums\Resources;
use App\Exceptions\PaymentProfile\PaymentProfileNotFoundException;
use Exception;
use Illuminate\Testing\Fluent\AssertableJson;
use Mockery;
use Symfony\Component\HttpFoundation\Response;
use Tests\Traits\GetPestRoutesPaymentProfile;
use Tests\Traits\RandomStringTestData;
use Tests\Traits\TestAuthorizationMiddleware;
use Throwable;

class PaymentProfileControllerCRUDTest extends PaymentProfileController
{
    use GetPestRoutesPaymentProfile;
    use TestAuthorizationMiddleware;
    use RandomStringTestData;

    public function test_payment_profiles_delete_forbids_unauthorized_access(): void
    {
        $this->checkAuthorizationMiddleware(
            fn () => $this->getDeletePaymentProfileJsonResponse($this->paymentProfileId)
        );
    }

    public function test_payment_profiles_delete_shows_error_when_account_does_not_belong_to_customer(): void
    {
        $this->createAndLogInAuth0User();

        $this->getDeletePaymentProfileJsonResponse($this->paymentProfileId)
            ->assertNotFound();
    }

    /**
     * @dataProvider deleteFailureDataProvider
     */
    public function test_payment_profiles_delete_shows_error_when_delete_fails(
        Throwable $deleteException,
        int $expectedStatus
    ): void {
        $itemId = $this->paymentProfileId;

        $this->createAndLogInAuth0UserWithAccount();

        $actionMock = Mockery::mock(DeletePaymentProfileActionV2::class);
        $this->instance(DeletePaymentProfileActionV2::class, $actionMock);

        $actionMock
            ->shouldReceive('__invoke')
            ->withArgs(
                fn (
                    int $accountNumber,
                    int $paymentProfileId
                ) => $accountNumber === $this->getTestAccountNumber() && $paymentProfileId === $itemId
            )
            ->once()
            ->andThrow($deleteException);

        $this
            ->getDeletePaymentProfileJsonResponse($itemId)
            ->assertStatus($expectedStatus);
    }

    public function test_payment_profiles_delete_returns_abstract_http_exception(): void
    {
        $itemId = $this->paymentProfileId;

        $this->createAndLogInAuth0UserWithAccount();

        $actionMock = Mockery::mock(DeletePaymentProfileActionV2::class);
        $this->instance(DeletePaymentProfileActionV2::class, $actionMock);

        $actionMock
            ->shouldReceive('__invoke')
            ->withArgs(
                fn (
                    int $accountNumber,
                    int $paymentProfileId
                ) => $accountNumber === $this->getTestAccountNumber() && $paymentProfileId === $itemId
            )
            ->once()
            ->andThrow(new PaymentProfileNotFoundException());

        $this
            ->getDeletePaymentProfileJsonResponse($itemId)
            ->assertStatus(Response::HTTP_NOT_FOUND);
    }

    public function test_payment_profiles_delete_deletes_payment_profile(): void
    {
        $itemId = $this->paymentProfileId;

        $this->createAndLogInAuth0UserWithAccount();

        $actionMock = Mockery::mock(DeletePaymentProfileActionV2::class);
        $this->instance(DeletePaymentProfileActionV2::class, $actionMock);

        $actionMock
            ->shouldReceive('__invoke')
            ->withArgs(
                fn (
                    int $accountNumber,
                    int $paymentProfileId
                ) => $accountNumber === $this->getTestAccountNumber() && $paymentProfileId === $itemId
            )
            ->once()
            ->andReturn(true);

        $this
            ->getDeletePaymentProfileJsonResponse($itemId)
            ->assertNoContent();
    }

    public function test_get_payment_profiles_forbids_unauthorized_access(): void
    {
        $this->checkAuthorizationMiddleware(
            fn () => $this->getGetPaymentProfilesJsonResponse()
        );
    }

    public function test_get_payment_profiles_shows_error_when_account_does_not_belong_to_customer(): void
    {
        $this->createAndLogInAuth0User();

        $this->getGetPaymentProfilesJsonResponse()
            ->assertNotFound();
    }

    public function test_get_payment_profiles_searches_for_customer_payment_profiles(): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $paymentProfiles = [
            new CreditCardPaymentMethod(
                paymentMethodId: $this->getTestPaymentMethodUuid(),
                crmAccountId: $this->getTestCrmAccountUuid(),
                type: ucfirst(strtolower(\App\Enums\Models\Payment\PaymentMethod::CREDIT_CARD->value)),
                dateAdded: "2023-11-15 14:14:16",
                isPrimary: true,
                description: 'Test description',
                isAutoPay: false,
                ccType: 'VISA',
                ccLastFour: '1111',
                ccExpirationMonth: 7,
                ccExpirationYear: 2030,
            ),
            new AchPaymentMethod(
                paymentMethodId: $this->getTestPaymentMethodUuid(),
                crmAccountId: $this->getTestCrmAccountUuid(),
                type: PaymentMethod::ACH->value,
                dateAdded: "2023-11-16 10:35:02",
                isPrimary: false,
                isAutoPay: false,
                achAccountLastFour: '1111',
                achRoutingNumber: '985612814',
                achAccountType: 'personal_checking',
                achBankName: 'Universal Bank',
            )
        ];

        /** @var BasePaymentMethod|AchPaymentMethod|CreditCardPaymentMethod $paymentProfile */
        $paymentProfile = current($paymentProfiles);

        $statuses = array_map(
            fn (string $status) => StatusType::from($status),
            explode(',', self::VALID_REQUEST_STATUSES)
        );

        $this->mockShowCustomerPaymentProfilesAction($paymentProfiles, $statuses);

        $response = $this->getGetPaymentProfilesJsonResponse();

        $response
            ->assertOk()
            ->assertJson(
                fn (AssertableJson $json) => $json
                    ->where('data.0.payment_method_id', $paymentProfile->paymentMethodId)
                    ->where('data.0.type', $paymentProfile->type)
                    ->where('data.0.date_added', $paymentProfile->dateAdded)
                    ->where('data.0.is_primary', $paymentProfile->isPrimary)
                    ->where('data.0.is_autopay', $paymentProfile->isAutoPay)
                    ->where('data.0.cc_type', $paymentProfile->ccType)
                    ->where('data.0.cc_last_four', $paymentProfile->ccLastFour)
                    ->where('data.0.cc_expiration_month', $paymentProfile->ccExpirationMonth)
                    ->where('data.0.cc_expiration_year', $paymentProfile->ccExpirationYear)
                    ->where('data.0.description', $paymentProfile->description)
            );
    }

    public function test_get_payment_profiles_handles_fatal_error(): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $actionMock = Mockery::mock(ShowCustomerPaymentProfilesActionV2::class);
        $this->instance(ShowCustomerPaymentProfilesActionV2::class, $actionMock);
        $actionMock->shouldReceive('__invoke')->andThrow(new Exception('Error'));

        $response = $this->getGetPaymentProfilesJsonResponse();

        $response->assertStatus(Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    public function test_get_payment_profiles_returns_abstract_http_exception(): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $actionMock = Mockery::mock(ShowCustomerPaymentProfilesActionV2::class);
        $this->instance(ShowCustomerPaymentProfilesActionV2::class, $actionMock);
        $actionMock->shouldReceive('__invoke')->andThrow(new PaymentProfileNotFoundException());

        $response = $this->getGetPaymentProfilesJsonResponse();

        $response->assertStatus(Response::HTTP_NOT_FOUND);
    }

    public function test_update_payment_profile_forbids_unauthorized_access(): void
    {
        $this->checkAuthorizationMiddleware(
            fn () => $this->getPatchPaymentProfileJsonResponse()
        );
    }

    public function test_update_payment_profile_shows_error_when_account_does_not_belong_to_customer(): void
    {
        $this->createAndLogInAuth0User();

        $this->getPatchPaymentProfileJsonResponse()
            ->assertNotFound();
    }

    public function test_update_payment_profile_updates_payment_profile(): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $this->paymentProfileServiceMock->expects('updatePaymentProfile')
            ->with(UpdatePaymentProfileDTO::class)
            ->once()
            ->andReturn($this->getPestRoutesPaymentProfile(billingName: 'John Doe Smith'));

        $this->getPatchPaymentProfileJsonResponse()
            ->assertOk()
            ->assertExactJson($this->getResourceUpdatedExpectedResponse(
                'customer/' . $this->getTestAccountNumber() . '/paymentprofiles/' . $this->paymentProfileId,
                Resources::PAYMENT_PROFILE->value,
                $this->paymentProfileId
            ));
    }

    /**
     * @dataProvider provideUpdatePaymentProfileExceptionsData
     */
    public function test_update_payment_profile_returns_valid_responses_on_exceptions(
        $exception,
        $status,
        string $message
    ): void {
        $this->createAndLogInAuth0UserWithAccount();

        $this->paymentProfileServiceMock->expects('updatePaymentProfile')
            ->with(UpdatePaymentProfileDTO::class)
            ->andThrow($exception)
            ->once();

        $this->getPatchPaymentProfileJsonResponse()
            ->assertStatus($status)
            ->assertJsonPath('errors.0.title', $message);
    }

    public function test_update_payment_profile_returns_valid_response_on_validation_exception(): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $this->getPatchPaymentProfileJsonResponse(['expMonth' => 14])
            ->assertUnprocessable()
            ->assertJsonPath('errors.expMonth.0', 'The exp month must be between 1 and 12.');
    }

}
