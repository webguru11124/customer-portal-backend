<?php

namespace Tests\Unit\Listeners;

use App\Events\Appointment\AppointmentCanceled;
use App\Events\Appointment\AppointmentScheduled;
use App\Events\CustomerSessionStarted;
use App\Interfaces\AccountNumberAware;
use App\Listeners\PreloadSpots;
use App\Utilites\ShellExecutor;
use Carbon\Carbon;
use Illuminate\Support\Facades\Event;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Traits\RandomIntTestData;

class PreloadSpotsTest extends TestCase
{
    use RandomIntTestData;

    private const PAGES_DATES_OFFSETS = [
        1 => [1, 4],
        2 => [5, 8],
        3 => [9, 12],
        4 => [13, 16],
    ];
    private const PAGES_AMOUNT = 4;

    protected MockInterface|ShellExecutor $shellExecutorMock;

    protected AccountNumberAware $event;
    protected PreloadSpots $subject;

    public function setUp(): void
    {
        parent::setUp();

        $this->shellExecutorMock = Mockery::mock(ShellExecutor::class);

        $this->event = new CustomerSessionStarted($this->getTestAccountNumber());
        $this->subject = new PreloadSpots($this->shellExecutorMock);
    }

    public function test_it_listens_events(): void
    {
        Event::fake();

        Event::assertListening(AppointmentCanceled::class, $this->subject::class);
        Event::assertListening(AppointmentScheduled::class, $this->subject::class);
        Event::assertListening(CustomerSessionStarted::class, $this->subject::class);
    }

    public function test_it_triggers_preload_command_for_every_page(): void
    {
        for ($page = 1; $page <= self::PAGES_AMOUNT; $page++) {
            $this->shellExecutorMock
                ->shouldReceive('run')
                ->with($this->buildCommand($this->event->accountNumber, $page))
                ->once()
                ->andReturn(null);
        }

        $this->subject->handle($this->event);
    }

    private function buildCommand(int $accountNumber, int $page): string
    {
        $startDayOffset = self::PAGES_DATES_OFFSETS[$page][0];
        $endDayOffset = self::PAGES_DATES_OFFSETS[$page][1];

        $dateFormant = 'Y-m-d';

        $startDate = Carbon::now()->addDays($startDayOffset)->format($dateFormant);
        $endDate = Carbon::now()->addDays($endDayOffset)->format($dateFormant);

        return sprintf(
            'php %s preload:spots %d %s %s > /dev/null &',
            base_path('artisan'),
            $accountNumber,
            $startDate,
            $endDate
        );
    }
}
