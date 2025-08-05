<?php

declare(strict_types=1);

namespace App\Models\External;

use App\Helpers\DateTimeHelper;
use App\Interfaces\Repository\CustomerRepository;
use App\Interfaces\Repository\ExternalRepository;
use App\Repositories\Relations\ExternalModelRelation;
use App\Repositories\Relations\HasMany;
use Aptive\PestRoutesSDK\Resources\Customers\CustomerAddress;
use Aptive\PestRoutesSDK\Resources\Customers\CustomerAutoPay;
use Aptive\PestRoutesSDK\Resources\Customers\CustomerBillingInformation;
use Aptive\PestRoutesSDK\Resources\Customers\CustomerPhone;
use Aptive\PestRoutesSDK\Resources\Customers\CustomerSource;
use Aptive\PestRoutesSDK\Resources\Customers\CustomerStatus;
use Illuminate\Support\Collection;

/**
 * @property Collection<int, SubscriptionModel> $subscriptions
 * @property Collection<int, AppointmentModel> $appointments
 * @property Collection<int, PaymentProfileModel> $paymentProfiles
 */
class CustomerModel extends AbstractExternalModel
{
    private const MONTHLY_BILLING_FREQUENCY = 30;

    public int $id;
    public int $officeId;
    public string $firstName;
    public string $lastName;
    public string|null $companyName;
    public string|null $spouse;
    public bool $isCommercial;
    public CustomerStatus $status;
    public string|null $email;
    /** @var CustomerPhone[] */
    public array $phones;
    public CustomerAddress $address;
    public CustomerBillingInformation $billingInformation;
    public float $latitude;
    public float $longitude;
    public int $squareFeet;
    public int|null $addedBy;
    public \DateTimeInterface $dateAdded;
    public \DateTimeInterface|null $dateCancelled;
    public \DateTimeInterface|null $dateUpdated;
    public CustomerSource|null $source;
    public CustomerAutoPay $autoPay;
    public int|null $preferredTechId;
    public bool $paidInAdvance;
    /** @var string[] */
    public array $subscriptionIds;
    public float $balance;
    public int $balanceAge;
    public float $responsibleBalance;
    public int $responsibleBalanceAge;
    public string|null $customerLink;
    public int|null $masterAccountId;
    public int $preferredDayForBilling;
    public \DateTimeInterface|null $paymentHoldDate;
    public string|null $mostRecentCardLastFour;
    public string|null $mostRecentCardExpirationDate;
    public int|null $regionId;
    public string|null $mapCode;
    public string|null $mapPage;
    public string|null $specialScheduling;
    public float $taxRate;
    public bool $smsReminders;
    public bool $phoneReminders;
    public bool $emailReminders;
    public CustomerSource|null $customerSource;
    public float $maxMonthlyCharge;
    public string|null $county;
    public bool $useStructures;
    public bool $isMultiUnit;
    public int|null $autoPayPaymentProfileId;
    public int|null $divisionId;
    public string|null $portalLogin;
    public \DateTimeInterface|null $portalLoginExpires;

    /**
     * @return array<string, ExternalModelRelation>
     */
    public function getRelations(): array
    {
        return [
            'subscriptions' => new HasMany(SubscriptionModel::class, 'customerId'),
            'appointments' => new HasMany(AppointmentModel::class, 'customerId'),
            'paymentProfiles' => new HasMany(PaymentProfileModel::class, 'customerId'),
        ];
    }

    /**
     * @return class-string<ExternalRepository<self>>
     */
    public static function getRepositoryClass(): string
    {
        return CustomerRepository::class;
    }

    public function getFirstPhone(): string|null
    {
        return !empty($this->phones[0]) ? $this->phones[0]->phone : null;
    }

    public function getSecondPhone(): string|null
    {
        return !empty($this->phones[1]) ? $this->phones[1]->phone : null;
    }

    public function getBalanceCents(): int
    {
        return (int) round($this->responsibleBalance * 100);
    }

    public function isOnMonthlyBilling(): bool
    {
        $monthlyBillingSubscriptions = $this->subscriptions->filter(
            fn (SubscriptionModel $subscription) => $subscription->billingFrequency === self::MONTHLY_BILLING_FREQUENCY
        );

        return $monthlyBillingSubscriptions->count() > 0;
    }

    public function getDueDate(): string|null
    {
        if ($this->subscriptions->isEmpty()) {
            return null;
        }

        /** @var SubscriptionModel $nextServiceSubscription */
        $nextServiceSubscription = $this->subscriptions->sortBy(['nextServiceDate'])->first();

        return $nextServiceSubscription->nextServiceDate->format(DateTimeHelper::defaultDateFormat());
    }

    public function getFullName(): string
    {
        return $this->firstName . ' ' . $this->lastName;
    }
}
