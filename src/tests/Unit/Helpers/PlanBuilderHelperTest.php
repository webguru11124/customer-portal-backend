<?php

namespace Tests\Unit\Helpers;

use App\DTO\PlanBuilder\Category;
use App\DTO\PlanBuilder\Status;
use App\Exceptions\PlanBuilder\CannotFetchPlanBuilderDataException;
use App\Helpers\PlanBuilderHelper;
use App\Repositories\PlanBuilder\PlanBuilderRepository;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class PlanBuilderHelperTest extends TestCase
{
    protected PlanBuilderHelper $planBuilderHelper;
    protected PlanBuilderRepository $planBuilderRepositoryMock;

    public function setUp(): void
    {
        parent::setUp();

        $this->planBuilderRepositoryMock = \Mockery::mock(PlanBuilderRepository::class);
        $this->planBuilderHelper = new PlanBuilderHelper($this->planBuilderRepositoryMock);
    }

    /**
     * @dataProvider providePlanBuilderCategoriesData
     */
    public function test_it_returns_category(
        array $planBuilderCategories,
        Category|null $expectedCategory
    ): void {
        $this->planBuilderRepositoryMock
            ->shouldReceive('getPlanCategories')
            ->andReturn($planBuilderCategories);

        $this->setUpConfig('planbuilder.customer_portal.category_name', 'Customer Portal');

        $this->assertEquals($expectedCategory, $this->planBuilderHelper->getPlanBuilderCustomerPortalCategory());
    }

    public function test_customer_portal_category_throw_an_exception_on_plan_builder_failure(): void
    {
        $this->planBuilderRepositoryMock
            ->shouldReceive('getPlanCategories')
            ->andThrow(\Exception::class);

        $this->expectException(CannotFetchPlanBuilderDataException::class);

        $this->planBuilderHelper->getPlanBuilderCustomerPortalCategory();
    }

    /**
     * @dataProvider providePlanBuilderStatusData
     */
    public function test_it_returns_status(
        array $planBuilderStatuses,
        Status|null $expectedStatus
    ): void {
        $this->planBuilderRepositoryMock
            ->shouldReceive('getSettings')
            ->andReturn([
                'planStatuses' => $planBuilderStatuses,
            ]);

        $this->setUpConfig('planbuilder.customer_portal.active_status_name', 'Active');

        $this->assertEquals($expectedStatus, $this->planBuilderHelper->getPlanBuilderActiveStatus());
    }

    public function test_active_status_throw_an_exception_on_plan_builder_failure(): void
    {
        $this->planBuilderRepositoryMock
            ->shouldReceive('getSettings')
            ->andThrow(\Exception::class);

        $this->expectException(CannotFetchPlanBuilderDataException::class);

        $this->planBuilderHelper->getPlanBuilderActiveStatus();
    }

    protected function providePlanBuilderCategoriesData(): iterable
    {
        $customerPortalCategory = $this->createFakeCategory('Customer Portal');

        yield 'return_null_category_due_to_plan_builder_empty_categories' => [
            [],
            null,
        ];

        yield 'return_null_category_due_to_plan_builder_missing_categories' => [
            [
                $this->createFakeCategory('Direct to Home'),
                $this->createFakeCategory('Service Pro'),
                $this->createFakeCategory('Self Checkout'),
            ],
            null,
        ];

        yield 'return_category' => [
            [
                $this->createFakeCategory('Direct to Home'),
                $this->createFakeCategory('Service Pro'),
                $this->createFakeCategory('Self Checkout'),
                $customerPortalCategory,
            ],
            $customerPortalCategory,
        ];
    }

    protected function providePlanBuilderStatusData(): iterable
    {
        $activeStatus = $this->createFakeStatus('Active');

        yield 'return_null_status_due_to_plan_builder_empty_settings' => [
            [],
            null,
        ];

        yield 'return_null_status_due_to_plan_builder_missing_statuses' => [
            [
                $this->createFakeStatus('Ready'),
                $this->createFakeStatus('Draft'),
            ],
            null,
        ];

        yield 'return_status' => [
            [
                $this->createFakeStatus('Ready'),
                $this->createFakeStatus('Draft'),
                $activeStatus,
            ],
            $activeStatus,
        ];
    }

    protected function createFakeCategory(string $categoryName): Category
    {
        return Category::fromApiResponse((object)[
            'id' => random_int(1, 99),
            'name' => $categoryName,
            'company_id' => random_int(1, 99),
            'order' => random_int(1, 99),
            'created_at' => '2023-01-01',
            'updated_at' => '2023-01-01',
        ]);
    }

    protected function createFakeStatus(string $statusName): Status
    {
        return Status::fromApiResponse((object)[
            'id' => random_int(1, 99),
            'name' => $statusName,
            'company_id' => random_int(1, 99),
            'order' => random_int(1, 99),
            'created_at' => '2023-01-01',
            'updated_at' => '2023-01-01',
        ]);
    }

    protected function setUpConfig(string $key, string $value): void
    {
        Config::expects('get')
            ->once()
            ->with($key)
            ->andReturn($value);
    }
}
