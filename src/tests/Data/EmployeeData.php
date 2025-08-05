<?php

namespace Tests\Data;

use App\Models\External\EmployeeModel;
use App\Repositories\Mappers\PestRoutesEmployeeToExternalModelMapper;
use Aptive\PestRoutesSDK\Resources\Employees\Employee;

/**
 * @extends AbstractTestPestRoutesData<Employee, EmployeeModel>
 */
class EmployeeData extends AbstractTestPestRoutesData
{
    protected static function getRequiredEntityClass(): string
    {
        return Employee::class;
    }

    protected static function getSignature(): array
    {
        $id = random_int(10000, 99999);

        return [
            'employeeID' => $id,
            'officeID' => '1',
            'active' => '1',
            'fname' => 'CXP',
            'lname' => 'Scheduler',
            'initials' => 'CS',
            'nickname' => '',
            'type' => '0',
            'phone' => '',
            'email' => '',
            'username' => '',
            'experience' => '1',
            'pic' => '',
            'linkedEmployeeIDs' => '0',
            'employeeLink' => '',
            'licenseNumber' => '',
            'supervisorID' => '-1',
            'roamingRep' => '0',
            'lastLogin' => '0000-00-00 00:00:00',
            'teamIDs' => [
            ],
            'primaryTeam' => '0',
            'accessControlProfileID' => '1',
            'startAddress' => '1960 parker ct suite B',
            'startCity' => 'stone mountain',
            'startState' => 'GA',
            'startZip' => '30087',
            'startLat' => '33.828',
            'startLng' => '-84.1065',
            'endAddress' => '1960 parker ct suite B',
            'endCity' => 'stone mountain',
            'endState' => 'GA',
            'endZip' => '30087',
            'endLat' => '33.828',
            'endLng' => '-84.1065',
            'dateUpdated' => '2023-03-17 06:58:10',
            'skills' => [
            ],
            'accessControl' => [
            ],
        ];
    }

    protected static function getMapperClass(): string
    {
        return PestRoutesEmployeeToExternalModelMapper::class;
    }
}
