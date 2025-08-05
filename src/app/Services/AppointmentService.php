<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\Appointment\CreateAppointmentDTO;
use App\DTO\Appointment\SearchAppointmentsDTO;
use App\DTO\Appointment\UpdateAppointmentDTO;
use App\DTO\Check;
use App\DTO\FlexIVR\Appointment\CreateAppointment as CreateFlexIVRAppointmentDTO;
use App\Exceptions\Appointment\AppointmentCanNotBeCreatedException;
use App\Exceptions\Appointment\AppointmentSubscriptionCanNotBeReassigned;
use App\Exceptions\Entity\EntityNotFoundException;
use App\Exceptions\Subscription\CanNotDetermineDueSubscription;
use App\Helpers\ConfigHelper;
use App\Helpers\DateTimeHelper;
use App\Interfaces\Repository\AppointmentRepository;
use App\Interfaces\Repository\RouteRepository;
use App\Interfaces\Repository\ServiceTypeRepository;
use App\Interfaces\Repository\SpotRepository;
use App\Interfaces\Repository\SubscriptionRepository;
use App\Models\External\AppointmentModel;
use App\Models\External\CustomerModel;
use App\Models\External\RouteModel;
use App\Models\External\ServiceTypeModel;
use App\Models\External\SpotModel;
use App\Models\External\SubscriptionModel;
use App\Utilites\CustomerDutyDeterminer;
use Aptive\PestRoutesSDK\Resources\Appointments\AppointmentStatus;
use Illuminate\Validation\ValidationException;

/**
 * Handles Appointment communication with repositories.
 */
class AppointmentService
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly AppointmentRepository $appointmentRepository,
        private readonly ServiceTypeRepository $serviceTypeRepository,
        private readonly SpotRepository $spotRepository,
        private readonly SubscriptionRepository $subscriptionRepository,
        private readonly CustomerDutyDeterminer $customerDutyDeterminer,
        private readonly RouteRepository $routeRepository,
    ) {
    }

    public function calculateAppointmentDuration(ServiceTypeModel $serviceType): int
    {
        return $serviceType->isReservice
            ? ConfigHelper::getReserviceTreatmentDuration()
            : ConfigHelper::getStandardTreatmentDuration();
    }

    /**
     * @throws AppointmentCanNotBeCreatedException
     * @throws EntityNotFoundException
     */
    public function resolveNewAppointmentTypeForCustomer(
        CustomerModel $customer,
        SubscriptionModel|null $dueSubscription = null
    ): ServiceTypeModel {
        if ($dueSubscription === null) {
            $dueSubscription = $this->resolveNewAppointmentSubscriptionForCustomer($customer);
        }

        /** @var ServiceTypeModel $serviceType */
        $serviceType = $dueSubscription === null
            ? $this->serviceTypeRepository->office($customer->officeId)->find(ConfigHelper::getReserviceTypeId())
            : $dueSubscription->serviceType;

        return $serviceType;
    }

    /**
     * @param CustomerModel $customer
     * @return SubscriptionModel|null
     * @throws AppointmentCanNotBeCreatedException
     */
    public function resolveNewAppointmentSubscriptionForCustomer(CustomerModel $customer): SubscriptionModel|null
    {
        try {
            $dueSubscription = $this->customerDutyDeterminer->getSubscriptionCustomerIsDueFor($customer);
        } catch (CanNotDetermineDueSubscription $exception) {
            $this->getLogger()?->info($exception->getMessage());

            throw new AppointmentCanNotBeCreatedException(previous: $exception);
        }

        return $dueSubscription;
    }

    /**
     * Determines if an appointment can be scheduled.
     * @param CreateAppointmentDTO $createAppointmentDTO
     * @return Check
     */
    public function canCreateAppointment(CreateAppointmentDTO $createAppointmentDTO): Check
    {
        $routeSpotAndDatesCheck = $this->checkRouteSpotAndDates($createAppointmentDTO);
        if ($routeSpotAndDatesCheck->isFalse()) {
            return $routeSpotAndDatesCheck;
        }

        return $this->hasSubscriptionWithoutPendingAppointment($createAppointmentDTO);
    }

    /**
     * Check existing appointments
     *
     * @param CreateAppointmentDTO|CreateFlexIVRAppointmentDTO $createAppointmentDTO
     * @return Check
     */
    public function hasSubscriptionWithoutPendingAppointment(CreateAppointmentDTO | CreateFlexIVRAppointmentDTO $createAppointmentDTO): Check
    {
        $upcomingAppointmentsCollection = $this->appointmentRepository
            ->office($createAppointmentDTO->officeId)
            ->getUpcomingAppointments($createAppointmentDTO->accountNumber);

        $subscriptionsCollection = $this->subscriptionRepository
            ->office($createAppointmentDTO->officeId)
            ->searchByCustomerId([$createAppointmentDTO->accountNumber]);

        if ($subscriptionsCollection->count() <= $upcomingAppointmentsCollection->count()) {
            return Check::false(__('exceptions.has_upcoming_appointment'));
        }

        return Check::true();
    }

    /**
     * Check route spot and dates
     *
     * @param CreateAppointmentDTO $createAppointmentDTO
     * @return Check
     */
    private function checkRouteSpotAndDates(CreateAppointmentDTO $createAppointmentDTO): Check
    {
        if (!empty($createAppointmentDTO->routeId)) {
            /** @var RouteModel $route */
            $route = $this->routeRepository
                ->office($createAppointmentDTO->officeId)
                ->find($createAppointmentDTO->routeId);

            if ($route->isInitial()) {
                return Check::false(__('exceptions.initial_route_schedule_forbidden'));
            }
        }

        if ($createAppointmentDTO->spotId !== null) {
            /** @var SpotModel $spot */
            $spot = $this->spotRepository
                ->office($createAppointmentDTO->officeId)
                ->find($createAppointmentDTO->spotId);
        }

        if (isset($spot) && ($check = $this->canAssignSpotToAppointment($spot))->isFalse()) {
            return $check;
        }

        if (!DateTimeHelper::isFutureDate($createAppointmentDTO->start)
            || !DateTimeHelper::isFutureDate($createAppointmentDTO->end)) {
            return Check::false(__('exceptions.date_expired'));
        }

        return Check::true();
    }

    /**
     * Determines if a given appointment can be rescheduled.
     */
    public function canRescheduleAppointment(AppointmentModel $appointment): Check
    {
        if ($appointment->start !==null && !DateTimeHelper::isFutureDate($appointment->start)) {
            return Check::false(__('exceptions.appointment_expired'));
        }

        return Check::true();
    }

    /**
     * Determines if an appointment can be cancelled.
     */
    public function canCancelAppointment(AppointmentModel $appointment): Check
    {
        if ($appointment->start !== null && !DateTimeHelper::isFutureDate($appointment->start)) {
            return Check::false(__('exceptions.appointment_expired'));
        }

        if (!$appointment->isReservice()) {
            return Check::false(__('exceptions.cancel_only_reservice'));
        }

        return Check::true();
    }

    /**
     * Determines if spot can be assigned to appointment.
     */
    public function canAssignSpotToAppointment(SpotModel $spot): Check
    {
        /** @var RouteModel $route */
        $route = $this->routeRepository
            ->office($spot->officeId)
            ->find($spot->routeId);

        if ($route->isInitial()) {
            return Check::false(__('exceptions.initial_route_schedule_forbidden'));
        }

        if (!DateTimeHelper::isFutureDate($spot->start)) {
            return Check::false(__('exceptions.spot_expired'));
        }

        return Check::true();
    }

    /**
     * @throws ValidationException
     * @throws AppointmentSubscriptionCanNotBeReassigned
     */
    public function reassignSubscriptionToAppointment(
        SubscriptionModel $newSubscription,
        SubscriptionModel $oldSubscription
    ): void {
        // Get all appointments for old subscription (this subscription will be deactivated)
        $appointments = $this->appointmentRepository
            ->office($oldSubscription->officeId)
            ->search(new SearchAppointmentsDTO(
                officeId: $oldSubscription->officeId,
                accountNumber: [$oldSubscription->customerId],
                status: [AppointmentStatus::Pending],
                subscriptionIds: [$oldSubscription->id],
            ));

        if ($appointments->isEmpty()) {
            return;
        }

        foreach ($appointments as $appointment) {
            try {
                // Assign newly activated subscription to appointment
                $this->appointmentRepository
                    ->office($newSubscription->officeId)
                    ->updateAppointment(new UpdateAppointmentDTO(
                        officeId: $newSubscription->officeId,
                        appointmentId: $appointment->id,
                        subscriptionId: $newSubscription->id,
                        typeId: $newSubscription->serviceId,
                    ));
            } catch (\Throwable $exception) {
                throw new AppointmentSubscriptionCanNotBeReassigned(
                    __(
                        'exceptions.cannot_reassign_appointment_subscription',
                        [
                            'appointmentId' => $appointment->id,
                            'subscriptionId' => $newSubscription->id,
                        ]
                    )
                );
            }
        }
    }
}
