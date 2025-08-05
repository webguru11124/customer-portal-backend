<?php

declare(strict_types=1);

namespace Tests\Unit\Http\API\V2\Controller;

use App\Exceptions\PlanBuilder\FieldNotFound;
use App\Models\Account;
use App\Services\AccountService;
use App\Services\PlanBuilderService;
use Aptive\Component\Http\HttpStatus;
use Mockery;
use Mockery\MockInterface;
use RuntimeException;
use Tests\Data\PlanBuilderResultsData;
use Tests\Traits\ExpectedV2ResponseData;
use Tests\Traits\RandomIntTestData;
use Tests\Traits\TestAuthorizationMiddleware;
use Tests\Unit\Http\API\V1\Controller\ApiTestCase;
use Throwable;

class ProductControllerTest extends ApiTestCase
{
    use ExpectedV2ResponseData;
    use RandomIntTestData;
    use TestAuthorizationMiddleware;

    public Account $account;

    public PlanBuilderService|MockInterface $planBuilderServiceMock;
    public AccountService|MockInterface $accountServiceMock;

    public function setUp(): void
    {
        parent::setUp();

        $this->planBuilderServiceMock = Mockery::mock(PlanBuilderService::class);
        $this->instance(PlanBuilderService::class, $this->planBuilderServiceMock);
        $this->accountServiceMock = Mockery::mock(AccountService::class);
        $this->instance(AccountService::class, $this->accountServiceMock);
        $this->account = Account::factory()->make([
            'office_id' => $this->getTestOfficeId(),
            'account_number' => $this->getTestAccountNumber(),
        ]);
    }

    public function test_search_requires_authentication(): void
    {
        $this->checkAuthorizationMiddleware(
            fn () => $this->getJson($this->getRoute())
        );
    }

    public function test_search_requires_authorization(): void
    {
        $this->createAndLogInAuth0User();

        $this->getJson($this->getRoute())
            ->assertNotFound();
    }

    /**
     * @param array<string, scalar|scalar[]> $queryParams
     *
     * @return string
     */
    private function getRoute(): string
    {
        return route('api.v2.customer.products.get', ['accountNumber' => $this->getTestAccountNumber()]);
    }

    public function test_get_returns_products(): void
    {
        $this->createAndLogInAuth0UserWithAccount();
        $products = PlanBuilderResultsData::getProducts();
        $this->accountServiceMock->shouldReceive('getAccountByAccountNumber')
            ->with($this->getTestAccountNumber())
            ->andReturn($this->account)
            ->once();
        $this->planBuilderServiceMock
            ->shouldReceive('getProducts')
            ->andReturn($products)
            ->once();

        $response = $this->getJson($this->getRoute());
        $response->assertOk()
            ->assertJsonPath('data.0.id', '1')
            ->assertJsonPath('data.0.attributes.name', 'Accessory Structure')
            ->assertJsonPath('data.0.attributes.image', 'https://s3.amazonaws.com/aptive.staging-01.product-manager-api.bucket/product_images/1/Accessory%20Structure.png')
            ->assertJsonPath('data.0.attributes.is_recurring', false)
            ->assertJsonPath('data.1.type', 'Product')
            ->assertJsonPath('data.1.attributes.initial_price', 130)
            ->assertJsonPath('data.1.attributes.recurring_price', 58)
            ->assertJsonPath('data.1.attributes.pest_routes_id', 1960);
    }

    /**
     * @dataProvider getProductsExceptionProvider
     */
    public function test_get_returns_proper_error_on_exception(Throwable $exception, int $expectedStatusCode): void
    {
        $this->createAndLogInAuth0UserWithAccount();
        $this->accountServiceMock->shouldReceive('getAccountByAccountNumber')
            ->with($this->getTestAccountNumber())
            ->andReturn($this->account)
            ->once();
        $this->planBuilderServiceMock
            ->shouldReceive('getProducts')
            ->andThrow($exception)
            ->once();

        $response = $this->getJson($this->getRoute());
        $response->assertStatus($expectedStatusCode);
    }

    public static function getProductsExceptionProvider(): iterable
    {
        yield 'Unexpected exception' => [
            new RuntimeException(),
            HttpStatus::INTERNAL_SERVER_ERROR,
        ];
        yield 'FieldNotFound' => [
            new FieldNotFound(),
            HttpStatus::INTERNAL_SERVER_ERROR,
        ];
    }
}
