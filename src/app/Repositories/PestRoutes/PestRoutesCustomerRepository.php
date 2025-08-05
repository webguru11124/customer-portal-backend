<?php

namespace App\Repositories\PestRoutes;

use App\DTO\Customer\SearchCustomersDTO;
use App\DTO\Customer\UpdateCommunicationPreferencesDTO;
use App\Exceptions\Account\AccountFrozenException;
use App\Exceptions\Entity\EntityNotFoundException;
use App\Exceptions\Entity\InvalidSearchedResourceException;
use App\Exceptions\PestRoutesRepository\OfficeNotSetException;
use App\Helpers\ConfigHelper;
use App\Interfaces\Repository\CustomerRepository;
use App\Models\External\CustomerModel;
use App\Repositories\Mappers\PestRoutesCustomerToExternalModelMapper;
use App\Repositories\PestRoutes\ParametersFactories\CustomerParametersFactory;
use App\Services\LoggerAwareTrait;
use App\Services\PestRoutesClientAwareTrait;
use App\Traits\Repositories\EntityMapperAware;
use App\Traits\Repositories\HttpParametersFactoryAware;
use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use Aptive\PestRoutesSDK\Resources\Customers\Customer;
use Aptive\PestRoutesSDK\Resources\Customers\CustomersResource;
use Aptive\PestRoutesSDK\Resources\Customers\CustomerStatus;
use Aptive\PestRoutesSDK\Resources\Customers\Params\UpdateCustomersParams;
use Aptive\PestRoutesSDK\Resources\Offices\OfficesResource;
use Aptive\PestRoutesSDK\Resources\Resource;
use Illuminate\Support\Collection;

/**
 * @extends AbstractPestRoutesRepository<CustomerModel, Customer>
 */
class PestRoutesCustomerRepository extends AbstractPestRoutesRepository implements CustomerRepository
{
    use PestRoutesClientAwareTrait;
    use LoggerAwareTrait;
    /**
     * @use EntityMapperAware<Customer, CustomerModel>
     */
    use EntityMapperAware;
    use HttpParametersFactoryAware;

    public function __construct(
        PestRoutesCustomerToExternalModelMapper $entityMapper,
        CustomerParametersFactory $httpParametersFactory
    ) {
        $this->entityMapper = $entityMapper;
        $this->httpParametersFactory = $httpParametersFactory;

        parent::__construct();
    }

    public function updateCustomerCommunicationPreferences(UpdateCommunicationPreferencesDTO $dto): int
    {
        $params = new UpdateCustomersParams(
            customerId: $dto->accountNumber,
            smsReminders: $dto->smsReminders,
            phoneReminders: $dto->phoneReminders,
            emailReminders: $dto->emailReminders
        );

        return $this
            ->getPestRoutesClient()
            ->office($dto->officeId)
            ->customers()
            ->update($params);
    }

    /**
     * @param string $email
     *
     * @return Collection<int, CustomerModel>
     */
    public function searchActiveCustomersByEmail(string $email, array $officeIds, bool|null $isActive = true): Collection
    {
        $searchDto = new SearchCustomersDTO(
            officeIds: $officeIds,
            email: $email,
            isActive: $isActive
        );

        /** @var Collection<int, CustomerModel> $result */
        $result = $this
            ->office(ConfigHelper::getGlobalOfficeId())
            ->search($searchDto);

        return $result;
    }

    /**
     * @return Collection<int, Customer>
     *
     * @throws InternalServerErrorHttpException
     * @throws InvalidSearchedResourceException
     * @throws OfficeNotSetException
     */
    protected function findManyNative(int ...$id): Collection
    {
        $searchDto = new SearchCustomersDTO(
            officeIds: [$this->getOfficeId()],
            accountNumbers: $id
        );

        /** @var Collection<int, Customer> $result */
        $result = $this->searchNative($searchDto);

        return $result;
    }

    /**
     * @return CustomersResource
     */
    protected function getSearchedResource(OfficesResource $officesResource): Resource
    {
        return $officesResource->customers();
    }

    /**
     * @throws AccountFrozenException
     * @throws EntityNotFoundException
     */
    public function find(int $id): CustomerModel
    {
        // @codeCoverageIgnoreStart
        // Unable to test this method because of error on calling "find" method in CustomersResource mock object
        /** @var CustomerModel $customer */
        $customer = parent::find($id);

        if ($customer->status === CustomerStatus::Inactive) {
            $message = sprintf('Customer account ID=%d is frozen.', $id);

            throw new AccountFrozenException($message);
        }

        return $customer;
        // @codeCoverageIgnoreEnd
    }
}
