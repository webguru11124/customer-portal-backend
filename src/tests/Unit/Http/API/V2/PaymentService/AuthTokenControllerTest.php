<?php

declare(strict_types=1);

namespace Tests\Unit\Http\API\V2\PaymentService;

use App\DTO\Payment\TokenexAuthKeysRequestDTO;
use App\DTO\Payment\TokenexAuthKeys;
use Illuminate\Support\Facades\Config;
use App\Repositories\AptivePayment\AptivePaymentRepository;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\MockObject\Exception;
use Tests\Traits\ExpectedV2ResponseData;
use Tests\Traits\RandomIntTestData;
use Tests\Traits\TestAuthorizationMiddleware;
use Tests\Unit\Http\API\V1\Controller\ApiTestCase;

final class AuthTokenControllerTest extends ApiTestCase
{
    use ExpectedV2ResponseData;
    use RandomIntTestData;
    use TestAuthorizationMiddleware;

    public AptivePaymentRepository|MockInterface $paymentRepoMock;
    private const REQUEST_BODY = [
        'origin'=>"https://localhost:8080",
        'timestamp' => "20231013140400"
    ];

    public function setUp(): void
    {
        parent::setUp();

        $this->paymentRepoMock = Mockery::mock(AptivePaymentRepository::class);
        $this->instance(AptivePaymentRepository::class, $this->paymentRepoMock);
    }

    public function test_search_requires_authentication(): void
    {
        $this->checkAuthorizationMiddleware(
            fn () => $this->postJson(
                $this->getRoute(),
                self::REQUEST_BODY
            )
        );
    }

    /**
     * @param array<string, scalar|scalar[]> $queryParams
     *
     * @return string
     */
    private function getRoute(): string
    {
        return route('api.v2.payment-service.auth-token');
    }

    /**
     * @throws Exception when random_int fails
     */
    public function test_returns_auth_token(): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $tokenData = [
            "_metadata" => [
                  "success" => true,
                  "links" => [
                     "self" => "https://api.payment-service.tst.goaptive.com/api/v1/gateways/tokenex/authentication-keys"
                  ]
               ],
            "result" => (object)[
                        "message" => "TokenEx Authentication Key generated successfully.",
                        "authentication_key" => "uGy2U3xeElLo/j95Dg841WmHP/uNIDAkQkdug/vwg/g="
                     ]
         ];
        $tokenResponse = TokenexAuthKeys::fromApiResponse((object) $tokenData);

        Config::set('payment.api_token_scheme', 'PCI');

        $this->paymentRepoMock
            ->shouldReceive('getTokenexAuthKeys')
            ->withArgs(
                fn (TokenexAuthKeysRequestDTO $dto) =>
                    $dto->tokenScheme == "PCI" &&
                    $dto->origins == [self::REQUEST_BODY['origin']] &&
                    $dto->timestamp == self::REQUEST_BODY['timestamp']
            )
            ->andReturn($tokenResponse)
            ->once();

        $response = $this->postJson(
            $this->getRoute(),
            self::REQUEST_BODY
        );

        $response->assertOk();
    }
}
