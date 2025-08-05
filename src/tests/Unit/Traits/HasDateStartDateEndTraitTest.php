<?php

namespace Tests\Unit\Traits;

use App\Traits\HasDateStartDateEnd;
use Carbon\Carbon;
use Tests\TestCase;

class HasDateStartDateEndTraitTest extends TestCase
{
    private const DATE_START = '2022-07-10';
    private const DATE_END = '2022-08-01';

    private const DATE_START_TIME = self::DATE_START . ' 00:00:00';
    private const DATE_END_TIME = self::DATE_END . ' 23:59:59';

    private const TIME_FORMAT = 'Y-m-d H:i:s';

    private function getSubjectClass(string|null $dateStart, string|null $dateEnd): object
    {
        return new class ($dateStart, $dateEnd) {
            use HasDateStartDateEnd;

            public function __construct(
                public readonly ?string $dateStart,
                public readonly ?string $dateEnd
            ) {
            }
        };
    }

    public function test_it_returns_start_end_date()
    {
        $subject = $this->getSubjectClass(self::DATE_START, self::DATE_END);

        $carbonDateStart = $subject->getCarbonDateStart();
        $carbonDateEnd = $subject->getCarbonDateEnd();

        self::assertInstanceOf(Carbon::class, $carbonDateStart);
        self::assertInstanceOf(Carbon::class, $carbonDateEnd);
        self::assertEquals(self::DATE_START_TIME, $carbonDateStart->format(self::TIME_FORMAT));
        self::assertEquals(self::DATE_END_TIME, $carbonDateEnd->format(self::TIME_FORMAT));
    }

    public function test_it_returns_null_for_unset_dates()
    {
        $subject = $this->getSubjectClass(null, null);

        $this->assertNull($subject->getCarbonDateStart());
        $this->assertNull($subject->getCarbonDateEnd());
    }
}
