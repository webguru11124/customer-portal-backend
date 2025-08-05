<?php

declare(strict_types=1);

namespace Tests\Unit\Http\API\V1\Controller;

use App\DTO\Customer\AutoPayResponseDTO;
use App\Enums\Resources;
use App\Models\Account;
use App\Services\CustomerService;
use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use Aptive\Component\JsonApi\JsonApi;
use DateTimeImmutable;
use Illuminate\Testing\TestResponse;
use Mockery;
use Symfony\Component\HttpFoundation\Response;
use Tests\Traits\TestAuthorizationMiddleware;

final class AutoPayControllerTest extends ApiTestCase
{
    use TestAuthorizationMiddleware;

    public function test_get_auto_pay_data_requires_authentication(): void
    {
        $this->checkAuthorizationMiddleware(
            fn () => $this->getJsonResponse()
        );
    }

    public function test_get_auto_pay_data_requires_authorization(): void
    {
        $this->createAndLogInAuth0User();

        $this->getJsonResponse()
            ->assertNotFound();
    }

    public function test_get_auto_pay_data_returns_internal_server_error_when_fetching_autopay_data(): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $customerServiceMock = Mockery::mock(CustomerService::class);
        $customerServiceMock
            ->expects('getCustomerAutoPayData')
            ->withAnyArgs()
            ->once()
            ->andThrow(new InternalServerErrorHttpException());

        $this->instance(CustomerService::class, $customerServiceMock);

        $this->getJsonResponse()
            ->assertStatus(Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    public function test_get_auto_pay_data_returns_autopay_data(): void
    {
        $this->createAndLogInAuth0UserWithAccount();
        $nextPaymentDate = new DateTimeImmutable('tomorrow');

        $customerServiceMock = Mockery::mock(CustomerService::class);
        $customerServiceMock
            ->expects('getCustomerAutoPayData')
            ->withArgs(fn (Account $account) => $account->account_number === $this->getTestAccountNumber())
            ->once()
            ->andReturn([
                new AutoPayResponseDTO(
                    id: 19997,
                    isEnabled: true,
                    cardType: 'Visa',
                    cardLastFour: '1111',
                    planName: 'VIP',
                    nextPaymentAmount: 197.97,
                    nextPaymentDate: $nextPaymentDate,
                    preferredBillingDate: 'January 1st'
                ),
            ]);

        $this->instance(
            CustomerService::class,
            $customerServiceMock
        );

        $this->getJsonResponse()
            ->assertOk()
            ->assertExactJson($this->getAutopayExpectedData($nextPaymentDate));
    }

    protected function getJsonResponse(): TestResponse
    {
        return $this->getJson(
            route('api.customer.autopay.get', ['accountNumber' => $this->getTestAccountNumber()])
        );
    }

    private function getAutopayExpectedData(DateTimeImmutable $nextPaymentDate): array
    {
        return [
            'links' => [
                'self' => sprintf('/api/v1/customer/%d/autopay', $this->getTestAccountNumber()),
            ],
            'data' => [[
                'id' => '19997',
                'type' => Resources::AUTOPAY->value,
                'attributes' => [
                    'isEnabled' => true,
                    'cardType' => 'Visa',
                    'cardLastFour' => '1111',
                    'planName' => 'VIP',
                    'nextPaymentAmount' => 197.97,
                    'nextPaymentDate' => $nextPaymentDate->format(JsonApi::DEFAULT_DATE_FORMAT),
                    'preferredBillingDate' => 'January 1st',
                ],
            ]],
        ];
    }
}
