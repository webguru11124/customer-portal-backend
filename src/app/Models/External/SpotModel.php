<?php

declare(strict_types=1);

namespace App\Models\External;

use App\Interfaces\Repository\ExternalRepository;
use App\Interfaces\Repository\SpotRepository;

class SpotModel extends AbstractExternalModel
{
    public int $id;
    public int $officeId;
    public int|null $subscriptionId;
    public int $routeId;
    public \DateTimeInterface $start;
    public \DateTimeInterface $end;
    public int $capacity;
    public string $description;
    public int|null $currentAppointmentId;
    public int|null $currentAppointmentDuration;
    public float|null $distanceToPrevious;
    public float|null $previousLatitude;
    public float|null $previousLongitude;
    public int|null $previousCustomerId;
    public int|null $previousSpotId;
    public int|null $previousAppointmentId;
    public float|null $distanceToNext;
    public float|null $nextLatitude;
    public float|null $nextLongitude;
    public int|null $nextCustomerId;
    public int|null $nextSpotId;
    public int|null $nextAppointmentId;
    public int $assignedEmployeeId;
    public float|null $distanceToClosest;
    public bool $isReserved = false;
    public bool|null $isOpen = false;
    public bool $apiCanSchedule = true;
    public string|null $blockReason = null;
    public \DateTimeInterface|null $reservationEnd = null;
    /** @var int[]|null */
    public array|null $appointmentIds = [];
    /** @var int[]|null */
    public array|null $customerIds = [];

    /**
     * @return class-string<ExternalRepository<self>>
     */
    public static function getRepositoryClass(): string
    {
        return SpotRepository::class;
    }
}
