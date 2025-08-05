<?php

declare(strict_types=1);

namespace App\DTO\FlexIVR\Spot;

use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;

final class SearchSpots extends Data
{
    public function __construct(
        #[MapOutputName('officeID')]
        public int $officeId,
        #[MapOutputName('customerID')]
        public int $customerId,
        public float $lat,
        public float $lng,
        public string|null $state,
        public bool $isInitial = false,
        public string $executionSID = '',
        public string $cxp = 'true',
    ) {
    }
}
