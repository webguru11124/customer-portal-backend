<?php

declare(strict_types=1);

namespace Tests\Unit\Http\API\V2\Controller;

use App\Actions\PaymentProfile\ShowCustomerPaymentProfilesActionV2;
use App\Enums\Models\PaymentProfile\StatusType;
use App\Exceptions\Authorization\UnauthorizedException;
use App\Exceptions\PaymentProfile\PaymentProfileNotDeletedException;
use App\Exceptions\PaymentProfile\PaymentProfileNotFoundException;
use App\Models\Account;
use App\Services\AccountService;
use App\Services\PaymentProfileService;
use App\Services\TransactionSetupService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Mockery;
use Mockery\MockInterface;
use Symfony\Component\HttpFoundation\Response;
use Tests\Traits\ExpectedV2ResponseData;
use Tests\Traits\RandomIntTestData;
use Tests\Traits\TestAuthorizationMiddleware;

class PaymentProfileController extends ApiTestCase
{
    use ExpectedV2ResponseData;
    use RandomIntTestData;
    use RefreshDatabase;
    use TestAuthorizationMiddleware;

    protected const REDIRECT_URI = 'schema://test?id=12';
    protected const VALID_REQUEST_STATUSES = 'valid,expired,failed';
    protected const VALID_REQUEST_METHODS = 'CC,ACH';

    public int $paymentProfileId;
    public Account $account;
    public TransactionSetupService|MockInterface|null $transactionSetupServiceMock;
    public AccountService|MockInterface|null $accountServiceMock;
    public PaymentProfileService|MockInterface|null $paymentProfileServiceMock;

    public function setUp(): void
    {
        parent::setUp();
        $this->accountServiceMock = Mockery::mock(AccountService::class);
        $this->paymentProfileServiceMock = Mockery::mock(PaymentProfileService::class);
        $this->instance(PaymentProfileService::class, $this->paymentProfileServiceMock);
        $this->instance(AccountService::class, $this->accountServiceMock);

        $this->paymentProfileId = $this->getTestPaymentProfileId();

        $this->account = Account::factory()->make([
            'office_id' => $this->getTestOfficeId(),
            'account_number' => $this->getTestAccountNumber(),
        ]);
    }

    /**
     * @param \App\DTO\Payment\PaymentMethod[] $paymentProfiles
     * @param StatusType[] $inputStatuses
     *
     * @return MockInterface|ShowCustomerPaymentProfilesActionV2
     */
    protected function mockShowCustomerPaymentProfilesAction(
        array $paymentProfiles,
        array $inputStatuses = [],
    ): MockInterface|ShowCustomerPaymentProfilesActionV2 {
        $actionMock = Mockery::mock(ShowCustomerPaymentProfilesActionV2::class);
        $actionMock->shouldReceive('__invoke')
                   ->withArgs(function (
                       int $accountNumber,
                       array $statuses,
                   ) use ($inputStatuses) {
                       return $accountNumber === $this->getTestAccountNumber() && $statuses === $inputStatuses;
                   })
                   ->once()
                   ->andReturn($paymentProfiles);
        $this->instance(ShowCustomerPaymentProfilesActionV2::class, $actionMock);

        return $actionMock;
    }


    public function deleteFailureDataProvider(): array
    {
        return [
            [
                'deleteException' => new PaymentProfileNotFoundException(),
                'expectedStatus' => Response::HTTP_NOT_FOUND,
            ],
            'api error' => [
                'deleteException' => new PaymentProfileNotDeletedException(),
                'expectedStatus' => Response::HTTP_INTERNAL_SERVER_ERROR,
            ],
            'PP is autopay' => [
                'deleteException' => new PaymentProfileNotDeletedException(
                    'Can not delete a payment profile because it is set for autopay.',
                    PaymentProfileNotDeletedException::STATUS_LOCKED
                ),
                'expectedStatus' => Response::HTTP_CONFLICT,
            ],
            [
                'deleteException' => new UnauthorizedException(),
                'expectedStatus' => Response::HTTP_UNAUTHORIZED,
            ],
            [
                'exception' => new Exception(),
                'expectedStatus' => Response::HTTP_INTERNAL_SERVER_ERROR,
            ],
        ];
    }

    public function provideUpdatePaymentProfileExceptionsData(): array
    {
        return [
            [
                'exception' => new Exception(),
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => '500 Internal Server Error',
            ],
            [
                'exception' => new PaymentProfileNotFoundException(),
                'status' => Response::HTTP_NOT_FOUND,
                'message' => '404 Not Found',
            ],
        ];
    }

    protected function getGetPaymentProfilesJsonResponse(int|null $accountNumber = null): TestResponse
    {
        $accountNumber = $accountNumber ?? $this->getTestAccountNumber();

        return $this->getJson(route(
            'api.v2.customer.paymentprofiles.get',
            [
                'accountNumber' => $accountNumber,
                'statuses' => self::VALID_REQUEST_STATUSES,
            ]
        ));
    }

    protected function getPatchPaymentProfileJsonResponse(array $request = []): TestResponse
    {
        $request = array_merge(
            [
                'billingFName' => 'Jonh',
                'billingLName' => 'Doe',
                'expMonth' => 12,
            ],
            $request
        );

        $route = route(
            'api.v2.customer.paymentprofiles.update',
            [
                'accountNumber' => $this->getTestAccountNumber(),
                'paymentProfileId' =>  $this->paymentProfileId,
            ]
        );

        return $this->patchJson($route, $request);
    }

    protected function getDeletePaymentProfileJsonResponse(int $paymentProfileId): TestResponse
    {
        return $this->deleteJson(route(
            'api.v2.customer.paymentprofiles.delete',
            [
                'accountNumber' => $this->getTestAccountNumber(),
                'paymentProfileId' => $paymentProfileId,
            ]
        ));
    }
}
