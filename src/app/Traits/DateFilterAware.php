<?php

namespace App\Traits;

use Aptive\PestRoutesSDK\Filters\DateFilter;
use DateTimeInterface;

trait DateFilterAware
{
    public function getDateFilter(DateTimeInterface|null $bottomDate = null, DateTimeInterface|null $topDate = null): DateFilter|null
    {
        return match (true) {
            $bottomDate !== null && $topDate !== null => DateFilter::between($bottomDate, $topDate),
            $bottomDate !== null && $topDate === null => DateFilter::greaterThanOrEqualTo($bottomDate),
            $bottomDate === null && $topDate !== null => DateFilter::lessThanOrEqualTo($topDate),
            default => null
        };
    }
}
