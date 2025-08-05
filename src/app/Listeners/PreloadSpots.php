<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Helpers\DateTimeHelper;
use App\Interfaces\AccountNumberAware;
use App\Utilites\ShellExecutor;
use Carbon\Carbon;

class PreloadSpots
{
    private const PAGES_AMOUNT = 4;
    private const PAGE_SIZE = 4;

    public function __construct(
        private ShellExecutor $shellExecutor
    ) {
    }

    public function handle(AccountNumberAware $event): void
    {
        for ($page = 1; $page <= self::PAGES_AMOUNT; $page++) {
            $accountNumber = $event->getAccountNumber();
            $startDate = $this->getStartDate($page);
            $endDate = $this->getEndDate($page);

            $command = sprintf(
                'php %s preload:spots %d %s %s > /dev/null &',
                base_path('artisan'),
                $accountNumber,
                $startDate,
                $endDate
            );

            $this->shellExecutor->run($command);
        }
    }

    private function getStartDate(int $page): string
    {
        return Carbon::now()
            ->addDays($this->getStartDayOffset($page))
            ->format(DateTimeHelper::defaultDateFormat());
    }

    private function getEndDate(int $page): string
    {
        return Carbon::now()
            ->addDays($this->getEndDayOffset($page))
            ->format(DateTimeHelper::defaultDateFormat());
    }

    private function getStartDayOffset(int $page): int
    {
        return ($page - 1) * self::PAGE_SIZE + 1;
    }

    private function getEndDayOffset(int $page): int
    {
        return $this->getStartDayOffset($page) + self::PAGE_SIZE - 1;
    }
}
