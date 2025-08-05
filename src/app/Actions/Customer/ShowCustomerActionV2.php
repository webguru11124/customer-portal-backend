<?php

declare(strict_types=1);

namespace App\Actions\Customer;

use App\DTO\Customer\ShowCustomerSubscriptionResultDTO;
use App\DTO\Customer\V2\ShowCustomerResultDTO;
use App\DTO\Payment\AchPaymentMethod;
use App\DTO\Payment\BasePaymentMethod;
use App\DTO\Payment\CreditCardPaymentMethod;
use App\DTO\Payment\PaymentMethodsListRequestDTO;
use App\Enums\Models\Payment\PaymentMethod as PaymentMethodEnum;
use App\Exceptions\PaymentProfile\PaymentProfileNotFoundException;
use App\Interfaces\Repository\CustomerRepository;
use App\Interfaces\Repository\PaymentProfileRepository;
use App\Models\Account;
use App\Models\External\CustomerModel;
use App\Models\External\SubscriptionModel;
use App\Repositories\AptivePayment\AptivePaymentRepository;
use App\Services\PlanBuilderService;
use App\Services\SubscriptionUpgradeService;
use App\Utilites\CustomerDutyDeterminer;
use Aptive\PestRoutesSDK\Resources\Customers\CustomerAutoPay;

class ShowCustomerActionV2 extends ShowCustomerAction
{
    public function __construct(
        public CustomerRepository $customerRepository,
        public PaymentProfileRepository $paymentProfileRepository,
        public CustomerDutyDeterminer $customerDutyDeterminer,
        private readonly SubscriptionUpgradeService $subscriptionUpgradeService,
        private readonly AptivePaymentRepository $paymentRepository,
        public PlanBuilderService $planBuilderService,
    ) {
        parent::__construct(
            customerRepository: $customerRepository,
            paymentProfileRepository: $paymentProfileRepository,
            customerDutyDeterminer: $customerDutyDeterminer,
            planBuilderService: $planBuilderService,
        );
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

        $autoPayPaymentProfile = $this->getAutoPayPaymentProfile($customer);

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
            paymentProfileId: $autoPayPaymentProfile?->paymentMethodId,
            //@phpstan-ignore-next-line
            autoPayProfileLastFour: $autoPayPaymentProfile?->isAch() ? $autoPayPaymentProfile->achAccountLastFour : $autoPayPaymentProfile?->ccLastFour,
            isDueForStandardTreatment: $this->isCustomerDueForStandardTreatment($customer),
            lastTreatmentDate: $this->getLastTreatmentDate($customer),
            status: $customer->status,
            autoPayMethod: $this->getAutoPayMethod($autoPayPaymentProfile),
            subscription: $subscription
                ? new ShowCustomerSubscriptionResultDTO(
                    subscription: $subscription,
                    subscriptionUpgradeService: $this->subscriptionUpgradeService
                )
                : null,
            currentPlan: $subscription ? $this->getCurrentPlanData($customer->officeId, $subscription) : null,
        );
    }

    protected function getAutoPayPaymentProfile(CustomerModel $customer): BasePaymentMethod|CreditCardPaymentMethod|AchPaymentMethod|null
    {
        $paymentMethodsList = $this->paymentRepository->getPaymentMethodsList(new PaymentMethodsListRequestDTO(
            customerId: $customer->id
        ));

        if (0 === count($paymentMethodsList)) {
            return null;
        }

        $primaryPaymentProfiles = array_filter(
            $paymentMethodsList,
            static fn (BasePaymentMethod $paymentMethod) => $paymentMethod->isAutoPay
        );

        return 0 !== count($primaryPaymentProfiles) ? current($primaryPaymentProfiles) : null;
    }

    protected function getAutoPayMethod(BasePaymentMethod|null $paymentMethod): CustomerAutoPay
    {
        return match (true) {
            null === $paymentMethod => CustomerAutoPay::NotOnAutoPay,
            PaymentMethodEnum::ACH->value === $paymentMethod->type => CustomerAutoPay::AutoPayACH,
            PaymentMethodEnum::ACH->value !== $paymentMethod->type => CustomerAutoPay::AutoPayCC,
        };
    }
}
