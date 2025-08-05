<?php

declare(strict_types=1);

namespace App\Actions\Appointment;

use App\DTO\Appointment\SearchAppointmentsDTO;
use App\DTO\FlexIVR\Appointment\CreateAppointment;
use App\Enums\FlexIVR\AppointmentType;
use App\Enums\FlexIVR\Window;
use App\Events\Appointment\AppointmentScheduled;
use App\Exceptions\Account\AccountNotFoundException;
use App\Exceptions\Appointment\AppointmentCanNotBeCreatedException;
use App\Exceptions\Appointment\AppointmentSpotAlreadyUsedException;
use App\Exceptions\Appointment\CannotCreateAppointmentException;
use App\Exceptions\Appointment\CannotResolveAppointmentSubscriptionException;
use App\Exceptions\Entity\EntityNotFoundException;
use App\Helpers\ConfigHelper;
use App\Interfaces\FlexIVRApi\AppointmentRepository as FlexIVRApiAppointmentRepository;
use App\Interfaces\Repository\AppointmentRepository;
use App\Interfaces\Repository\CustomerRepository;
use App\Models\Account;
use App\Models\External\AppointmentModel;
use App\Models\External\CustomerModel;
use App\Models\External\SubscriptionModel;
use App\Services\AccountService;
use App\Services\AppointmentService;
use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use Aptive\PestRoutesSDK\Resources\Appointments\AppointmentStatus;
use Illuminate\Validation\ValidationException;
use DateTimeImmutable;

/**
 * @final
 */
class CreateAppointmentInFlexIVRAction
{
    public function __construct(
        private readonly AccountService $accountService,
        private readonly AppointmentService $appointmentService,
        private readonly CustomerRepository $customerRepository,
        private readonly AppointmentRepository $appointmentRepository,
        private readonly FlexIVRApiAppointmentRepository $flexAppointmentRepository,
    ) {
    }

    /**
     * @param int $accountNumber
     * @param int $spotId
     * @param Window $window
     * @param bool $isAroSpot
     * @param string|null $notes
     * @return AppointmentModel
     * @throws AccountNotFoundException
     * @throws AppointmentCanNotBeCreatedException
     * @throws AppointmentSpotAlreadyUsedException
     * @throws CannotResolveAppointmentSubscriptionException
     * @throws EntityNotFoundException
     * @throws InternalServerErrorHttpException
     * @throws ValidationException
     * @throws CannotCreateAppointmentException
     */
    public function __invoke(
        int $accountNumber,
        int $spotId,
        Window $window,
        bool $isAroSpot,
        string|null $notes = null,
    ): AppointmentModel {

        $hasPendingOrCompletedAppointments = $this->customerHasPendingOrCompletedAppointments(
            accountNumber: $accountNumber,
            status: [AppointmentStatus::Pending, AppointmentStatus::Completed]
        );

        if (!$hasPendingOrCompletedAppointments) {
            throw new CannotCreateAppointmentException(CannotCreateAppointmentException::INITIAL_APPOINTMENT_ERROR);
        }

        $account = $this->accountService->getAccountByAccountNumber($accountNumber);
        /** @var CustomerModel $customer */
        $customer = $this->customerRepository
            ->office($account->office_id)
            ->withRelated(['subscriptions.serviceType', 'appointments.serviceType'])
            ->find($account->account_number);
        [$subscriptionId, $appointmentType] = $this->getSubscriptionAndServiceType($customer, $account);

        $dto = new CreateAppointment(
            officeId: $account->office_id,
            accountNumber: $account->account_number,
            subscriptionId: $subscriptionId,
            spotId: $spotId,
            window: $window,
            appointmentType: $appointmentType,
            isAroSpot: $isAroSpot,
            notes: $notes,
        );

        if (($this->appointmentService->hasSubscriptionWithoutPendingAppointment($dto)->isFalse())) {
            throw new CannotCreateAppointmentException();
        }

        $appointmentId = $this->flexAppointmentRepository->createAppointment($dto);
        $appointment = $this->appointmentRepository
            ->office($account->office_id)
            ->withRelated(['serviceType'])
            ->find($appointmentId);
        AppointmentScheduled::dispatch($account->account_number);

        return $appointment;
    }

    /**
     * @param CustomerModel $customer
     *
     * @return array{int, AppointmentType}
     *
     * @throws AppointmentCanNotBeCreatedException
     * @throws EntityNotFoundException
     * @throws CannotResolveAppointmentSubscriptionException
     */
    private function getSubscriptionAndServiceType(CustomerModel $customer, Account $account): array
    {
        $subscription = $this->appointmentService->resolveNewAppointmentSubscriptionForCustomer($customer);
        $serviceType = $this->appointmentService->resolveNewAppointmentTypeForCustomer($customer, $subscription);

        if ($subscription === null) {
            $subscription = $this->getNonMosquitoSubscription($customer);
        }

        if ($subscription === null) {
            throw new CannotResolveAppointmentSubscriptionException();
        }

        $lastCompletedAppointment = $this->getLastCompletedAppointment($account);
        $appointmentType = $serviceType->isReservice ? AppointmentType::RESERVICE :
            AppointmentType::fromServiceType($serviceType);
        $subscriptionId = $serviceType->isReservice ? AppointmentModel::RESERVICE_SUBSCRIPTION_ID : $subscription->id;

        if ($lastCompletedAppointment !== null && $lastCompletedAppointment->serviceType && $lastCompletedAppointment->serviceType->isInitial && $lastCompletedAppointment->start && $lastCompletedAppointment->start->diff(new DateTimeImmutable())->days < 20) {
            $appointmentType = AppointmentType::RESERVICE;
            $subscriptionId = AppointmentModel::RESERVICE_SUBSCRIPTION_ID;
        }
        return [$subscriptionId, $appointmentType];
    }

    private function getNonMosquitoSubscription(CustomerModel $customer): SubscriptionModel|null
    {
        return $customer->subscriptions->filter(
            fn (SubscriptionModel $subscription) => $subscription->isActive
                && !in_array(
                    $subscription->serviceType->description,
                    ConfigHelper::getMosquitoServiceTypes()
                )
        )->first();
    }

    /**
     * Retrieves customer appointments based on search criteria.
     *
     * @param int $accountNumber
     * @param string|null $dateStart
     * @param string|null $dateEnd
     * @param AppointmentStatus[]|null $status
     * @throws AccountNotFoundException
     * @throws InternalServerErrorHttpException|ValidationException
     */
    protected function customerHasPendingOrCompletedAppointments(
        int $accountNumber,
        string|null $dateStart = null,
        string|null $dateEnd = null,
        array|null $status = null
    ): bool {

        $account = $this->accountService->getAccountByAccountNumber($accountNumber);

        $searchAppointmentDTO = new SearchAppointmentsDTO(
            officeId: $account->office_id,
            accountNumber: [$account->account_number],
            dateStart: $dateStart,
            dateEnd: $dateEnd,
            status: $status
        );
        $result = $this->appointmentRepository
            ->office($searchAppointmentDTO->officeId)
            ->withRelated(['serviceType'])
            ->search($searchAppointmentDTO);

        return $result->count() > 0;
    }

    /**
     *
     * @param Account $account
     * @return AppointmentModel|null
     */
    private function getLastCompletedAppointment(Account $account): ?AppointmentModel
    {
        $appointments = $this->appointmentRepository
            ->office($account->office_id)
            ->withRelated(['serviceType'])
            ->searchByCustomerId([$account->account_number]);

        $lastCompletedAppointment = $appointments
            ->where('status', AppointmentStatus::Completed)
            ->where('serviceType.description', '!=', AppointmentType::RESERVICE)
            ->sortByDesc('start')
            ->first();

        return $lastCompletedAppointment;
    }
}
