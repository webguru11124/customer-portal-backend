<?php

declare(strict_types=1);

namespace App\Models\External;

use App\Helpers\DateTimeHelper;
use App\Interfaces\Repository\AppointmentRepository;
use App\Interfaces\Repository\ExternalRepository;
use App\Repositories\Relations\BelongsTo;
use App\Repositories\Relations\ExternalModelRelation;
use App\Repositories\Relations\HasMany;
use App\Traits\HandleServiceType;
use Aptive\PestRoutesSDK\Resources\Appointments\AppointmentStatus;
use Aptive\PestRoutesSDK\Resources\Appointments\AppointmentTimeWindow;
use DateTimeInterface;
use Illuminate\Support\Collection;

/**
 * @property ServiceTypeModel|null $serviceType
 * @property string $serviceTypeName
 * @property string $durationRepresentation
 * @property Collection<int, DocumentModel> $documents
 */
class AppointmentModel extends AbstractExternalModel
{
    use HandleServiceType;

    public const PR_NOTE_PREFIX = 'Scheduled via CXP';
    public const RESERVICE_SUBSCRIPTION_ID = -1;

    private const DURATION_REPRESENTATION_SUBSTRACTION = 5;
    private const DURATION_REPRESENTATION_ADDITION = 10;

    public int $id;
    public int $officeId;
    public int $customerId;
    public int $subscriptionId;
    public int|null $subscriptionRegionId;
    public int $routeId;
    public int|null $spotId;
    public DateTimeInterface|null $start;
    public DateTimeInterface|null $end;
    public int $duration;
    public int $serviceTypeId;
    public DateTimeInterface $dateAdded;
    public int $employeeId;
    public AppointmentStatus $status;
    public AppointmentTimeWindow $timeWindow;
    public int $callAhead;
    public bool $isInitial;
    public int|null $completedBy;
    public int|null $servicedBy;
    public DateTimeInterface|null $dateCompleted;
    public string|null $notes;
    public string|null $officeNotes;
    public DateTimeInterface|null $timeIn;
    public DateTimeInterface|null $timeOut;
    public DateTimeInterface|null $checkIn;
    public DateTimeInterface|null $checkOut;
    public int|null $windSpeed;
    public string|null $windDirection;
    public float|null $temperature;
    public float|null $amountCollected;
    public int|null $paymentMethod;
    public bool|null $servicedInterior;
    public int|null $ticketId;
    public DateTimeInterface|null $dateCancelled;

    /** @var int[] */
    public array $additionalTechs;
    public string|null $cancellationReason;

    /** @var int[] */
    public array $targetPests;
    public string|null $appointmentNotes;
    public bool $doInterior;
    public DateTimeInterface $dateUpdated;
    public int|null $cancelledBy;
    public int|null $assignedTech;
    public float $latIn;
    public float $latOut;
    public float $longIn;
    public float $longOut;
    public int $sequence;
    public int $lockedBy;

    /** @var int[] */
    public array $unitIds;

    /**
     * @return class-string<ExternalRepository<self>>
     */
    public static function getRepositoryClass(): string
    {
        return AppointmentRepository::class;
    }

    /**
     * @return array<string, ExternalModelRelation>
     */
    public function getRelations(): array
    {
        return [
            'serviceType' => new BelongsTo(ServiceTypeModel::class, 'serviceTypeId'),
            'documents' => new HasMany(DocumentModel::class, 'appointmentId'),
        ];
    }

    public function getServiceTypeName(): string
    {
        return $this->serviceType === null ? '' : $this->handleServiceTypeDescription($this->serviceType->description);
    }

    public function getDurationRepresentation(): string
    {
        return sprintf(
            '%d-%d min (times may vary)',
            $this->duration - self::DURATION_REPRESENTATION_SUBSTRACTION,
            $this->duration + self::DURATION_REPRESENTATION_ADDITION
        );
    }

    public function canBeCanceled(): bool
    {
        return
            $this->isUpcoming()
            && $this->isReservice()
            && !$this->isToday()
            && !$this->isInitial;
    }

    public function isReservice(): bool
    {
        return $this->serviceType === null ? false : $this->serviceType->isReservice;
    }

    public function canBeRescheduled(): bool
    {
        return
            $this->isUpcoming()
            && !$this->isToday()
            && !$this->isInitial;
    }

    public function isUpcoming(): bool
    {
        return $this->start !== null && DateTimeHelper::isFutureDate($this->start);
    }

    public function isTodayOrUpcoming(): bool
    {
        return $this->start !== null && DateTimeHelper::isTodayOrFutureDate($this->start);
    }

    public function isToday(): bool
    {
        return $this->start !== null && DateTimeHelper::isToday($this->start);
    }
}
