<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories\PestRoutes;

use App\DTO\Employee\SearchEmployeesDTO;
use App\Exceptions\Entity\EntityNotFoundException;
use App\Models\External\EmployeeModel;
use App\Models\External\ServiceTypeModel;
use App\Repositories\Mappers\PestRoutesEmployeeToExternalModelMapper;
use App\Repositories\PestRoutes\AbstractPestRoutesRepository;
use App\Repositories\PestRoutes\ParametersFactories\EmployeeParametersFactory;
use App\Repositories\PestRoutes\PestRoutesEmployeeRepository;
use Aptive\PestRoutesSDK\Collection as PestRoutesCollection;
use Aptive\PestRoutesSDK\Resources\Employees\Employee;
use Aptive\PestRoutesSDK\Resources\Employees\EmployeesResource;
use Aptive\PestRoutesSDK\Resources\Employees\Params\SearchEmployeesParams;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Tests\Data\EmployeeData;
use Tests\TestCase;
use Tests\Traits\PestRoutesClientMockBuilderAware;
use Tests\Traits\RandomIntTestData;
use Tests\Unit\Repositories\ExtendsAbstractExternalRepository;

final class PestRoutesEmployeeRepositoryTest extends TestCase
{
    use RandomIntTestData;
    use PestRoutesClientMockBuilderAware;
    use ExtendsAbstractPestRoutesRepository;
    use ExtendsAbstractExternalRepository;

    protected PestRoutesEmployeeRepository $subject;

    public function setUp(): void
    {
        parent::setUp();

        $this->subject = new PestRoutesEmployeeRepository(
            new PestRoutesEmployeeToExternalModelMapper(),
            new EmployeeParametersFactory()
        );
    }

    protected function getSubject(): AbstractPestRoutesRepository
    {
        return $this->subject;
    }

    public function test_it_finds_single_employee(): void
    {
        /** @var Employee $pestRoutesEmployee */
        $pestRoutesEmployee = EmployeeData::getTestData(
            1,
            ['employeeID' => $this->getTestEmployeeId()]
        )->first();

        $pestRoutesClientMock = $this->getPestRoutesClientMockBuilder()
            ->office($this->getTestOfficeId())
            ->resource(EmployeesResource::class)
            ->callSequense('employees', 'find')
            ->methodExpectsArgs('find', [$this->getTestEmployeeId()])
            ->willReturn($pestRoutesEmployee)
            ->mock();

        $this->subject->setPestRoutesClient($pestRoutesClientMock);

        /** @var ServiceTypeModel $result */
        $result = $this->subject
            ->office($this->getTestOfficeId())
            ->find($this->getTestEmployeeId());

        self::assertEquals($pestRoutesEmployee->id, $result->id);
    }

    public function test_it_searches_employees(): void
    {
        $fname = 'fname';
        $lname = 'lname';

        $searchDto = new SearchEmployeesDTO(
            officeId: $this->getTestOfficeId(),
            fname: $fname,
            lname: $lname
        );

        $employees = EmployeeData::getTestData(2);

        $pestRoutesClientMock = $this->getPestRoutesClientMockBuilder()
            ->office($this->getTestOfficeId())
            ->resource(EmployeesResource::class)
            ->callSequense('employees', 'includeData', 'search', 'all')
            ->methodExpectsArgs(
                'search',
                function (SearchEmployeesParams $params) use ($fname, $lname) {
                    $array = $params->toArray();

                    return $array['officeIDs'] === (string) $this->getTestOfficeId()
                        && $array['fname'] === $fname
                        && $array['lname'] === $lname;
                }
            )
            ->willReturn(new PestRoutesCollection($employees->toArray()))
            ->mock();

        $this->subject->setPestRoutesClient($pestRoutesClientMock);

        $result = $this->subject
            ->office($this->getTestOfficeId())
            ->search($searchDto);

        self::assertCount($employees->count(), $result);
        self::assertEquals(
            $employees->map(fn (Employee $employee) => $employee->id)->toArray(),
            $result->map(fn (EmployeeModel $employee) => $employee->id)->toArray(),
        );
    }

    public function test_it_searches_default_cxp_scheduler(): void
    {
        ['fname' => $fname, 'lname' => $lname] = Config::get('aptive.cxp_scheduler_name');

        $employees = EmployeeData::getTestData(2);

        $pestRoutesClientMock = $this->getPestRoutesClientMockBuilder()
            ->office($this->getTestOfficeId())
            ->resource(EmployeesResource::class)
            ->callSequense('employees', 'includeData', 'search', 'all')
            ->methodExpectsArgs(
                'search',
                function (SearchEmployeesParams $params) use ($fname, $lname) {
                    $array = $params->toArray();

                    return $array['fname'] === $fname
                        && $array['lname'] === $lname;
                }
            )
            ->willReturn(new PestRoutesCollection($employees->toArray()))
            ->mock();

        $this->subject->setPestRoutesClient($pestRoutesClientMock);

        $result = $this->subject
            ->office($this->getTestOfficeId())
            ->findCxpScheduler();

        self::assertEquals(
            $employees->first()->id,
            $result->id,
        );
    }

    public function test_find_cxp_scheduler_throws_entity_not_found_exception(): void
    {
        $pestRoutesClientMock = $this->getPestRoutesClientMockBuilder()
            ->office($this->getTestOfficeId())
            ->resource(EmployeesResource::class)
            ->callSequense('employees', 'includeData', 'search', 'all')
            ->willReturn(new PestRoutesCollection())
            ->mock();

        $this->subject->setPestRoutesClient($pestRoutesClientMock);

        $this->expectException(EntityNotFoundException::class);

        $this->subject
            ->office($this->getTestOfficeId())
            ->findCxpScheduler();
    }

    public function test_it_finds_many(): void
    {
        $ids = [
            $this->getTestEmployeeId(),
            $this->getTestEmployeeId() + 1,
        ];

        /** @var Collection<int, Employee> $employees */
        $employees = EmployeeData::getTestData(2);

        $clientMock = $this->getPestRoutesClientMockBuilder()
            ->office($this->getTestOfficeId())
            ->resource(EmployeesResource::class)
            ->callSequense('employees', 'includeData', 'search', 'all')
            ->methodExpectsArgs(
                'search',
                function (SearchEmployeesParams $params) use ($ids) {
                    $array = $params->toArray();

                    return $array['officeIDs'] === (string) $this->getTestOfficeId()
                        && $array['active'] === (string) true
                        && $array['employeeIDs'] === $ids;
                }
            )
            ->willReturn(new PestRoutesCollection($employees->all()))
            ->mock();

        $this->subject->setPestRoutesClient($clientMock);

        $result = $this->subject
            ->office($this->getTestOfficeId())
            ->findMany(...$ids);

        $this->assertCount($employees->count(), $result);
    }
}
