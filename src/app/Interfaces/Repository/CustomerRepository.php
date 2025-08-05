<?php

namespace App\Interfaces\Repository;

use App\DTO\Customer\UpdateCommunicationPreferencesDTO;
use App\Models\External\CustomerModel;
use Illuminate\Support\Collection;

/**
 * @extends ExternalRepository<CustomerModel>
 */
interface CustomerRepository extends ExternalRepository
{
    public function updateCustomerCommunicationPreferences(UpdateCommunicationPreferencesDTO $dto): int;

    /**
     * @param string $email
     * @param int[] $officeIds
     * @return Collection<int, CustomerModel>
     */
    public function searchActiveCustomersByEmail(string $email, array $officeIds, bool|null $isActive = true): Collection;
}
