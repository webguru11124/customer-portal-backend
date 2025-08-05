<?php

declare(strict_types=1);

namespace App\DTO\PlanBuilder;

use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapOutputName(SnakeCaseMapper::class)]
final class Plan extends Data
{
    /**
     * @param int $id
     * @param int $extReferenceId
     * @param string $name
     * @param string $startOn
     * @param string $endOn
     * @param int $planServiceFrequencyId
     * @param int $planStatusId
     * @param bool $billMonthly
     * @param int|null $initialDiscount
     * @param int|null $recurringDiscount
     * @param string $createdAt
     * @param string $updatedAt
     * @param int $order
     * @param AreaPlanPricing[] $areaPlanPricings
     * @param int[] $planCategoryIds
     * @param AreaPlan|null $defaultAreaPlan
     * @param AreaPlan[] $areaPlans
     * @param int[] $agreementLengthIds
     */
    private function __construct(
        public int $id,
        public int $extReferenceId,
        public string $name,
        public string $startOn,
        public string $endOn,
        public int $planServiceFrequencyId,
        public int $planStatusId,
        public bool $billMonthly,
        public int|null $initialDiscount,
        public int|null $recurringDiscount,
        public string $createdAt,
        public string $updatedAt,
        public int $order,
        public array $areaPlanPricings,
        public array $planCategoryIds,
        public AreaPlan|null $defaultAreaPlan,
        public array $areaPlans,
        public array $agreementLengthIds,
    ) {
    }

    /**
     * @param object{
     *     id: int,
     *     ext_reference_id: int,
     *     name: string,
     *     start_on: string,
     *     end_on: string|null,
     *     plan_service_frequency_id: int,
     *     plan_status_id: int,
     *     bill_monthly: bool,
     *     initial_discount: int,
     *     recurring_discount: int,
     *     created_at: string,
     *     updated_at: string,
     *     order: int,
     *     area_plan_pricings: object{id: int, plan_pricing_level_id: int, area_plan_id: int, initial_min: float, initial_max: float, recurring_min: float, recurring_max: float, created_at: string, updated_at: string}[],
     *     plan_category_ids: int[],
     *     default_area_plan: object{id: int, area_id: int|null, plan_id: int, created_at: string|null, updated_at: string|null, can_sell_percentage_threshold: int|null, service_product_ids: int[], area_plan_pricings: object{id: int, plan_pricing_level_id: int, area_plan_id: int, initial_min: float, initial_max: float, recurring_min: float, recurring_max: float, created_at: string, updated_at: string}[], addons: object{id: int, area_plan_id: int, product_id: int, is_recurring: bool, initial_min: float, initial_max: float, recurring_min: float, recurring_max: float, created_at: string|null, updated_at: string|null}[]}|null,
     *     area_plans: object{id: int, area_id: int|null, plan_id: int, created_at: string|null, updated_at: string|null, can_sell_percentage_threshold: int|null, service_product_ids: int[], area_plan_pricings: object{id: int, plan_pricing_level_id: int, area_plan_id: int, initial_min: float, initial_max: float, recurring_min: float, recurring_max: float, created_at: string, updated_at: string}[], addons: object{id: int, area_plan_id: int, product_id: int, is_recurring: bool, initial_min: float, initial_max: float, recurring_min: float, recurring_max: float, created_at: string|null, updated_at: string|null}[]}[],
     *     agreement_length_ids: int[]
     * } $data
     *
     * @return self
     */
    public static function fromApiResponse(object $data): self
    {
        $areaPlansList = array_map(
            static fn (object $appr) => AreaPlan::fromApiResponse($appr),
            $data->area_plans ?? []
        );
        $areaPlansPricingsList = array_map(
            static fn (object $appr) => AreaPlanPricing::fromApiResponse($appr),
            isset($data->area_plan_pricings) ? (array) $data->area_plan_pricings : []
        );
        return new self(
            id: $data->id,
            extReferenceId: (int) $data->ext_reference_id,
            name: $data->name,
            startOn: (string) ($data->start_on ?? ''),
            endOn: (string) ($data->end_on ?? ''),
            planServiceFrequencyId: $data->plan_service_frequency_id,
            planStatusId: $data->plan_status_id,
            billMonthly: $data->bill_monthly,
            initialDiscount: $data->initial_discount ?? null,
            recurringDiscount: $data->recurring_discount ?? null,
            createdAt: (string) $data->created_at,
            updatedAt: (string) $data->updated_at,
            order: $data->order,
            areaPlanPricings: $areaPlansPricingsList,
            planCategoryIds: $data->plan_category_ids ?? [],
            defaultAreaPlan: isset($data->default_area_plan) ?
                AreaPlan::fromApiResponse($data->default_area_plan) : null,
            areaPlans: $areaPlansList,
            agreementLengthIds: $data->agreement_length_ids ?? [],
        );
    }
}
