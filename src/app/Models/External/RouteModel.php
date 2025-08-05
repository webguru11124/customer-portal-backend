<?php

declare(strict_types=1);

namespace App\Models\External;

use App\Interfaces\Repository\ExternalRepository;
use App\Interfaces\Repository\RouteRepository;

class RouteModel extends AbstractExternalModel
{
    private const INITIAL_ROUTE_SUBSTRING = 'initial';

    public int $id;
    public string $title;
    public int $templateId;
    public \DateTimeInterface|null $dateAdded;
    public int $addedBy;
    public int $officeId;
    public string $groupTitle;
    public \DateTimeInterface $date;
    public string|null $dayNotes;
    public string|null $dayAlert;
    public int $dayId;
    /** @var int[] */
    public array $additionalTechs;
    public int|null $assignedTech;
    public bool $apiCanSchedule;
    /** @var int[] */
    public array $scheduleTeams;
    /** @var int[] */
    public array $scheduleTypes;
    public float $averageLatitude;
    public float $averageLongitude;
    public float $averageDistance;
    public \DateTimeInterface|null $dateUpdated;
    public int $distanceScore;

    /**
     * @return class-string<ExternalRepository<self>>
     */
    public static function getRepositoryClass(): string
    {
        return RouteRepository::class;
    }

    public function isInitial(): bool
    {
        if (stripos($this->groupTitle, self::INITIAL_ROUTE_SUBSTRING) !== false) {
            return true;
        }

        if (stripos($this->title, self::INITIAL_ROUTE_SUBSTRING) !== false) {
            return true;
        }

        return false;
    }
}
