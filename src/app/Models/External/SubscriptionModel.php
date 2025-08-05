<?php

declare(strict_types=1);

namespace App\Models\External;

use App\Interfaces\Repository\ExternalRepository;
use App\Interfaces\Repository\SubscriptionRepository;
use App\Repositories\Relations\BelongsTo;
use App\Repositories\Relations\ExternalModelRelation;
use Aptive\PestRoutesSDK\Resources\Subscriptions\SubscriptionDay;
use Aptive\PestRoutesSDK\Resources\Subscriptions\SubscriptionInvoiceCondition;
use Aptive\PestRoutesSDK\Resources\Tickets\Ticket;

/**
 * @property ServiceTypeModel $serviceType
 */
class SubscriptionModel extends AbstractExternalModel
{
    public int $id;
    public int $customerId;
    public int $billToAccountId;
    public int $officeId;
    public int $serviceId;
    public int $billingFrequency;
    public int $frequency;
    public int $followupService;
    public int $agreementLength;
    public int|null $soldById;
    public int $annualRecurringServiceCount;
    public int|null $regionId;
    public int $renewalFrequency;
    public int $duration;
    public int|null $sourceId;
    public int|null $initialStatus;
    public int|null $initialAppointmentId;
    public int|null $addedBy;
    public int|null $soldBy2Id;
    public int|null $soldBy3Id;
    public int|null $preferredTechId;
    public int|null $sentriconSiteId;
    public int|null $lastAppointmentId;
    public int|null $leadId;
    public int|null $leadAddedBy;
    public int|null $leadSourceId;
    public int|null $leadStatus;
    public int|null $leadStageId;
    public int|null $leadAssignedTo;
    public float $initialQuote;
    public float $initialDiscount;
    public float $initialServiceTotal;
    public float $yifDiscount;
    public float $recurringCharge;
    public float $contractValue;
    public float $maxMonthlyCharge;
    public float|null $leadValue;
    public bool $isActive;
    public bool|null $sentriconConnected;
    public string $statusText;
    public string $initialStatusText;
    public string|null $cancellingNotes;
    public string|null $preferredStart;
    public string|null $preferredEnd;
    public string|null $subscriptionLink;
    public string|null $source;
    public string|null $leadStage;
    public string|null $leadLostReason;
    public string|null $leadSource;
    /** @var int[] */
    public array $unitIds;
    /** @var int[]|null */
    public array|null $completedAppointmentIds;
    public \DateTimeInterface $dateAdded;
    public \DateTimeInterface|null $dateUpdated;
    public \DateTimeInterface $nextServiceDate;
    public \DateTimeInterface|null $nextBillingDate;
    public \DateTimeInterface|null $dateCancelled;
    public \DateTimeInterface|null $contractAdded;
    public \DateTimeInterface|null $lastServiceCompleted;
    public \DateTimeInterface|null $leadDateAssigned;
    public \DateTimeInterface|null $leadDateAdded;
    public \DateTimeInterface|null $leadUpdated;
    public \DateTimeInterface|null $leadDateClosed;
    public \DateTimeInterface|null $initialBillingDate;
    public \DateTimeInterface|null $renewalDate;
    public \DateTimeInterface|null $customDate;
    public \DateTimeInterface|null $seasonalStart;
    public \DateTimeInterface|null $seasonalEnd;
    public \DateTimeInterface|null $expirationDate;
    public Ticket|null $recurringTicket;
    public SubscriptionInvoiceCondition $initialInvoice;
    public SubscriptionDay|null $preferredDay;
    /** @var string[] */
    public array $cancellationNotes = [];

    /**
     * @return array<string, ExternalModelRelation>
     */
    public function getRelations(): array
    {
        return [
            'serviceType' => new BelongsTo(ServiceTypeModel::class, 'serviceId'),
        ];
    }

    /**
     * @return class-string<ExternalRepository<self>>
     */
    public static function getRepositoryClass(): string
    {
        return SubscriptionRepository::class;
    }
}
