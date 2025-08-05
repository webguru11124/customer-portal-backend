<?php

declare(strict_types=1);

namespace Tests\Unit\Listeners;

use App\DTO\Subscriptions\SubscriptionAddonRequestDTO;
use App\DTO\Ticket\CreateTicketTemplatesAddonRequestDTO;
use App\Events\Subscription\SubscriptionCreated;
use App\Exceptions\Entity\EntityNotFoundException;
use App\Helpers\SubscriptionAddonsConfigHelper;
use App\Interfaces\Repository\SubscriptionRepository;
use App\Interfaces\Repository\TicketTemplateAddonRepository;
use App\Listeners\AddRecurringAddonsToSubscription;
use App\Models\External\SubscriptionAddonModel;
use App\Models\External\SubscriptionModel;
use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Tests\Data\SubscriptionAddonData;
use Tests\Data\SubscriptionData;
use Tests\Data\TicketData;
use Tests\TestCase;
use Tests\Traits\RandomIntTestData;

final class AddRecurringAddonsToSubscriptionTest extends TestCase
{
    use RandomIntTestData;

    protected AddRecurringAddonsToSubscription $subject;
    protected SubscriptionRepository $subscriptionRepository;
    protected TicketTemplateAddonRepository $ticketAddonRepository;

    public function setUp(): void
    {
        parent::setUp();

        $this->subscriptionRepository = \Mockery::mock(SubscriptionRepository::class);
        $this->ticketAddonRepository = \Mockery::mock(TicketTemplateAddonRepository::class);

        $this->subject = new AddRecurringAddonsToSubscription(
            ticketAddonRepository: $this->ticketAddonRepository,
            subscriptionRepository: $this->subscriptionRepository,
        );
    }

    public function test_it_listens_event(): void
    {
        Event::fake();

        Event::assertListening(SubscriptionCreated::class, $this->subject::class);
    }

    public function test_it_add_recurring_addon_to_subscription(): void
    {
        /** @var SubscriptionAddonModel $subscriptionAddon */
        $subscriptionAddon = SubscriptionAddonData::getTestEntityData(1)->first();
        /** @var array $recurringTicket */
        $recurringTicket = TicketData::getRawTestData(1)->first();
        /** @var SubscriptionModel $subscription */
        $subscription = SubscriptionData::getTestEntityData(1, [
            'recurringTicket' => (object) $recurringTicket
        ])->first();

        $event = $this->getEvent();

        $this->prepareOffice($event->getOfficeId());

        $this->subscriptionRepository
            ->expects('find')
            ->withArgs([$event->getSubscriptionId()])
            ->once()
            ->andReturn($subscription);

        $this->ticketAddonRepository
            ->shouldReceive('createTicketsAddon')
            ->withArgs(
                fn (CreateTicketTemplatesAddonRequestDTO $requestDTO) => $requestDTO->ticketId === $recurringTicket['ticketID'] &&
                    $requestDTO->description === SubscriptionAddonsConfigHelper::getAddonDefaultName() &&
                    $requestDTO->quantity === SubscriptionAddonsConfigHelper::getAddonDefaultQuantity() &&
                    $requestDTO->amount === SubscriptionAddonsConfigHelper::getAddonDefaultAmount() &&
                    $requestDTO->isTaxable === SubscriptionAddonsConfigHelper::getAddonDefaultTaxable() &&
                    $requestDTO->creditTo === SubscriptionAddonsConfigHelper::getAddonDefaultCreditTo() &&
                    $requestDTO->productId === $this->getTestProductId() &&
                    $requestDTO->serviceId === SubscriptionAddonsConfigHelper::getAddonDefaultServiceId()
            )
            ->once()
            ->andReturn($subscriptionAddon->id);

        $this->subject->handle($event);
    }

    public function test_it_skip_processing_if_recurring_ticket_is_empty(): void
    {
        /** @var SubscriptionModel $subscription */
        $subscription = SubscriptionData::getTestEntityData(1, [
            'recurringTicket' => null
        ])->first();

        $event = $this->getEvent();

        $this->prepareOffice($event->getOfficeId());

        $this->subscriptionRepository
            ->expects('find')
            ->withArgs([$event->getSubscriptionId()])
            ->once()
            ->andReturn($subscription);

        $this->ticketAddonRepository
            ->shouldReceive('createTicketsAddon')
            ->withAnyArgs()
            ->never();

        $this->subject->handle($event);
    }

    public function test_it_skip_processing_if_recurring_addon_is_empty(): void
    {
        $event = new SubscriptionCreated(
            subscriptionId: $this->getTestSubscriptionId(),
            officeId: $this->getTestOfficeId(),
            subscriptionFlag: $this->getTestSubscriptionFlagId(),
            recurringAddons: [],
        );

        $this->subscriptionRepository
            ->shouldReceive('createInitialAddon')
            ->withAnyArgs()
            ->never();

        $this->subject->handle($event);
    }

    public function test_it_throws_internal_server_error_http_exception(): void
    {
        $event = $this->getEvent();

        $this->prepareExceptionLog();
        $this->prepareOffice($event->getOfficeId());

        $this->ticketAddonRepository
            ->shouldReceive('createTicketsAddon')
            ->withAnyArgs()
            ->andThrow(new InternalServerErrorHttpException());

        $this->subject->handle($event);
    }

    public function test_it_throws_entity_not_found_exception(): void
    {
        $event = $this->getEvent();

        $this->prepareExceptionLog();
        $this->prepareOffice($event->getOfficeId());

        $this->subscriptionRepository
            ->expects('find')
            ->withArgs([$event->getSubscriptionId()])
            ->once()
            ->andReturn(new EntityNotFoundException());

        $this->subject->handle($event);
    }

    private function getEvent(): SubscriptionCreated
    {
        return new SubscriptionCreated(
            subscriptionId: $this->getTestSubscriptionId(),
            officeId: $this->getTestOfficeId(),
            subscriptionFlag: $this->getTestSubscriptionFlagId(),
            recurringAddons: [
                new SubscriptionAddonRequestDTO(
                    productId: $this->getTestProductId(),
                    amount: SubscriptionAddonsConfigHelper::getAddonDefaultAmount(),
                    description: SubscriptionAddonsConfigHelper::getAddonDefaultName(),
                    quantity: SubscriptionAddonsConfigHelper::getAddonDefaultQuantity(),
                    taxable: SubscriptionAddonsConfigHelper::getAddonDefaultTaxable(),
                    serviceId: SubscriptionAddonsConfigHelper::getAddonDefaultServiceId(),
                    creditTo: SubscriptionAddonsConfigHelper::getAddonDefaultCreditTo(),
                    officeId: $this->getTestOfficeId()
                ),
            ]
        );
    }

    private function prepareOffice(int $officeId): void
    {
        $this->ticketAddonRepository
            ->expects('office')
            ->withArgs([$officeId])
            ->once()
            ->andReturn($this->ticketAddonRepository);

        $this->subscriptionRepository
            ->expects('office')
            ->withArgs([$officeId])
            ->once()
            ->andReturn($this->subscriptionRepository);
    }

    private function prepareExceptionLog(): void
    {
        $event = $this->getEvent();

        $errorMessage = sprintf(
            'Recurring addon %s was not assigned to subscription %d after event %s due to: %s',
            current($event->getSubscriptionRecurringAddons())->description,
            $event->getSubscriptionId(),
            SubscriptionCreated::class,
            ''
        );

        Log::shouldReceive('error')
            ->withArgs(fn (string $message) => str_contains($message, $errorMessage))
            ->andReturn(null);
    }
}
