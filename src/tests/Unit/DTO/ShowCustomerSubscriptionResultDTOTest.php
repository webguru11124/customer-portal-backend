<?php

declare(strict_types=1);

namespace Tests\Unit\DTO;

use App\DTO\Customer\ShowCustomerSubscriptionResultDTO;
use App\DTO\PlanBuilder\Product;
use App\Helpers\DateTimeHelper;
use App\Models\External\ServiceTypeModel;
use App\Models\External\SubscriptionModel;
use App\Services\SubscriptionUpgradeService;
use Illuminate\Support\Facades\Config;
use Tests\Data\ServiceTypeData;
use Tests\Data\SubscriptionData;
use Tests\Data\TicketData;
use Tests\Data\TicketTemplateAddonData;
use Tests\TestCase;
use Tests\Traits\RandomIntTestData;

final class ShowCustomerSubscriptionResultDTOTest extends TestCase
{
    use RandomIntTestData;

    private const PRODUCT_ID = 1620;

    public function test_it_show_customer_subscription_result_properties(): void
    {
        Config::set('aptive.subscription.addons_exceptions.allowed_categories', 'Specialty Pest');

        $recurringTicket = TicketData::getRawTestData(1, [
            'items' => [(object)TicketTemplateAddonData::getRawTestData(1, ['productID' => self::PRODUCT_ID])->first()],
        ])->first();
        /** @var SubscriptionModel $subscription */
        $subscription = SubscriptionData::getTestEntityData(1, [
            'recurringTicket' => (object) $recurringTicket,
        ])->first();
        /** @var ServiceTypeModel $subscriptionServiceType */
        $subscriptionServiceType = ServiceTypeData::getTestEntityDataOfTypes(ServiceTypeData::PREMIUM)->first();
        $subscription->setRelated('serviceType', $subscriptionServiceType);

        $subscriptionUpgradeService = \Mockery::mock(SubscriptionUpgradeService::class);
        $subscriptionUpgradeService
            ->shouldReceive('isUpgradeAvailable')
            ->withArgs([$subscription])
            ->once()
            ->andReturn(true);

        $planBuilderProducts = $this->setupPlanBuilderProducts(self::PRODUCT_ID, 'Rodent');
        $subscriptionUpgradeService
            ->shouldReceive('getPlanBuilderPlanSpecialtyPestsProducts')
            ->withArgs([$subscription])
            ->once()
            ->andReturn($planBuilderProducts);

        $showCustomerSubscriptionResultDTO = new ShowCustomerSubscriptionResultDTO(
            subscription: $subscription,
            subscriptionUpgradeService: $subscriptionUpgradeService
        );

        $this->assertEquals($subscription->id, $showCustomerSubscriptionResultDTO->id);
        $this->assertEquals($subscriptionServiceType->description, $showCustomerSubscriptionResultDTO->serviceType);
        $this->assertEquals($subscription->recurringCharge, $showCustomerSubscriptionResultDTO->pricePerTreatment);
        $this->assertEquals(
            $subscription->contractAdded?->format(DateTimeHelper::defaultDateFormat()),
            $showCustomerSubscriptionResultDTO->agreementDate
        );
        $this->assertEquals($subscription->agreementLength, $showCustomerSubscriptionResultDTO->agreementLength);
        $this->assertEquals(
            current(array_map(
                static fn (Product $planBuilderProduct) => [$planBuilderProduct->extReferenceId => $planBuilderProduct->name],
                $planBuilderProducts
            )),
            $showCustomerSubscriptionResultDTO->specialtyPests
        );
        $this->assertTrue($showCustomerSubscriptionResultDTO->isUpgradeAvailable);
    }

    /**
     * @param int $extReferenceId
     * @param string $productName
     * @param int $qty
     *
     * @return array<int, Product>
     */
    protected function setupPlanBuilderProducts(
        int $extReferenceId,
        string $productName,
        int $qty = 1
    ): array {
        $products = [];
        for ($i = 1; $i <= $qty; $i++) {
            $products[] = Product::fromApiResponse((object) [
                'id' => $extReferenceId,
                'ext_reference_id' => $extReferenceId,
                'product_sub_category_id' => random_int(1, PHP_INT_MAX),
                'order' => random_int(1, PHP_INT_MAX),
                'name' => $productName,
                'image' => '',
                'is_recurring' => true,
                'initial_min' => 0.00,
                'initial_max' => 0.00,
                'recurring_min' => 0.00,
                'recurring_max' => 0.00,
                'company_id' => random_int(1, PHP_INT_MAX),
                'created_at' => (new \DateTime())->format(DateTimeHelper::defaultDateFormat()),
                'updated_at' => (new \DateTime())->format(DateTimeHelper::defaultDateFormat()),
                'needs_customer_support' => false,
                'description' => '',
                'image_name' => '',
            ]);
        }

        return $products;
    }
}
