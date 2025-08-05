<?php

namespace App\Models\External;

use App\Interfaces\Repository\ExternalRepository;
use App\Interfaces\Repository\PaymentRepository;
use Aptive\PestRoutesSDK\Resources\Payments\PaymentMethod;
use Aptive\PestRoutesSDK\Resources\Payments\PaymentOrigin;
use Aptive\PestRoutesSDK\Resources\Payments\PaymentSource;
use Aptive\PestRoutesSDK\Resources\Payments\PaymentStatus;
use DateTimeInterface;

class PaymentModel extends AbstractExternalModel
{
    public int $id;
    public int $officeId;
    public int $customerId;
    public DateTimeInterface $date;
    public PaymentMethod $paymentMethod;
    public float|null $amount;
    public float|null $appliedAmount;
    public float|null $unassignedAmount;
    public PaymentStatus $status;
    /** @var int[] */
    public array $invoiceIds;
    /** @var int[] Ticket (invoice) IDs this payment is applied to. */
    public array $paymentApplications;
    public int $employeeId;
    public bool $officePayment;
    public bool $collectionPayment;
    public bool $writeOff;
    public PaymentOrigin $paymentOrigin;
    public int|null $originalPaymentId;
    public string|null $lastFour;
    public string|null $notes;
    public DateTimeInterface|null $batchOpened;
    public DateTimeInterface|null $batchClosed;
    public PaymentSource|null $paymentSource;

    /**
     * @return class-string<ExternalRepository<self>>
     */
    public static function getRepositoryClass(): string
    {
        return PaymentRepository::class;
    }
}
