<?php

declare(strict_types=1);

namespace App\Traits;

use App\Helpers\DateTimeHelper;
use Carbon\Carbon;
use DateTimeZone;

trait HasDateStartDateEnd
{
    public readonly string|null $dateStart;
    public readonly string|null $dateEnd;

    public function getCarbonDateStart(string|DateTimeZone|null $tz = null): Carbon|null
    {
        return $this->dateStart ? DateTimeHelper::dateToCarbon($this->dateStart, $tz)->startOfDay() : null;
    }

    public function getCarbonDateEnd(string|DateTimeZone|null $tz = null): Carbon|null
    {
        return $this->dateEnd ? DateTimeHelper::dateToCarbon($this->dateEnd, $tz)->endOfDay() : null;
    }
}
