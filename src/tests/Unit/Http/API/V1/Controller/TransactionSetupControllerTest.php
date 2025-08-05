<?php

declare(strict_types=1);

namespace Tests\Unit\Http\API\V1\Controller;

use App\Actions\CreateTransactionSetupAction;
use App\Actions\RetrieveTransactionSetupBySlugAction;
use App\Exceptions\TransactionSetup\TransactionSetupExpiredException;
use App\Models\TransactionSetup;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\ItemNotFoundException;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Testing\TestResponse;
use Mockery;
use Mockery\MockInterface;
use Symfony\Component\HttpFoundation\Response;
use Tests\Traits\RandomIntTestData;
use Tests\Traits\TestAuthorizationMiddleware;

class TransactionSetupControllerTest extends ApiTestCase
{
    use RandomIntTestData;
    use TestAuthorizationMiddleware;

    public string $slug = '1f4g2a';
    public int $accountNumber;
    protected string $methodName = '__invoke';

    public MockInterface $createActionMock;
    public MockInterface $retrieveActionMock;

    public function setUp(): void
    {
        parent::setUp();
        $this->accountNumber = $this->getTestAccountNumber();

        $this->retrieveActionMock = Mockery::mock(RetrieveTransactionSetupBySlugAction::class);
        $this->createActionMock = Mockery::mock(CreateTransactionSetupAction::class);
        $this->instance(RetrieveTransactionSetupBySlugAction::class, $this->retrieveActionMock);
        $this->instance(CreateTransactionSetupAction::class, $this->createActionMock);
    }

    public function test_show_requires_authentication(): void
    {
        $this->checkAuthorizationMiddleware(fn () => $this->getShowJsonResponse());
    }

    public function test_show_retrieves_transaction_setup_from_a_slug_for_user_without_account(): void
    {
        $this->createAndLogInAuth0User();
        $this->checkShowReturnsTransactionSetup();
    }

    public function test_show_retrieves_transaction_setup_from_a_slug(): void
    {
        $this->createAndLogInAuth0UserWithAccount();
        $this->checkShowReturnsTransactionSetup();
    }

    protected function checkShowReturnsTransactionSetup(): void
    {
        $transactionSetup = TransactionSetup::factory()->generated()->withAddress()->make([
            'slug' => $this->slug,
        ]);

        $expectedArray = [
            'slug' => $this->slug,
            'account_number' => $transactionSetup->account_number,
            'status' => $transactionSetup->status->value,
            'transaction_setup_id' => $transactionSetup->transaction_setup_id,
            'billing_name' => $transactionSetup->billing_name,
            'billing_address_line_1' => $transactionSetup->billing_address_line_1,
            'billing_address_line_2' => $transactionSetup->billing_address_line_2,
            'billing_city' => $transactionSetup->billing_city,
            'billing_state' => $transactionSetup->billing_state,
            'billing_zip' => $transactionSetup->billing_zip,
        ];

        $this->retrieveActionMock
            ->shouldReceive($this->methodName)
            ->with($this->slug)
            ->andReturn($expectedArray);

        $this->getShowJsonResponse()
            ->assertOk()
            ->assertJson($expectedArray);
    }

    public function test_show_returns_error_message_on_exception(): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $this->retrieveActionMock
            ->shouldReceive($this->methodName)
            ->with($this->slug)
            ->andThrow(ItemNotFoundException::class);

        $this->getShowJsonResponse()
            ->assertStatus(Response::HTTP_INTERNAL_SERVER_ERROR)
            ->assertJson(fn (AssertableJson $json) => $json->has('message')->etc());
    }

    public function test_show_returns_410_on_transaction_setup_expired_exception(): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $this->retrieveActionMock
            ->shouldReceive($this->methodName)
            ->with($this->slug)
            ->andThrow(TransactionSetupExpiredException::class);

        $this->getShowJsonResponse()
            ->assertStatus(Response::HTTP_GONE);
    }

    public function test_create_requires_authentication(): void
    {
        $this->checkAuthorizationMiddleware(fn () => $this->getCreateJsonResponse());
    }

    public function test_create_retrieves_transaction_setup(): void
    {
        $this->createAndLogInAuth0UserWithAccount();
        $this->checkCreateRetrievesTransactionSetup();
    }

    protected function checkCreateRetrievesTransactionSetup(): void
    {
        $transactionSetup = TransactionSetup::factory()->generated()->make([
            'slug' => $this->slug,
            'account_number' => $this->accountNumber,
        ]);

        $this->createActionMock
            ->shouldReceive($this->methodName)
            ->andReturn($transactionSetup);

        $this->getCreateJsonResponse()
            ->assertOk()
            ->assertJson([
                'slug' => $this->slug,
                'account_number' => $transactionSetup->account_number,
                'status' => $transactionSetup->status->value,
                'transaction_setup_id' => $transactionSetup->transaction_setup_id,
            ]);
    }

    public function test_create_returns_error_message_on_exception(): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $this->createActionMock
            ->shouldReceive($this->methodName)
            ->with($this->accountNumber)
            ->andThrow(ModelNotFoundException::class);

        $this->getCreateJsonResponse()
            ->assertStatus(Response::HTTP_INTERNAL_SERVER_ERROR)
            ->assertJson(fn (AssertableJson $json) => $json->has('errors')->etc());
    }

    public function test_create_returns_422_error_message_on_invalid_request(): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $this->postJson(route('api.transaction-setup.create'), [])
            ->assertUnprocessable()
            ->assertJson(fn (AssertableJson $json) => $json->has('message')->etc());
    }

    protected function getShowJsonResponse(): TestResponse
    {
        return $this->getJson(route('api.transaction-setup.show', ['slug' => $this->slug]));
    }

    protected function getCreateJsonResponse(): TestResponse
    {
        return $this->postJson(route('api.transaction-setup.create'), ['accountId' => $this->accountNumber]);
    }
}
