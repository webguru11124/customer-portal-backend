<?php

declare(strict_types=1);

namespace App\Http\Responses\V2;

use App\DTO\PlanBuilder\Addon;
use App\DTO\PlanBuilder\Plan;
use App\Enums\Resources;
use App\Models\External\SubscriptionModel;
use Aptive\Component\JsonApi\CollectionDocument;
use Aptive\Component\JsonApi\Document;
use Aptive\Component\JsonApi\Exceptions\ValidationException;
use Aptive\Component\JsonApi\Objects\ResourceObject;
use Aptive\Illuminate\Http\JsonApi\QueryResponse;
use Illuminate\Http\Request;

class GetUpgradesResponse extends QueryResponse
{
    /**
     * @var array<string, mixed>
     */
    protected array $currentPlanData = [];

    /**
     * @param Request $request
     * @param array<string, mixed> $result
     *
     * @return Document
     *
     * @throws ValidationException
     */
    protected function toDocument(Request $request, mixed $result): Document
    {
        $collectionDocument  = new CollectionDocument();
        $agreementLength = sprintf('%d months', $result['subscription']->agreementLength);
        $collectionDocument->addResource($this->currentPlanToResource(
            $result['current'],
            $result['subscription'],
            $result['serviceFrequencies'][$result['current']->planServiceFrequencyId]->frequencyDisplay,
            $agreementLength,
            $result['purchasedAddons'],
        ));

        foreach ($result['upgrades'] as $upgrade) {
            $collectionDocument->addResource($this->planToResource(
                $upgrade,
                $result['subscription'],
                $result['serviceFrequencies'][$upgrade->planServiceFrequencyId]->frequencyDisplay,
                $agreementLength,
                $result['purchasedAddons'],
                $result['hasDiscountToPro'],
            ));
        }

        return $collectionDocument;
    }

    /**
     * @param Plan $plan
     * @param SubscriptionModel $subscription
     * @param string $frequency
     * @param string $agreementLength
     * @param int[] $purchasedAddons
     * @return ResourceObject
     * @throws ValidationException
     */
    private function currentPlanToResource(
        Plan $plan,
        SubscriptionModel $subscription,
        string $frequency,
        string $agreementLength,
        array $purchasedAddons = [],
    ): ResourceObject {
        return ResourceObject::make(Resources::PLAN->value, $plan->id, $this->getCurrentPlanData(
            $plan,
            $subscription,
            $frequency,
            $agreementLength,
            $purchasedAddons,
        ));
    }

    /**
     * @param Plan $plan
     * @param SubscriptionModel $subscription
     * @param string $frequency
     * @param string $agreementLength
     * @param int[] $purchasedAddons
     * @return array<string, mixed>
     * @throws ValidationException
     */
    private function getCurrentPlanData(
        Plan $plan,
        SubscriptionModel $subscription,
        string $frequency,
        string $agreementLength,
        array $purchasedAddons = [],
    ): array {
        if (empty($this->currentPlanData)) {
            $this->currentPlanData = [
                'plan_id' => $plan->id,
                'is_current_plan' => true,
                'name' => $plan->name,
                'frequency' => $frequency,
                'order' => $plan->order,
                'initial_price' => (float) $subscription->initialServiceTotal,
                'recurring_price' => (float) $subscription->recurringTicket?->serviceCharge,
                'agreement_length' => $agreementLength,
                'products' => isset($plan->defaultAreaPlan) ? $plan->defaultAreaPlan->serviceProductIds : [],
                'addons' => isset($plan->defaultAreaPlan) ?
                    $this->getCurrentPlanAddons($plan->defaultAreaPlan->addons, $purchasedAddons, $subscription) : [],
            ];
        }
        return $this->currentPlanData;
    }

    /**
     * @param Addon[] $addons
     * @param int[] $purchasedAddons
     * @return array<int, ResourceObject>
     * @throws ValidationException
     */
    private function getCurrentPlanAddons(
        array $addons,
        array $purchasedAddons,
        SubscriptionModel $subscription,
    ): array {
        $addonData = [];
        foreach ($addons as $addon) {
            $isPurchased = array_key_exists($addon->productId, $purchasedAddons);
            $addonData[] = ResourceObject::make(Resources::ADDON->value, $addon->id, [
                'addon_id' => $addon->id,
                'product_id' => $addon->productId,
                'initial_price' => $addon->initialMin,
                'recurring_price' => $isPurchased ? $this->getAddonRecurringPriceFromSubscription(
                    $purchasedAddons[$addon->productId],
                    $subscription,
                    $addon
                ) : $addon->recurringMin,
                'purchased' => $isPurchased,
            ]);
        }
        return $addonData;
    }

    /**
     * @param int $extReferenceId
     * @param SubscriptionModel $subscription
     * @param Addon $addon
     * @return float
     */
    private function getAddonRecurringPriceFromSubscription(
        int $extReferenceId,
        SubscriptionModel $subscription,
        Addon $addon,
    ): float {
        if ($subscription->recurringTicket) {
            $items = $subscription->recurringTicket->items;
            foreach ($items as $item) {
                if ($item->productId === $extReferenceId) {
                    return $item->amount;
                }
            }
        }

        return $addon->recurringMin;
    }

    /**
     * @param Plan $plan
     * @param SubscriptionModel $subscription
     * @param string $frequency
     * @param string $agreementLength
     * @param int[] $purchasedAddons
     * @return ResourceObject
     * @throws ValidationException
     */
    private function planToResource(
        Plan $plan,
        SubscriptionModel $subscription,
        string $frequency,
        string $agreementLength,
        array $purchasedAddons = [],
        bool $hasDiscountToPro = false,
    ): ResourceObject {
        return ResourceObject::make(Resources::PLAN->value, $plan->id, [
            'plan_id' => $plan->id,
            'is_current_plan' => false,
            'name' => $plan->name,
            'frequency' => $frequency,
            'order' => $plan->order,
            'initial_price' => $this->getInitialPrice($plan, $subscription, $hasDiscountToPro),
            'recurring_price' => $this->getRecurringPrice($plan, $subscription, $hasDiscountToPro),
            'agreement_length' => $agreementLength,
            'products' => isset($plan->defaultAreaPlan) ? $plan->defaultAreaPlan->serviceProductIds : [],
            'addons' => isset($plan->defaultAreaPlan) ?
                $this->getAddons($plan->defaultAreaPlan->addons, $purchasedAddons) : [],
        ]);
    }

    /**
     * @param Addon[] $addons
     * @param int[] $purchasedAddons
     * @return array<int, ResourceObject>
     * @throws ValidationException
     */
    private function getAddons(array $addons, array $purchasedAddons): array
    {
        $addonData = [];
        foreach ($addons as $addon) {
            $addonData[] = ResourceObject::make(Resources::ADDON->value, $addon->id, [
                'addon_id' => $addon->id,
                'product_id' => $addon->productId,
                'initial_price' => $addon->initialMin,
                'recurring_price' => $addon->recurringMin,
                'purchased' => in_array($addon->productId, $purchasedAddons),
            ]);
        }
        return $addonData;
    }

    /**
     * @param Plan $plan
     * @param SubscriptionModel $subscription
     * @return float
     */
    private function getInitialPrice(
        Plan $plan,
        SubscriptionModel $subscription,
        bool $hasDiscountToPro
    ): float {
        if ($hasDiscountToPro && $plan->name === 'Pro') {
            return (float) $subscription->initialServiceTotal - 10;
        }

        return max(
            (float) (!empty($plan->defaultAreaPlan?->areaPlanPricings) ?
                $plan->defaultAreaPlan->areaPlanPricings[0]->initialMin : 0),
            (float) $subscription->initialServiceTotal
        );
    }

    /**
     * @param Plan $plan
     * @param SubscriptionModel $subscription
     * @return float
     */
    private function getRecurringPrice(
        Plan $plan,
        SubscriptionModel $subscription,
        bool $hasDiscountToPro
    ): float {
        if ($hasDiscountToPro && $plan->name === 'Pro' && $subscription->recurringTicket) {
            return (float) ($subscription->recurringTicket->serviceCharge - 10);
        }

        return max(
            (float) (!empty($plan->defaultAreaPlan?->areaPlanPricings) ?
                $plan->defaultAreaPlan->areaPlanPricings[0]->recurringMin : 0),
            (float) $subscription->recurringTicket?->serviceCharge
        );
    }
}
