<?php

declare(strict_types=1);

namespace Tests\Unit\Listeners;

use App\DTO\GenericFlagAssignmentsRequestDTO;
use App\Events\Subscription\SubscriptionCreated;
use App\Interfaces\Repository\GenericFlagAssignmentRepository;
use App\Listeners\AssignFlagToSubscription;
use App\Models\External\GenericFlagAssignmentModel;
use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use Aptive\PestRoutesSDK\Resources\GenericFlagAssignments\GenericFlagAssignmentType;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Tests\Data\GenericFlagAssignmentData;
use Tests\TestCase;
use Tests\Traits\RandomIntTestData;

final class AssignFlagToSubscriptionTest extends TestCase
{
    use RandomIntTestData;

    protected AssignFlagToSubscription $subject;
    protected GenericFlagAssignmentRepository $genericFlagAssignmentRepositoryMock;

    public function setUp(): void
    {
        parent::setUp();

        $this->genericFlagAssignmentRepositoryMock = \Mockery::mock(GenericFlagAssignmentRepository::class);

        $this->subject = new AssignFlagToSubscription($this->genericFlagAssignmentRepositoryMock);
    }

    public function test_it_listens_event(): void
    {
        Event::fake();

        Event::assertListening(SubscriptionCreated::class, $this->subject::class);
    }

    public function test_it_assign_generic_flag(): void
    {
        /** @var GenericFlagAssignmentModel $genericFlagAssignment */
        $genericFlagAssignment = GenericFlagAssignmentData::getTestEntityData(1)->first();

        $event = $this->getEvent();

        $this->genericFlagAssignmentRepositoryMock
            ->shouldReceive('office')
            ->withArgs([$event->getOfficeId()])
            ->once()
            ->andReturn($this->genericFlagAssignmentRepositoryMock);

        $this->genericFlagAssignmentRepositoryMock
            ->shouldReceive('assignGenericFlag')
            ->withArgs(
                fn (
                    GenericFlagAssignmentsRequestDTO $requestDTO
                ) => $requestDTO->genericFlagId === $this->getTestSubscriptionFlagId() &&
                    $requestDTO->entityId === $this->getTestSubscriptionId() &&
                    $requestDTO->type === GenericFlagAssignmentType::SUBS
            )
            ->once()
            ->andReturn($genericFlagAssignment->id);

        $this->subject->handle($event);
    }

    public function test_it_skip_processing_if_subscription_flag_is_empty(): void
    {
        $event = new SubscriptionCreated(
            subscriptionId: $this->getTestSubscriptionId(),
            officeId: $this->getTestOfficeId(),
            subscriptionFlag: null,
        );

        $this->genericFlagAssignmentRepositoryMock
            ->shouldReceive('assignGenericFlag')
            ->withAnyArgs()
            ->never();

        $this->subject->handle($event);
    }

    public function test_it_throws_internal_server_error_http_exception(): void
    {
        $event = $this->getEvent();

        $errorMessage = sprintf(
            'Flag %d was not assigned to subscription %d after event %s due to: %s',
            $event->getSubscriptionFlag(),
            $event->getSubscriptionId(),
            SubscriptionCreated::class,
            ''
        );

        Log::shouldReceive('error')
            ->withArgs(fn (string $message) => str_contains($message, $errorMessage))
            ->andReturn(null);

        $this->genericFlagAssignmentRepositoryMock
            ->shouldReceive('office')
            ->withArgs([$event->getOfficeId()])
            ->once()
            ->andReturn($this->genericFlagAssignmentRepositoryMock);

        $this->genericFlagAssignmentRepositoryMock
            ->shouldReceive('assignGenericFlag')
            ->withArgs(
                fn (
                    GenericFlagAssignmentsRequestDTO $requestDTO
                ) => $requestDTO->genericFlagId === $this->getTestSubscriptionFlagId() &&
                    $requestDTO->entityId === $this->getTestSubscriptionId() &&
                    $requestDTO->type === GenericFlagAssignmentType::SUBS
            )
            ->andThrow(new InternalServerErrorHttpException());

        $this->subject->handle($event);
    }

    private function getEvent(): SubscriptionCreated
    {
        return new SubscriptionCreated(
            subscriptionId: $this->getTestSubscriptionId(),
            officeId: $this->getTestOfficeId(),
            subscriptionFlag: $this->getTestSubscriptionFlagId(),
        );
    }
}
