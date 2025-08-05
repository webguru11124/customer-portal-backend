<?php

declare(strict_types=1);

namespace Tests\Unit\Http\API\V1\Controller;

use App\Actions\CompleteCreditCardTransactionSetupAction;
use App\Actions\CreateCreditCardTransactionSetupAction;
use App\Exceptions\PaymentProfile\CreditCardAuthorizationException;
use App\Exceptions\PaymentProfile\PaymentProfileNotFoundException;
use App\Exceptions\PaymentProfile\PaymentProfilesNotFoundException;
use App\Exceptions\TransactionSetup\TransactionSetupException;
use App\Services\LogService;
use Exception;
use Illuminate\Support\Str;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Testing\TestResponse;
use Mockery;
use Mockery\MockInterface;
use Symfony\Component\HttpFoundation\Response;
use Tests\Data\PaymentProfileData;
use Tests\Traits\TestAuthorizationMiddleware;

class CreditCardTransactionSetupControllerTest extends ApiTestCase
{
    use TestAuthorizationMiddleware;

    public string $slug = '123123';
    public string $methodName = '__invoke';
    public array $createTransactionPayload = [
        'slug' =>  '123123',
        'billing_name' => 'John Joe',
        'billing_address_line_1' => 'Aptive Street',
        'billing_address_line_2' => 'Unit #456',
        'billing_city' => 'Orlando',
        'billing_state' => 'FL',
        'billing_zip' => '32832',
        'auto_pay' => 1,
    ];

    public string $transactionSetupId = '7ADA6A04-814B-4E4C-9014-5085604D39E9';
    public array $completeTransactinSetupPayload = [
        'PaymentAccountID' => '162317263',
        'HostedPaymentStatus' => 'Complete',
        'ValidationCode' => '89F5694DC8814A73',
    ];

    public MockInterface $completeActionMock;
    public MockInterface $createActionMock;
    public MockInterface $logServiceMock;

    public function setUp(): void
    {
        parent::setUp();

        $this->completeActionMock = Mockery::mock(CompleteCreditCardTransactionSetupAction::class);
        $this->createActionMock = Mockery::mock(CreateCreditCardTransactionSetupAction::class);
        $this->logServiceMock = Mockery::mock(LogService::class);
        $this->instance(LogService::class, $this->logServiceMock);
        $this->instance(CreateCreditCardTransactionSetupAction::class, $this->createActionMock);
        $this->instance(CompleteCreditCardTransactionSetupAction::class, $this->completeActionMock);
    }

    public function test_store_forbids_unauthorized_access(): void
    {
        $this->checkAuthorizationMiddleware(
            fn () => $this->getCreateJsonResponse($this->createTransactionPayload)
        );
    }

    public function test_store_do_not_require_authorization(): void
    {
        $this->createAndLogInAuth0User();
        $this->checkStoreActionReturnsValidResult();
    }

    public function test_store_calls_the_action(): void
    {
        $this->createAndLogInAuth0UserWithAccount();
        $this->checkStoreActionReturnsValidResult();
    }

    protected function checkStoreActionReturnsValidResult(): void
    {
        $transactionSetupID = 'testid12345';
        $redirectURL = Str::replace(
            '{{TransactionSetupID}}',
            $transactionSetupID,
            config('worldpay.transaction_setup_url')
        );

        $this->createActionMock->shouldReceive($this->methodName)
            ->with(...array_values($this->createTransactionPayload))
            ->andReturn($transactionSetupID)
            ->once();

        $this->getCreateJsonResponse($this->createTransactionPayload)
            ->assertOk()
            ->assertJson(['url' => $redirectURL]);
    }

    public function test_store_calls_the_action_without_autopay(): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $transactionSetupID = 'testid12345';

        $arguments = $this->createTransactionPayload;
        $arguments['auto_pay'] = 0;
        $this->createActionMock->shouldReceive($this->methodName)
            ->with(...array_values($arguments))
            ->andReturn($transactionSetupID)
            ->once();

        $validData = $this->createTransactionPayload;
        unset($validData['auto_pay']);

        $this->getCreateJsonResponse($validData)
            ->assertOk()
            ->assertJson(['url' => 'https://certtransaction.hostedpayments.com/?TransactionSetupID=testid12345']);
    }

    public function test_store_returns_error_on_invalid_input(): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $invalidData = $this->createTransactionPayload;
        unset($invalidData['billing_name']);

        $this->getCreateJsonResponse($invalidData)
            ->assertUnprocessable()
            ->assertJsonPath('message', 'The billing name field is required.');
    }

    public function test_store_handles_transaction_setup_exception(): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $this->createActionMock->shouldReceive($this->methodName)
            ->with(...array_values($this->createTransactionPayload))
            ->andThrow(TransactionSetupException::class);

        $this->getCreateJsonResponse($this->createTransactionPayload)
            ->assertStatus(Response::HTTP_INTERNAL_SERVER_ERROR)
            ->assertJson(fn (AssertableJson $json) => $json->has('errors')->etc());
    }

    public function test_store_handles_exception(): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $this->createActionMock->shouldReceive($this->methodName)
            ->with(...array_values($this->createTransactionPayload))
            ->andThrow(Exception::class);

        $this->getCreateJsonResponse($this->createTransactionPayload)
            ->assertStatus(Response::HTTP_INTERNAL_SERVER_ERROR)
            ->assertJson(fn (AssertableJson $json) => $json->has('message')->etc());
    }

    public function test_complete_forbids_unauthorized_access(): void
    {
        $this->checkAuthorizationMiddleware(
            fn () => $this->getCompleteJsonResponse($this->completeTransactinSetupPayload)
        );
    }

    public function test_complete_do_not_require_authorization(): void
    {
        $this->createAndLogInAuth0User();
        $this->checkCompleteActionReturnsValidResult();
    }

    public function test_complete_calls_the_action(): void
    {
        $this->createAndLogInAuth0UserWithAccount();
        $this->checkCompleteActionReturnsValidResult();
    }

    protected function checkCompleteActionReturnsValidResult(): void
    {
        $paymentProfile = PaymentProfileData::getTestEntityData(
            1,
            ['merchantID' => $this->transactionSetupId]
        )->first();

        $this->completeActionMock->shouldReceive($this->methodName)
            ->withArgs([
                $this->transactionSetupId,
                $this->completeTransactinSetupPayload['HostedPaymentStatus'],
                $this->completeTransactinSetupPayload['PaymentAccountID'],
            ])
            ->andReturn($paymentProfile)
            ->once();

        $this->getCompleteJsonResponse($this->completeTransactinSetupPayload)
            ->assertOk()
            ->assertJsonPath('merchantID', $this->transactionSetupId);
    }

    public function test_complete_without_paymentaccountid_calls_the_action(): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $paymentProfile = PaymentProfileData::getTestEntityData(
            1,
            ['merchantID' => $this->transactionSetupId]
        )->first();

        $this->completeActionMock->shouldReceive($this->methodName)
            ->withArgs([
                $this->transactionSetupId,
                $this->completeTransactinSetupPayload['HostedPaymentStatus'],
                null,
            ])
            ->andReturn($paymentProfile)
            ->once();

        $payload = $this->completeTransactinSetupPayload;
        $payload['PaymentAccountID'] = null;

        $this->getCompleteJsonResponse($payload)
            ->assertOk()
            ->assertJsonPath('merchantID', $this->transactionSetupId);
    }

    public function test_complete_with_invalid_data_returns_validation_error(): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $payload = $this->completeTransactinSetupPayload;
        $payload['HostedPaymentStatus'] = null;

        $this->getCompleteJsonResponse($payload)
            ->assertUnprocessable()
            ->assertJsonPath('message', 'The hosted payment status field is required.');
    }

    /**
     * @dataProvider provideErrorData
     */
    public function test_complete_returns_error_on_exception(Exception $exception): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $this->completeActionMock->shouldReceive($this->methodName)
            ->withArgs([
                $this->transactionSetupId,
                $this->completeTransactinSetupPayload['HostedPaymentStatus'],
                $this->completeTransactinSetupPayload['PaymentAccountID'],
            ])
            ->andThrow($exception)
            ->once();

        $this->getCompleteJsonResponse($this->completeTransactinSetupPayload)
            ->assertStatus(Response::HTTP_INTERNAL_SERVER_ERROR)
            ->assertJson(fn (AssertableJson $json) => $json->hasAny(['message', 'errors'])->etc());
    }

    public function provideErrorData(): array
    {
        return [
            [new CreditCardAuthorizationException()],
            [new PaymentProfileNotFoundException()],
            [new PaymentProfilesNotFoundException()],
            [new Exception()],
        ];
    }

    protected function getCreateJsonResponse(array $postData): TestResponse
    {
        return $this->postJson(
            route('api.transaction-setup.credit-card.store', ['slug' => $this->createTransactionPayload['slug']]),
            $postData
        );
    }

    protected function getCompleteJsonResponse(array $postData): TestResponse
    {
        return $this->postJson(
            route('api.transaction-setup.credit-card.complete', ['tsId' => $this->transactionSetupId]),
            $postData
        );
    }
}
