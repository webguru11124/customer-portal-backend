<?php

declare(strict_types=1);

namespace App\DTO\PlanBuilder;

use App\DTO\BaseDTO;
use Spatie\LaravelData\Attributes\MapOutputName;

final class SearchPlansDTO extends BaseDTO
{
    /**
     * @param int|null $areaId
     * @param int|null $planPricingLevelId
     * @param int|null $planStatusId
     * @param int|null $planCategoryId
     * @param string|null $extReferenceId
     * @param int|null $officeId
     */
    public function __construct(
        #[MapOutputName('area_id')]
        public readonly int|null $areaId = null,
        #[MapOutputName('plan_pricing_level_id')]
        public readonly int|null $planPricingLevelId = null,
        #[MapOutputName('plan_status_id')]
        public readonly int|null $planStatusId = null,
        #[MapOutputName('plan_category_id')]
        public readonly int|null $planCategoryId = null,
        #[MapOutputName('ext_reference_id')]
        public readonly string|null $extReferenceId = null,
        #[MapOutputName('office_id')]
        public readonly int|null $officeId = null,
    ) {
    }
}
