<?php

namespace Tests\Unit\Helpers;

use App\Helpers\DateTimeHelper;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Support\Facades\Config;
use InvalidArgumentException;
use Tests\TestCase;

class DateTimeHelperTest extends TestCase
{
    private const VALID_DATE = '2022-02-24';
    private const DEFAULT_DATE_FORMAT = 'Y-m-d';
    private const DEFAULT_DATE_TIME_FORMAT = 'Y-m-d H:i:s';
    private const DIFFERENT_VALID_DATE = '02/24/2022';
    private const DIFFERENT_DATE_FORMAT = 'm/d/Y';
    private const DAYS_SHIFT = 5;
    private const UTC = 'UTC';

    public Carbon $carbonDate;

    public DateTimeInterface $dateTime;

    public function setUp(): void
    {
        parent::setUp();

        $this->carbonDate = Carbon::createFromFormat(self::DEFAULT_DATE_FORMAT, self::VALID_DATE);
        $this->dateTime = \DateTimeImmutable::createFromFormat(self::DEFAULT_DATE_FORMAT, self::VALID_DATE);
    }

    public function test_it_takes_default_date_format_from_config(): void
    {
        $oldDateFormat = Config::get('aptive.default_date_format');
        Config::set('aptive.default_date_format', self::DIFFERENT_DATE_FORMAT);

        self::assertEquals(self::DIFFERENT_DATE_FORMAT, DateTimeHelper::defaultDateFormat());
        self::assertNotEquals($oldDateFormat, DateTimeHelper::defaultDateFormat());
    }

    /**
     * @dataProvider dateAndFormatDataProvider
     */
    public function test_it_converts_string_to_carbon(string $date, ?string $format): void
    {
        self::assertEquals($this->carbonDate, DateTimeHelper::dateToCarbon($date, null, $format));
    }

    /**
     * @dataProvider timeZoneDataProvider
     */
    public function test_string_to_carbon_converts_timezone(string $tz, int $timeOffset): void
    {
        $time = '2023-01-01 00:00:00';
        $UTCTime = Carbon::parse($time, self::UTC);
        $localTime = DateTimeHelper::dateToCarbon($UTCTime, $tz, self::DEFAULT_DATE_TIME_FORMAT);

        self::assertEquals($UTCTime->diffInHours($localTime), abs($timeOffset));
    }

    public function timeZoneDataProvider(): iterable
    {
        yield ['PST', -8];
        yield ['CST', -6];
        yield ['EST', -5];
    }

    public function test_date_to_carbon_throws_exception_when_conversion_fails(): void
    {
        Carbon::useStrictMode(false);
        $this->expectException(InvalidArgumentException::class);
        DateTimeHelper::dateToCarbon('Alice', 'Bob');
    }

    /**
     * @dataProvider dateAndFormatDataProvider
     */
    public function test_it_converts_carbon_to_string(string $assertedDate, ?string $format = null)
    {
        self::assertEquals($assertedDate, DateTimeHelper::carbonToDate($this->carbonDate, $format));
    }

    /**
     * @dataProvider dateAndFormatDataProvider
     */
    public function test_it_converts_datetime_to_string(string $assertedDate, string|null $format = null): void
    {
        self::assertEquals($assertedDate, DateTimeHelper::dateTimeToDate($this->dateTime, $format));
    }

    public function dateAndFormatDataProvider(): array
    {
        return [
            [self::VALID_DATE, null],
            [self::VALID_DATE, self::DEFAULT_DATE_FORMAT],
            [self::DIFFERENT_VALID_DATE, self::DIFFERENT_DATE_FORMAT],
        ];
    }

    /**
     * @dataProvider formatsDataProvider
     */
    public function test_it_returns_today_date(string $format = null)
    {
        $expectedFormat = $format ?? self::DEFAULT_DATE_FORMAT;
        $expectedDate = date($expectedFormat, time());

        $result = DateTimeHelper::today($format);

        self::assertEquals($expectedDate, $result);
    }

    /**
     * @dataProvider formatsDataProvider
     */
    public function test_it_returns_day_before(string $format = null)
    {
        $expectedFormat = $format ?? self::DEFAULT_DATE_FORMAT;
        $expectedDate = date($expectedFormat, strtotime(self::DAYS_SHIFT . ' days ago'));

        $result = DateTimeHelper::dayBefore(self::DAYS_SHIFT, $format);

        self::assertEquals($expectedDate, $result);
    }

    /**
     * @dataProvider formatsDataProvider
     */
    public function test_it_returns_day_after(string $format = null)
    {
        $expectedFormat = $format ?? self::DEFAULT_DATE_FORMAT;
        $expectedDate = date($expectedFormat, strtotime('+' . self::DAYS_SHIFT . ' days'));

        $result = DateTimeHelper::dayAfter(self::DAYS_SHIFT, $format);

        self::assertEquals($expectedDate, $result);
    }

    public function formatsDataProvider()
    {
        return [
            [null],
            [self::DEFAULT_DATE_FORMAT],
            [self::DIFFERENT_DATE_FORMAT],
        ];
    }

    /**
     * @dataProvider isFutureDateDataProvider
     */
    public function test_is_future_date_returns_correct_value(DateTimeInterface $dateTime, bool $expectedResult): void
    {
        self::assertEquals($expectedResult, DateTimeHelper::isFutureDate($dateTime));
    }

    public function isFutureDateDataProvider(): iterable
    {
        yield 'yesterday' => [Carbon::now()->subDay(), false];
        yield 'today' => [Carbon::now(), false];
        yield 'tomorrow' => [Carbon::now()->addDay(), true];
    }

    /**
     * @dataProvider isTodayOrFutureDateDataProvider
     */
    public function test_is_today_or_future_date_returns_correct_value(
        DateTimeInterface $dateTime,
        bool $expectedResult
    ): void {
        self::assertEquals($expectedResult, DateTimeHelper::isTodayOrFutureDate($dateTime));
    }

    private function isTodayOrFutureDateDataProvider(): iterable
    {
        yield 'yesterday' => [Carbon::now()->subDay(), false];
        yield 'today' => [Carbon::now(), true];
        yield 'tomorrow' => [Carbon::now()->addDay(), true];
    }
}
