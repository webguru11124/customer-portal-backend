<?php

declare(strict_types=1);

namespace App\DTO\PlanBuilder;

use App\DTO\BaseDTO;
use DateTimeInterface;
use Spatie\LaravelData\Attributes\MapOutputName;

final class CurrentPlanDTO extends BaseDTO
{
    /**
     * @param string $name
     * @param string[] $includedProducts
     * @param DateTimeInterface $subscriptionStart
     * @param DateTimeInterface $subscriptionEnd
     */
    public function __construct(
        #[MapOutputName('name')]
        public readonly string $name,
        #[MapOutputName('included_products')]
        public readonly array $includedProducts,
        #[MapOutputName('subscription_start')]
        public readonly DateTimeInterface $subscriptionStart,
        #[MapOutputName('subscription_end')]
        public readonly DateTimeInterface $subscriptionEnd,
        #[MapOutputName('error')]
        public readonly bool $error = false,
    ) {
    }
}
