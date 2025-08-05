<?php

declare(strict_types=1);

namespace App\DTO\PlanBuilder;

use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapOutputName(SnakeCaseMapper::class)]
final class AreaPlan extends Data
{
    /**
     * @param int $id
     * @param int|null $areaId
     * @param int $planId
     * @param string|null $createdAt
     * @param string|null $updatedAt
     * @param int|null $canSellPercentageThreshold
     * @param int[] $serviceProductIds
     * @param AreaPlanPricing[] $areaPlanPricings
     * @param Addon[] $addons
     */
    private function __construct(
        public int $id,
        public int|null $areaId,
        public int $planId,
        public string|null $createdAt,
        public string|null $updatedAt,
        public int|null $canSellPercentageThreshold,
        public array $serviceProductIds,
        public array $areaPlanPricings,
        public array $addons,
    ) {
    }

    /**
     * @param object{
     *     id: int,
     *     area_id: int|null,
     *     plan_id: int,
     *     created_at: string|null,
     *     updated_at: string|null,
     *     can_sell_percentage_threshold: int|null,
     *     service_product_ids: int[],
     *     area_plan_pricings: object{id: int, plan_pricing_level_id: int, area_plan_id: int, initial_min: float, initial_max: float, recurring_min: float, recurring_max: float, created_at: string, updated_at: string}[],
     *     addons: object{id: int, area_plan_id: int, product_id: int, is_recurring: bool, initial_min: float, initial_max: float, recurring_min: float, recurring_max: float, created_at: string|null, updated_at: string|null}[],
     * } $data
     * @return self
     */
    public static function fromApiResponse(object $data): self
    {
        $areaPlanPricingsList = array_map(
            static fn (object $appr) => AreaPlanPricing::fromApiResponse($appr),
            $data->area_plan_pricings ?? []
        );
        $addonsList = array_map(
            static fn (object $addon) => Addon::fromApiResponse($addon),
            isset($data->addons) ? (array) $data->addons : []
        );
        return new self(
            id: $data->id,
            areaId: $data->area_id,
            planId: $data->plan_id,
            createdAt: $data->created_at ?? null,
            updatedAt: $data->updated_at ?? null,
            canSellPercentageThreshold: $data->can_sell_percentage_threshold,
            serviceProductIds: $data->service_product_ids ?? [],
            areaPlanPricings: $areaPlanPricingsList,
            addons: $addonsList,
        );
    }
}
