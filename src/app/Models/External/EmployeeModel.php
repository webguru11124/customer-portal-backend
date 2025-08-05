<?php

declare(strict_types=1);

namespace App\Models\External;

use App\Interfaces\Repository\EmployeeRepository;
use App\Interfaces\Repository\ExternalRepository;
use Aptive\PestRoutesSDK\Resources\Employees\EmployeeType;

class EmployeeModel extends AbstractExternalModel
{
    public int $id;
    public int $officeId;
    public bool $isActive;
    public string $firstName;
    public string $lastName;
    public string $initials;
    public string|null $nickname;
    public EmployeeType $type;
    public string|null $phone;
    public string $email;
    public string $username;
    public int $experience;
    public string|null $picture;
    /** @var int[] */
    public array $linkedEmployeeIds;
    public string|null $employeeLink;
    public string|null $licenseNumber;
    public int|null $supervisorId;
    public int|null $roamingRep;
    public \DateTimeInterface|null $lastLogin;
    /** @var int[] */
    public array $teamIds;
    public int $primaryTeamId;
    /** @var array<int, array<string, mixed>> */
    public array $accessControls;

    /**
     * @return class-string<ExternalRepository<self>>
     */
    public static function getRepositoryClass(): string
    {
        return EmployeeRepository::class;
    }
}
