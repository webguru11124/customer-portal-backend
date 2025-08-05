<?php

declare(strict_types=1);

namespace App\Repositories\PestRoutes;

use App\DTO\Employee\SearchEmployeesDTO;
use App\Exceptions\Entity\EntityNotFoundException;
use App\Exceptions\Entity\RelationNotFoundException;
use App\Helpers\ConfigHelper;
use App\Interfaces\Repository\EmployeeRepository;
use App\Models\External\EmployeeModel;
use App\Repositories\Mappers\PestRoutesEmployeeToExternalModelMapper;
use App\Repositories\PestRoutes\ParametersFactories\EmployeeParametersFactory;
use App\Services\LoggerAwareTrait;
use App\Traits\Repositories\EntityMapperAware;
use App\Traits\Repositories\HttpParametersFactoryAware;
use Aptive\PestRoutesSDK\Resources\Employees\Employee;
use Aptive\PestRoutesSDK\Resources\Offices\OfficesResource;
use Aptive\PestRoutesSDK\Resources\Resource;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * @extends AbstractPestRoutesRepository<EmployeeModel, Employee>
 */
class PestRoutesEmployeeRepository extends AbstractPestRoutesRepository implements EmployeeRepository
{
    /**
     * @use EntityMapperAware<Employee, EmployeeModel>
     */
    use EntityMapperAware;
    use HttpParametersFactoryAware;
    use LoggerAwareTrait;

    public function __construct(
        PestRoutesEmployeeToExternalModelMapper $entityMapper,
        EmployeeParametersFactory $httpParametersFactory
    ) {
        $this->entityMapper = $entityMapper;
        $this->httpParametersFactory = $httpParametersFactory;

        parent::__construct();
    }

    protected function findManyNative(int ...$id): Collection
    {
        $searchDto = new SearchEmployeesDTO(
            officeId: $this->getOfficeId(),
            ids: $id
        );

        return $this->searchNative($searchDto);
    }

    protected function getSearchedResource(OfficesResource $officesResource): Resource
    {
        return $officesResource->employees();
    }

    /**
     * @throws EntityNotFoundException
     * @throws RelationNotFoundException
     * @throws ValidationException
     */
    public function findCxpScheduler(): EmployeeModel
    {
        ['fname' => $fname, 'lname' => $lname] = ConfigHelper::getCxpSchedulerName();

        $searchDto = new SearchEmployeesDTO(
            officeId: $this->getOfficeId(),
            fname: $fname,
            lname: $lname
        );

        $employees = $this->search($searchDto);

        if ($employees->isEmpty()) {
            throw new EntityNotFoundException(
                sprintf('Can not find default CXP scheduler in office %d', $this->getOfficeId())
            );
        }

        /** @var EmployeeModel $result */
        $result = $employees->first();

        return $result;
    }
}
