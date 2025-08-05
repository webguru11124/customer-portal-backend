<?php

declare(strict_types=1);

namespace App\Helpers;

use Carbon\Carbon;
use DateTimeInterface;
use DateTimeZone;

class DateTimeHelper
{
    public const AM = 'AM';
    public const PM = 'PM';

    public static function defaultDateFormat(): string
    {
        return config('aptive.default_date_format');
    }

    /**
     * Transforms string date of given format to Carbon object.
     * Default format: Y-m-d.
     */
    public static function dateToCarbon(
        string $date,
        string|DateTimeZone|null $tz = null,
        string $fromFormat = null
    ): Carbon {
        $fromFormat ??= self::defaultDateFormat();
        $carbonDate = Carbon::createFromFormat($fromFormat, $date, $tz);

        if ($carbonDate === false) {
            throw new \InvalidArgumentException(sprintf(
                'Date "%s" or date format "%s" are invalid.',
                $date,
                $fromFormat
            ));
        }

        return $carbonDate;
    }

    /**
     * Transforms Carbon object to string date of given format.
     * Default format: Y-m-d.
     */
    public static function carbonToDate(Carbon $carbonDate, string $toFormat = null): string
    {
        return $carbonDate->format($toFormat ?? self::defaultDateFormat(), $carbonDate);
    }

    /**
     * Transforms DateTime object to string date of a given format.
     * Default format: Y-m-d.
     */
    public static function dateTimeToDate(DateTimeInterface $dateTime, string|null $toFormat = null): string
    {
        return $dateTime->format($toFormat ?? self::defaultDateFormat());
    }

    /**
     * Returns current date string of given format
     * Default format: Y-m-d.
     */
    public static function today(string $format = null): string
    {
        return self::carbonToDate(Carbon::now(), $format);
    }

    /**
     * Returns a day before current shifted on given days interval. It returns string of given format.
     * Default format: Y-m-d.
     */
    public static function dayBefore(int $daysInterval, string $format = null): string
    {
        return self::carbonToDate(Carbon::now()->subDays($daysInterval), $format);
    }

    /**
     * Returns a day after current shifted on given days interval. It returns string of given format.
     * Default format: Y-m-d.
     */
    public static function dayAfter(int $daysInterval, string $format = null): string
    {
        return self::carbonToDate(Carbon::now()->addDays($daysInterval), $format);
    }

    public static function isFutureDate(DateTimeInterface $date): bool
    {
        return Carbon::now() < $date;
    }

    /**
     * Check if presented date is a today/future date. All time is skipped, only date comparison.
     * Default format: Y-m-d.
     */
    public static function isTodayOrFutureDate(DateTimeInterface $date): bool
    {
        $currentDate = self::carbonToDate(Carbon::now(), self::defaultDateFormat());
        $presentedDate = self::dateTimeToDate($date, self::defaultDateFormat());

        return $currentDate <= $presentedDate;
    }

    public static function isToday(DateTimeInterface $date): bool
    {
        $dayStart = Carbon::now()->startOfDay();
        $dayEnd = Carbon::now()->endOfDay();

        return $dayStart <= $date && $date <= $dayEnd;
    }

    public static function isAmTime(DateTimeInterface $dateTime): bool
    {
        return $dateTime->format('A') === self::AM;
    }

    /**
     * @return string[]
     */
    public static function getAmTimeRange(): array
    {
        return ['08:00:00', '13:00:00'];
    }

    /**
     * @return string[]
     */
    public static function getPmTimeRange(): array
    {
        return ['13:00:00', '20:00:00'];
    }
}
