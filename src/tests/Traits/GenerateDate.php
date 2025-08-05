<?php

namespace Tests\Traits;

use App\Helpers\DateTimeHelper;
use Carbon\Carbon;

trait GenerateDate
{
    protected function generatePastDate(): string
    {
        return Carbon::now()->subDays(random_int(1, 10))->format(DateTimeHelper::defaultDateFormat());
    }

    protected function generateFutureDate(): string
    {
        return Carbon::now()->addDays(random_int(1, 10))->format(DateTimeHelper::defaultDateFormat());
    }
}
