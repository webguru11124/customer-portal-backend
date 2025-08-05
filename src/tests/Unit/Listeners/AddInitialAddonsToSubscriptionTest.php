<?php

declare(strict_types=1);

namespace Tests\Unit\Listeners;

use App\DTO\Subscriptions\SubscriptionAddonRequestDTO;
use App\Events\Subscription\SubscriptionCreated;
use App\Interfaces\Repository\SubscriptionAddonRepository;
use App\Listeners\AddInitialAddonsToSubscription;
use App\Models\External\SubscriptionAddonModel;
use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Tests\Data\SubscriptionAddonData;
use Tests\TestCase;
use Tests\Traits\RandomIntTestData;

final class AddInitialAddonsToSubscriptionTest extends TestCase
{
    use RandomIntTestData;

    protected AddInitialAddonsToSubscription $subject;
    protected SubscriptionAddonRepository $subscriptionAddonRepository;

    public function setUp(): void
    {
        parent::setUp();

        $this->subscriptionAddonRepository = \Mockery::mock(SubscriptionAddonRepository::class);

        $this->subject = new AddInitialAddonsToSubscription($this->subscriptionAddonRepository);
    }

    public function test_it_listens_event(): void
    {
        Event::fake();

        Event::assertListening(SubscriptionCreated::class, $this->subject::class);
    }

    public function test_it_add_initial_addon_to_subscription(): void
    {
        /** @var SubscriptionAddonModel $subscriptionAddon */
        $subscriptionAddon = SubscriptionAddonData::getTestEntityData(1)->first();

        $event = $this->getEvent();

        $this->subscriptionAddonRepository
            ->shouldReceive('office')
            ->withArgs([$event->getOfficeId()])
            ->once()
            ->andReturn($this->subscriptionAddonRepository);

        $this->subscriptionAddonRepository
            ->shouldReceive('createInitialAddon')
            ->withArgs([$this->getTestSubscriptionId(), current($event->getSubscriptionInitialAddons())])
            ->once()
            ->andReturn($subscriptionAddon->id);

        $this->subject->handle($event);
    }

    public function test_it_skip_processing_if_initial_addon_is_empty(): void
    {
        $event = new SubscriptionCreated(
            subscriptionId: $this->getTestSubscriptionId(),
            officeId: $this->getTestOfficeId(),
            subscriptionFlag: $this->getTestSubscriptionFlagId(),
            initialAddons: [],
        );

        $this->subscriptionAddonRepository
            ->shouldReceive('createInitialAddon')
            ->withAnyArgs()
            ->never();

        $this->subject->handle($event);
    }

    public function test_it_throws_internal_server_error_http_exception(): void
    {
        $event = $this->getEvent();

        $errorMessage = sprintf(
            'Initial addon %s was not assigned to subscription %d after event %s due to: %s',
            current($event->getSubscriptionInitialAddons())->description,
            $event->getSubscriptionId(),
            SubscriptionCreated::class,
            ''
        );

        Log::shouldReceive('error')
            ->withArgs(fn (string $message) => str_contains($message, $errorMessage))
            ->andReturn(null);

        $this->subscriptionAddonRepository
            ->shouldReceive('office')
            ->withArgs([$event->getOfficeId()])
            ->once()
            ->andReturn($this->subscriptionAddonRepository);

        $this->subscriptionAddonRepository
            ->shouldReceive('createInitialAddon')
            ->withArgs([$this->getTestSubscriptionId(), current($event->getSubscriptionInitialAddons())])
            ->andThrow(new InternalServerErrorHttpException());

        $this->subject->handle($event);
    }

    private function getEvent(): SubscriptionCreated
    {
        return new SubscriptionCreated(
            subscriptionId: $this->getTestSubscriptionId(),
            officeId: $this->getTestOfficeId(),
            subscriptionFlag: $this->getTestSubscriptionFlagId(),
            initialAddons: [
                new SubscriptionAddonRequestDTO(
                    productId: $this->getTestProductId(),
                    amount: 99,
                    description: 'Addon #1',
                ),
            ]
        );
    }
}
