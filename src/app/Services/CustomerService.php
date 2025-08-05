<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\Customer\AutoPayResponseDTO;
use App\DTO\Customer\SearchCustomersDTO;
use App\DTO\Customer\UpdateCommunicationPreferencesDTO;
use App\DTO\Payment\AchPaymentMethod;
use App\DTO\Payment\BasePaymentMethod;
use App\DTO\Payment\CreditCardPaymentMethod;
use App\DTO\Payment\PaymentMethodsListRequestDTO;
use App\Exceptions\Entity\EntityNotFoundException;
use App\Exceptions\PaymentProfile\PaymentProfileNotFoundException;
use App\Helpers\ConfigHelper;
use App\Interfaces\Repository\CustomerRepository;
use App\Interfaces\Repository\OfficeRepository;
use App\Interfaces\Repository\PaymentProfileRepository;
use App\Models\Account;
use App\Models\External\CustomerModel;
use App\Models\External\PaymentProfileModel;
use App\Models\External\SubscriptionModel;
use App\Models\User;
use App\Repositories\AptivePayment\AptivePaymentRepository;
use Aptive\PestRoutesSDK\Resources\Customers\CustomerAutoPay;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class CustomerService
{
    use LoggerAwareTrait;

    public function __construct(
        public CustomerRepository $customerRepository,
        public PaymentProfileRepository $paymentProfileRepository,
        public OfficeRepository $officeRepository,
        public AptivePaymentRepository $aptivePaymentRepository,
    ) {
    }

    public function updateCommunicationPreferences(UpdateCommunicationPreferencesDTO $dto): int
    {
        return $this->customerRepository->updateCustomerCommunicationPreferences($dto);
    }

    /**
     * @return AutoPayResponseDTO[]
     * @throws PaymentProfileNotFoundException
     */
    public function getCustomerAutoPayData(Account $account, bool $usePaymentServiceApi = false): array
    {
        /** @var CustomerModel $customer */
        $customer = $this->customerRepository
            ->office($account->office_id)
            ->withRelated(['subscriptions.serviceType'])
            ->find($account->account_number);

        return $this->buildAutoPayData($customer, $usePaymentServiceApi);
    }

    /**
     * @return AutoPayResponseDTO[]
     *
     * @throws \JsonException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws EntityNotFoundException
     */
    private function buildAutoPayData(CustomerModel $customer, bool $usePaymentServiceApi = false): array
    {
        $preferredBillingDate = self::getPreferredBillingDate($customer->preferredDayForBilling);

        if ($usePaymentServiceApi) {
            return $this->buildAutoPayDataFromPaymentService($customer, $preferredBillingDate);
        }

        return $this->buildAutoPayDataFromPestRoutes($customer, $preferredBillingDate);
    }

    /**
     * @return AutoPayResponseDTO[]
     * @throws EntityNotFoundException
     */
    private function buildAutoPayDataFromPestRoutes(CustomerModel $customer, string|null $preferredBillingDate): array
    {
        if ($customer->autoPay === CustomerAutoPay::NotOnAutoPay || empty($customer->autoPayPaymentProfileId)) {
            return [new AutoPayResponseDTO($customer->id, false)];
        }

        /** @var PaymentProfileModel $paymentProfile */
        $paymentProfile = $this->paymentProfileRepository
            ->office($customer->officeId)
            ->find($customer->autoPayPaymentProfileId);

        $activeSubscriptions = $this->getActiveSubscriptions($customer);

        return $this->getSubscriptionData($activeSubscriptions, $paymentProfile->cardType, $paymentProfile->cardLastFour, $preferredBillingDate);
    }

    /**
     * @return AutoPayResponseDTO[]
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \JsonException
     */
    private function buildAutoPayDataFromPaymentService(CustomerModel $customer, string|null $preferredBillingDate): array
    {
        $paymentProfiles = $this->aptivePaymentRepository
            ->getPaymentMethodsList(new PaymentMethodsListRequestDTO($customer->id));

        /** @var BasePaymentMethod|CreditCardPaymentMethod|AchPaymentMethod|null $paymentProfile */
        $paymentProfile = collect($paymentProfiles)
            ->filter(fn (BasePaymentMethod $paymentProfile) => $paymentProfile->isAutoPay)
            ->first();

        if (is_null($paymentProfile)) {
            return [new AutoPayResponseDTO($customer->id, false)];
        }

        $activeSubscriptions = $this->getActiveSubscriptions($customer);

        return $this->getSubscriptionData(
            $activeSubscriptions,
            $paymentProfile->type,
            //@phpstan-ignore-next-line
            $paymentProfile->isAch() ? $paymentProfile->achAccountLastFour : $paymentProfile->ccLastFour,
            $preferredBillingDate
        );
    }

    /**
     * @param CustomerModel $customer
     * @return Collection<int, SubscriptionModel>
     */
    private function getActiveSubscriptions(CustomerModel $customer): Collection
    {
        return $customer->subscriptions
            ->filter(fn (SubscriptionModel $subscription) => $subscription->isActive);
    }

    /**
     * @param Collection<int, SubscriptionModel> $subscriptions
     * @return AutoPayResponseDTO[]
     */
    private function getSubscriptionData(Collection $subscriptions, string|null $cardType, string|null $ccLastFour, string|null $preferredBillingDate): array
    {
        $subscriptionData = [];

        foreach ($subscriptions as $subscription) {
            $subscriptionData[] = new AutoPayResponseDTO(
                id: $subscription->id,
                isEnabled: true,
                planName: $subscription->serviceType->description,
                nextPaymentAmount: $subscription->recurringCharge,
                nextPaymentDate: $subscription->nextBillingDate,
                cardType: $cardType,
                cardLastFour: $ccLastFour,
                preferredBillingDate: $preferredBillingDate
            );
        }

        return $subscriptionData;
    }

    /**
     * @param User $user
     *
     * @return Collection<int, CustomerModel>
     */
    public function getActiveCustomersCollectionForUser(User $user): Collection
    {
        $accountNumbers = $user->accounts->map(fn (Account $account) => $account->account_number)->toArray();

        if (empty($accountNumbers)) {
            return new Collection([]);
        }

        $officeIds = $user->accounts
            ->map(fn (Account $account) => $account->office_id)
            ->unique()
            ->toArray();

        $searchCustomersDto = new SearchCustomersDTO(
            officeIds: $officeIds,
            accountNumbers: $accountNumbers,
            isActive: true,
        );

        /** @var Collection<int, CustomerModel> $result */
        $result = $this->customerRepository
            ->office(ConfigHelper::getGlobalOfficeId())
            ->search($searchCustomersDto);

        return $result;
    }

    public function isCustomerWithGivenEmailExists(string $email): bool
    {
        return $this->customerRepository
            ->office(ConfigHelper::getGlobalOfficeId())
            ->searchActiveCustomersByEmail(
                $email,
                $this->officeRepository->getAllOfficeIds()
            )->isNotEmpty();
    }

    private static function getPreferredBillingDate(int $preferredDayForBilling): string|null
    {
        if ($preferredDayForBilling <= 0) {
            return null;
        }

        if ($preferredDayForBilling > 31) {
            throw new InvalidArgumentException('Preferred billing day is invalid');
        }

        $billingDate = Carbon::now();
        $isNextMonth = $billingDate->day > $preferredDayForBilling;
        $billingDate->day = 1;

        if ($isNextMonth) {
            $billingDate->addMonth();
        }

        $billingDate->day = min($preferredDayForBilling, $billingDate->daysInMonth);

        return $billingDate->format('F jS');
    }
}
