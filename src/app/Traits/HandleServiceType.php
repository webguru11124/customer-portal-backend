<?php

declare(strict_types=1);

namespace App\Traits;

trait HandleServiceType
{
    protected function handleServiceTypeDescription(string $description): string
    {
        return str_replace('Quarterly Service', 'Standard Service', $description);
    }
}
