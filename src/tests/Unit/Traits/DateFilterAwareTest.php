<?php

namespace Tests\Unit\Traits;

use App\Traits\DateFilterAware;
use Aptive\PestRoutesSDK\Filters\DateFilter;
use Carbon\Carbon;
use Tests\TestCase;

class DateFilterAwareTest extends TestCase
{
    public $traitMock;

    public function setUp(): void
    {
        parent::setUp();

        $this->traitMock = $this->getMockForTrait(DateFilterAware::class);
    }

    /**
     * @dataProvider dateFilterDataProvider
     */
    public function test_it_creates_date_filter(Carbon $bottomDate = null, Carbon $topDate = null, mixed $expectedResult = null)
    {
        $result = $this->traitMock->getDateFilter($bottomDate, $topDate);

        self::assertEquals($expectedResult, $result);
    }

    public function dateFilterDataProvider()
    {
        $bottomDate = Carbon::createFromFormat('Y-m-d', '2022-01-01');
        $topDate = Carbon::createFromFormat('Y-m-d', '2022-02-01');

        return [
            [null, null, null],
            [$bottomDate, null, DateFilter::greaterThanOrEqualTo($bottomDate)],
            [null, $topDate, DateFilter::lessThanOrEqualTo($topDate)],
            [$bottomDate, $topDate, DateFilter::between($bottomDate, $topDate)],
        ];
    }
}
