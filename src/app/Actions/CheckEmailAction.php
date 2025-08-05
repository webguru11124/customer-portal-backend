<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTO\Appointment\SearchAppointmentsDTO;
use App\DTO\CheckEmailResponseDTO;
use App\Interfaces\Auth0\UserService as Auth0UserService;
use App\Interfaces\Repository\AppointmentRepository;
use App\Interfaces\Repository\CustomerRepository;
use App\Interfaces\Repository\OfficeRepository;
use App\Interfaces\Repository\UserRepository;
use App\Models\External\CustomerModel;
use Aptive\PestRoutesSDK\Resources\Appointments\AppointmentStatus;
use Aptive\PestRoutesSDK\Resources\Customers\CustomerStatus;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class CheckEmailAction
{
    public function __construct(
        private readonly CustomerRepository $customerRepository,
        private readonly UserRepository $userRepository,
        private readonly Auth0UserService $auth0UserRepository,
        private readonly OfficeRepository $officeRepository,
        private readonly AppointmentRepository $appointmentRepository
    ) {
    }

    public function __invoke(string $email, string $auth): CheckEmailResponseDTO
    {
        $officeIDs = $this->officeRepository->getAllOfficeIds();

        /** @var Collection<int, CustomerModel> $customers */
        $customers = $this->customerRepository
            ->searchActiveCustomersByEmail($email, $officeIDs, null);
        $prCustomerExist = $customers->isNotEmpty();

        $user = $this->userRepository->getUser($email);
        $cpUserExists = !empty($user);

        $authUserExists = $cpUserExists &&
            !empty($user->getAttribute($auth === 'Auth0' ? 'external_id' : 'fusionauth_id'));

        if (!$prCustomerExist && $cpUserExists) {
            $this->userRepository->deleteUserWithAccounts($email);
            $authUserExists = false;
        }

        $hasRegistered = $authUserExists ? true : null;
        if ($prCustomerExist && !$authUserExists) {
            $hasRegistered = $this->auth0UserRepository->isRegisteredEmail($email);
        }

        return new CheckEmailResponseDTO(
            exists: $prCustomerExist,
            hasLoggedIn: $authUserExists,
            hasRegistered: $hasRegistered,
            completedInitialService: $this->isInitialServiceComplete($customers),
            status: $this->getCustomerStatus($customers),
        );
    }


    /**
     * @param Collection<int, CustomerModel> $customers
     * @return CustomerStatus
     */
    protected function getCustomerStatus(Collection $customers): CustomerStatus
    {
        $status = CustomerStatus::Inactive;
        $customer = $customers->first();

        if ($customer) {
            $status = $customer->status;
        }

        return $status;
    }

    /**
     * @param Collection<int, CustomerModel> $customers
     *
     * @return bool
     *
     * @throws ValidationException
     */
    private function isInitialServiceComplete(Collection $customers): bool
    {
        /** @var CustomerModel $customer */
        foreach ($customers as $customer) {
            $searchDto = new SearchAppointmentsDTO(
                officeId: $customer->officeId,
                accountNumber: [$customer->id],
                status: [AppointmentStatus::Completed]
            );
            $appointments = $this->appointmentRepository
                ->office($customer->officeId)
                ->search($searchDto);

            if ($appointments->isNotEmpty()) {
                return true;
            }
        }

        return false;
    }
}
