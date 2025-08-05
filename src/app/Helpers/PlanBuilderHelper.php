<?php

declare(strict_types=1);

namespace App\Helpers;

use App\DTO\PlanBuilder\Category;
use App\DTO\PlanBuilder\Status;
use App\Exceptions\PlanBuilder\CannotFetchPlanBuilderDataException;
use App\Repositories\PlanBuilder\PlanBuilderRepository;

class PlanBuilderHelper
{
    public function __construct(
        private readonly PlanBuilderRepository $planBuilderRepository
    ) {
    }

    /**
     * @throws CannotFetchPlanBuilderDataException
     */
    public function getPlanBuilderCustomerPortalCategory(): Category|null
    {
        try {
            /** @var array<int, Category> $categories */
            $categories = $this->planBuilderRepository->getPlanCategories();
        } catch (\Throwable $exception) {
            throw new CannotFetchPlanBuilderDataException($exception->getMessage());
        }

        $categoryName = ConfigHelper::getPlanBuilderCategoryName();
        $customerPortalCategory = current(array_filter(
            $categories,
            static fn (Category $category) => $category->name === $categoryName
        ));

        return $customerPortalCategory ?: null;
    }

    /**
     * @throws CannotFetchPlanBuilderDataException
     */
    public function getPlanBuilderActiveStatus(): Status|null
    {
        try {
            /** @var array<string, array<string, mixed>> $settings */
            $settings = $this->planBuilderRepository->getSettings();
        } catch (\Throwable $exception) {
            throw new CannotFetchPlanBuilderDataException($exception->getMessage());
        }

        $statuses = $settings['planStatuses'];
        $activeStatusName = ConfigHelper::getPlanBuilderActiveStatusName();

        if (0 === count($statuses)) {
            return null;
        }

        $activeStatus = current(array_filter(
            $statuses,
            static fn (Status $status) => $status->name === $activeStatusName
        ));

        return $activeStatus ?: null;
    }
}
