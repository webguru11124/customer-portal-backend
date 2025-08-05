<?php

declare(strict_types=1);

namespace App\Actions\Customer;

use App\DTO\Customer\ShowCustomerResultDTO;
use App\DTO\PlanBuilder\CurrentPlanDTO;
use App\DTO\PlanBuilder\Plan;
use App\Exceptions\PaymentProfile\PaymentProfileNotFoundException;
use App\Exceptions\PlanBuilder\FieldNotFound;
use App\Exceptions\Subscription\CanNotDetermineDueSubscription;
use App\Helpers\DateTimeHelper;
use App\Interfaces\Repository\CustomerRepository;
use App\Interfaces\Repository\PaymentProfileRepository;
use App\Models\Account;
use App\Models\External\AppointmentModel;
use App\Models\External\CustomerModel;
use App\Models\External\PaymentProfileModel;
use App\Models\External\SubscriptionModel;
use App\Services\LoggerAwareTrait;
use App\Services\PlanBuilderService;
use App\Utilites\CustomerDutyDeterminer;
use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use Aptive\PestRoutesSDK\Resources\Appointments\AppointmentStatus;
use Aptive\PestRoutesSDK\Resources\Customers\CustomerAutoPay;
use Safe\DateTimeImmutable;

class ShowCustomerAction
{
    use LoggerAwareTrait;

    private SubscriptionModel|null $dueSubscription = null;
    private bool $isDueSubscriptionDetermined = false;

    public function __construct(
        public CustomerRepository $customerRepository,
        public PaymentProfileRepository $paymentProfileRepository,
        public CustomerDutyDeterminer $customerDutyDeterminer,
        public PlanBuilderService $planBuilderService,
    ) {
    }

    /**
     * @throws PaymentProfileNotFoundException
     */
    public function __invoke(Account $account): ShowCustomerResultDTO
    {
        /** @var CustomerModel $customer */
        $customer = $this->customerRepository
            ->office($account->office_id)
            ->withRelated(['subscriptions.serviceType', 'appointments.serviceType'])
            ->find($account->account_number);

        /** @var SubscriptionModel|null $subscription */
        $subscription = $customer->subscriptions->first();

        return new ShowCustomerResultDTO(
            id: $customer->id,
            officeId: $customer->officeId,
            firstName: $customer->firstName,
            lastName: $customer->lastName,
            email: (string) $customer->email,
            phoneNumber: $customer->getFirstPhone(),
            balanceCents: $customer->getBalanceCents(),
            isOnMonthlyBilling: $customer->isOnMonthlyBilling(),
            dueDate: $customer->getDueDate(),
            paymentProfileId: $customer->autoPayPaymentProfileId,
            autoPayProfileLastFour: $this->getCustomerAutoPayProfileLastFour($customer),
            isDueForStandardTreatment: $this->isCustomerDueForStandardTreatment($customer),
            lastTreatmentDate: $this->getLastTreatmentDate($customer),
            status: $customer->status,
            autoPayMethod: $customer->autoPay,
            currentPlan: $subscription ? $this->getCurrentPlanData($customer->officeId, $subscription) : null,
        );
    }

    protected function getDueSubscription(CustomerModel $customer): SubscriptionModel|null
    {
        if ($this->isDueSubscriptionDetermined) {
            return $this->dueSubscription;
        }

        try {
            $this->dueSubscription = $this->customerDutyDeterminer->getSubscriptionCustomerIsDueFor($customer);
        } catch (CanNotDetermineDueSubscription $exception) {
            $this->getLogger()?->info($exception->getMessage());
            $this->dueSubscription = null;
        }

        $this->isDueSubscriptionDetermined = true;

        return $this->dueSubscription;
    }

    protected function isCustomerDueForStandardTreatment(CustomerModel $customer): bool
    {
        return $this->getDueSubscription($customer) !== null;
    }

    /**
     * @throws PaymentProfileNotFoundException
     */
    protected function getCustomerAutoPayProfileLastFour(CustomerModel $customer): string|null
    {
        $isAutoPayOn = $customer->autoPay !== CustomerAutoPay::NotOnAutoPay;

        if ($isAutoPayOn && $customer->autoPayPaymentProfileId !== null) {
            /** @var PaymentProfileModel $paymentProfile */
            $paymentProfile = $this->paymentProfileRepository
                ->office($customer->officeId)
                ->find($customer->autoPayPaymentProfileId);

            return $paymentProfile->cardLastFour;
        }

        return null;
    }

    /**
     * @throws InternalServerErrorHttpException
     */
    protected function getLastTreatmentDate(CustomerModel $customer): string|null
    {
        if ($customer->subscriptions->isEmpty()) {
            return null;
        }

        $appointmentsCollection = $customer->appointments->filter(
            fn (AppointmentModel $appointmentModel) => $appointmentModel->status === AppointmentStatus::Completed
                && !$appointmentModel->isReservice()
        );

        if ($appointmentsCollection->isEmpty()) {
            return null;
        }

        $appointmentsCollection = $appointmentsCollection->sortBy(['start']);
        $subscription = $this->getDueSubscription($customer);

        if ($subscription !== null) {
            $appointmentsCollection = $appointmentsCollection->filter(
                fn (AppointmentModel $appointment) => $appointment->serviceTypeId === $subscription->serviceId
            );
        }

        /** @var AppointmentModel|null $appointment */
        $appointment = $appointmentsCollection->last();

        return $appointment?->start?->format(DateTimeHelper::defaultDateFormat());
    }

    /**
     * @param int $officeId
     * @param SubscriptionModel $subscription
     * @return CurrentPlanDTO
     * @throws \App\Exceptions\PlanBuilder\FieldNotFound
     */
    protected function getCurrentPlanData(int $officeId, SubscriptionModel $subscription): CurrentPlanDTO
    {
        /** @var DateTimeImmutable $dateAdded */
        $dateAdded = $subscription->dateAdded;

        $planProductNames = [];
        $error = false;

        try {
            /** @var Plan $currentPlan */
            $currentPlan = $this->planBuilderService->getServicePlan(
                $subscription->serviceId,
                $officeId
            );
            $products = $this->planBuilderService->getProducts($officeId);
            $areaPlan = $currentPlan->defaultAreaPlan;
            $serviceProductIds = empty($areaPlan) ? [] : $areaPlan->serviceProductIds;

            foreach ($serviceProductIds as $serviceProductId) {
                if (isset($products[$serviceProductId])) {
                    $planProductNames[] = $products[$serviceProductId]->name;
                }
            }
            $planName = $currentPlan->name;
        } catch (FieldNotFound $exception) {
            $planName = '';
            $error = true;
        }

        return new CurrentPlanDTO(
            name: $planName,
            includedProducts: $planProductNames,
            subscriptionStart: $dateAdded,
            subscriptionEnd: $dateAdded,
            error: $error
        );
    }
}
