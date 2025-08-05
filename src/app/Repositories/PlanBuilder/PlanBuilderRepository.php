<?php

declare(strict_types=1);

namespace App\Repositories\PlanBuilder;

use App\DTO\PlanBuilder\AgreementLength;
use App\DTO\PlanBuilder\AreaPlan;
use App\DTO\PlanBuilder\AreaPlanPricing;
use App\DTO\PlanBuilder\Category;
use App\DTO\PlanBuilder\Plan;
use App\DTO\PlanBuilder\PlanPricingLevel;
use App\DTO\PlanBuilder\PlanUpgradePaths;
use App\DTO\PlanBuilder\PlanServiceFrequency;
use App\DTO\PlanBuilder\Product;
use App\DTO\PlanBuilder\SearchPlansDTO;
use App\DTO\PlanBuilder\Status;
use App\DTO\PlanBuilder\TargetContractValue;
use GuzzleHttp\Exception\GuzzleException;
use JsonException;

class PlanBuilderRepository extends BaseRepository
{
    /**
     * @return Plan[]
     * @throws GuzzleException
     * @throws JsonException
     */
    public function getPlans(): array
    {
        /**
         * @var object{
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
         * }[] $responseData
         */
        $responseData = $this->sendGetRequest('plans');

        return array_map(static fn (object $plan) => Plan::fromApiResponse($plan), $responseData);
    }

    /**
     * @param SearchPlansDTO $dto
     * @return Plan[]
     * @throws GuzzleException
     * @throws JsonException
     */
    public function searchPlans(SearchPlansDTO $dto): array
    {
        /**
         * @var object{
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
         * }[] $responseData
         */
        $responseData = $this->sendGetRequest('plans/filter', $dto->toArray());

        return array_map(static fn (object $plan) => Plan::fromApiResponse($plan), $responseData);
    }

    /**
     * @param SearchPlansDTO $dto
     * @return array<string, mixed>
     * @throws GuzzleException
     * @throws JsonException
     */

    public function searchPlansWithProducts(SearchPlansDTO $dto): array
    {
        $responseData = $this->sendGetRequest('plans_with_products/filter', $dto->toArray());

        /**
         * @var object{
         *     plans: object{
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
         * }[],
         *     products: object{
         *     id: int,
         *     product_sub_category_id: int,
         *     ext_reference_id: string,
         *     name: string,
         *     order: int,
         *     image: string,
         *     is_recurring: bool,
         *     initial_min: float,
         *     initial_max: float,
         *     recurring_min: float,
         *     recurring_max: float,
         *     created_at: string,
         *     updated_at: string,
         *     needs_customer_support: bool,
         *     description: string|null,
         *     image_name: string}[]
         * } $responseData
         */
        return [
            'plans' => array_map(static fn (object $plan) => Plan::fromApiResponse($plan), $responseData->plans),
            'products' => array_map(
                static fn (object $product) => Product::fromApiResponse($product),
                $responseData->products
            ),
        ];
    }


    /**
     * @param int $planId
     * @return Plan
     * @throws GuzzleException
     * @throws JsonException
     */
    public function getPlan(int $planId): Plan
    {
        $responseData = $this->sendGetRequest(sprintf('plans/%d', $planId));
        /**
         * @var object{
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
         * } $responseData
         */
        return Plan::fromApiResponse($responseData);
    }

    /**
     * @param int $planId
     * @return array<string, mixed>
     * @throws GuzzleException
     * @throws JsonException
     */
    public function getPlanWithProducts(int $planId): array
    {
        $responseData = $this->sendGetRequest(sprintf('plans_with_products/%d', $planId));
        /**
         * @var object{
         *     plan: object{
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
         *     agreement_length_ids: int[]},
         *     products: object{
         *     id: int,
         *     product_sub_category_id: int,
         *     ext_reference_id: string,
         *     name: string,
         *     order: int,
         *     image: string,
         *     is_recurring: bool,
         *     initial_min: float,
         *     initial_max: float,
         *     recurring_min: float,
         *     recurring_max: float,
         *     created_at: string,
         *     updated_at: string,
         *     needs_customer_support: bool,
         *     description: string|null,
         *     image_name: string}[]
         * } $responseData
         */
        return [
            'plan' => Plan::fromApiResponse($responseData->plan),
            'products' =>
                array_map(static fn (object $product) => Product::fromApiResponse($product), $responseData->products),
        ];
    }

    /**
     * @return Category[]|Category
     * @throws GuzzleException
     * @throws JsonException
     */
    public function getPlanCategories(): array|object
    {
        $responseData = $this->sendGetRequest('plan_categories');
        /**
         * @var object{
         *     id: int,
         *     name: string,
         *     order: int,
         *     created_at: string,
         *     updated_at: string
         * }[] $responseData
         */
        return array_map(static fn (object $category) => Category::fromApiResponse($category), $responseData);
    }

    /**
     * @return AreaPlan[]|AreaPlan
     * @throws GuzzleException
     * @throws JsonException
     */
    public function getAreaPlans(): array|object
    {
        $responseData = $this->sendGetRequest('area_plans');
        /**
         * @var object{
         *     id: int,
         *     area_id: int|null,
         *     plan_id: int,
         *     created_at: string|null,
         *     updated_at: string|null,
         *     can_sell_percentage_threshold: int|null,
         *     service_product_ids: int[],
         *     area_plan_pricings: object{id: int, plan_pricing_level_id: int, area_plan_id: int, initial_min: float, initial_max: float, recurring_min: float, recurring_max: float, created_at: string, updated_at: string}[],
         *     addons: object{id: int, area_plan_id: int, product_id: int, is_recurring: bool, initial_min: float, initial_max: float, recurring_min: float, recurring_max: float, created_at: string|null, updated_at: string|null}[],
         * }[] $responseData
         */
        return array_map(static fn (object $areaPlan) => AreaPlan::fromApiResponse($areaPlan), $responseData);
    }

    /**
     * @return TargetContractValue[]
     * @throws GuzzleException
     * @throws JsonException
     */
    public function getTargetContractValues(): array
    {
        $responseData = $this->sendGetRequest('target_contract_values');
        /**
         * @var object{
         *     id: int,
         *     area_id: int,
         *     value: float,
         *     created_at: string,
         *     updated_at: string,
         * }[] $responseData
         */
        return array_map(static fn (object $value) => TargetContractValue::fromApiResponse($value), $responseData);
    }

    /**
     * @return PlanPricingLevel[]
     * @throws GuzzleException
     * @throws JsonException
     */
    public function getPlanPricingLevels(): array
    {
        $responseData = $this->sendGetRequest('plan_pricing_levels');
        /**
         * @var object{
         *     id: int,
         *     name: string,
         *     order: int,
         *     created_at: string,
         *     updated_at: string
         * }[] $responseData
         */
        return array_map(static fn (object $level) => PlanPricingLevel::fromApiResponse($level), $responseData);
    }

    /**
     * @return PlanUpgradePaths[]
     * @throws GuzzleException
     * @throws JsonException
     */
    public function getPlanUpgradePaths(): array
    {
        $responseData = $this->sendGetRequest('plan_upgrade_paths');
        /**
         * @var object{
         *     id: int,
         *     upgrade_from_plan_id: int,
         *     upgrade_to_plan_id: int,
         *     price_discount: int,
         *     use_to_plan_price: bool,
         *     created_at: string,
         *     updated_at: string
         * }[] $responseData
         */
        return array_map(static fn (object $path) => PlanUpgradePaths::fromApiResponse($path), $responseData);
    }

    /**
     * @return AreaPlanPricing[]
     * @throws GuzzleException
     * @throws JsonException
     */
    public function getAreaPlanPricings(): array
    {
        $responseData = $this->sendGetRequest('area_plan_pricings');
        /**
         * @var object{
         *     id: int,
         *     plan_pricing_level_id: int,
         *     area_plan_id: int,
         *     initial_min: float,
         *     initial_max: float,
         *     recurring_min: float,
         *     recurring_max: float,
         *     created_at: string,
         *     updated_at: string,
         * }[] $responseData
         */
        return array_map(static fn (object $pricing) => AreaPlanPricing::fromApiResponse($pricing), $responseData);
    }


    /**
     * @return array<string, array<string, mixed>>
     * @throws GuzzleException
     * @throws JsonException
     */
    public function getSettings(): array
    {
        $responseData = $this->sendGetRequest('settings');

        /**
         * @var object{
         *     plan_pricing_levels: object{id: int, name: string, order: int, created_at: string, updated_at: string}[],
         *     plan_service_frequencies: object{id: int, frequency: int, order: int, created_at: string|null, updated_at: string|null, frequency_display: string}[],
         *     plan_categories: object{id: int, name: string, order: int, created_at: string, updated_at: string}[],
         *     plan_statuses: object{id: int, name: string, order: int, created_at: string, updated_at: string}[],
         *     agreement_lengths: object{id: int, name: string, length: int, unit: string, order: int, created_at: string, updated_at: string}[]
         * } $responseData
         */
        return [
            'planPricingLevels' => array_map(
                static fn (object $ppl) => PlanPricingLevel::fromApiResponse($ppl),
                $responseData->plan_pricing_levels
            ),
            'planServiceFrequencies' => array_map(
                static fn (object $psf) => PlanServiceFrequency::fromApiResponse($psf),
                $responseData->plan_service_frequencies
            ),
            'planCategories' => array_map(
                static fn (object $category) => Category::fromApiResponse($category),
                $responseData->plan_categories
            ),
            'planStatuses' => array_map(
                static fn (object $status) => Status::fromApiResponse($status),
                $responseData->plan_statuses
            ),
            'agreementLengths' => array_map(
                static fn (object $status) => AgreementLength::fromApiResponse($status),
                $responseData->agreement_lengths
            ),
        ];
    }
}
