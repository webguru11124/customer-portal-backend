<?php

namespace App\Models\External;

use App\Interfaces\Repository\ExternalRepository;
use App\Interfaces\Repository\PaymentProfileRepository;
use Aptive\PestRoutesSDK\Resources\PaymentProfiles\PaymentProfileAccountType;
use Aptive\PestRoutesSDK\Resources\PaymentProfiles\PaymentProfileCheckType;
use Aptive\PestRoutesSDK\Resources\PaymentProfiles\PaymentProfilePaymentMethod;
use Aptive\PestRoutesSDK\Resources\PaymentProfiles\PaymentProfileStatus;
use Illuminate\Support\Carbon;

/**
 * @property-read bool $isValid
 * @property-read bool $isExpired
 */
class PaymentProfileModel extends AbstractExternalModel
{
    private const VALID_STATUSES = [
        PaymentProfileStatus::Valid,
        PaymentProfileStatus::LastTransactionFailed,
    ];

    public int $id;
    public int $customerId;
    public int $officeId;
    public int $createdBy;
    public string|null $description;
    public \DateTimeInterface|null $dateCreated;
    public PaymentProfileStatus $status;
    public string|null $statusNotes;
    public string|null $billingName;
    public string|null $primaryBillingAddress;
    public string|null $secondaryBillingAddress;
    public string|null $billingCountryCode;
    public string|null $billingCity;
    public string|null $billingState;
    public string|null $billingZip;
    public string|null $billingPhone;
    public string|null $billingEmail;
    public PaymentProfilePaymentMethod $paymentMethod;
    public string $gateway;
    public string|null $merchantId;
    public string|null $merchantToken;
    public string|null $cardLastFour;
    public string|null $cardExpirationMonth;
    public string|null $cardExpirationYear;
    public string|null $cardType;
    public string|null $bankName;
    public string|null $accountNumber;
    public string|null $routingNumber;
    public PaymentProfileCheckType|null $checkType;
    public PaymentProfileAccountType|null $accountType;
    public int $failedAttempts;
    public \DateTimeInterface|null $sentFailureDate;
    public \DateTimeInterface|null $lastAttemptDate;
    public \DateTimeInterface|null $paymentHoldDate;
    public int $retryPoints;
    public string $initialTransactionId;
    public string $lastDeclineType;

    /**
     * @return class-string<ExternalRepository<self>>
     */
    public static function getRepositoryClass(): string
    {
        return PaymentProfileRepository::class;
    }

    public function getIsExpired(): bool|null
    {
        if (empty($this->cardExpirationMonth) || empty($this->cardExpirationYear)) {
            return null;
        }

        $expirationYear = $this->cardExpirationYear;

        if (strlen($expirationYear) === 2) {
            $expirationYear = '20' . $expirationYear;
        }

        $expirationYear = (int) $expirationYear;
        $expirationMonth = (int) $this->cardExpirationMonth;
        $now = Carbon::now();

        return ($expirationYear < $now->year) || ($expirationYear === $now->year && $expirationMonth < $now->month);
    }

    public function getIsValid(): bool
    {
        if (!in_array($this->status, self::VALID_STATUSES)) {
            return false;
        }

        if ($this->paymentMethod === PaymentProfilePaymentMethod::AutoPayACH) {
            return true;
        }

        //@todo review it in CXP-984
        return empty($this->cardExpirationYear) ? true : $this->isExpired === false;
    }

    /**
     * It should be removed after standardization of all responses.
     *
     * @return array<string, mixed>
     */
    public function toOldDataArray(): array
    {
        return [
            'customerID' => $this->customerId,
            'billingAddress1' => $this->primaryBillingAddress,
            'billingAddress2' => $this->secondaryBillingAddress,
            'lastFour' => $this->cardLastFour,
            'merchantID' => $this->merchantId,
            'description' => $this->description,
            'billingName' => $this->billingName,
            'billingCity' => $this->billingCity,
            'billingState' => $this->billingState,
            'billingZip' => $this->billingZip,
            'billingPhone' => $this->billingPhone,
            'billingEmail' => $this->billingEmail,
            'bankName' => $this->bankName,
            'accountNumber' => $this->accountNumber,
            'routingNumber' => $this->routingNumber,
            'paymentMethod' => $this->paymentMethod,
            'accountType' => $this->accountType,
            'checkType' => $this->checkType,
            'status' => $this->status,
            'cardType' => $this->cardType,
            'expMonth' => $this->cardExpirationMonth,
            'expYear' => $this->cardExpirationYear,
            'id' => $this->id,
            'isExpired' => $this->isExpired,
            'isValid' => $this->isValid,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        return [
            'isExpired' => $this->getIsExpired(),
            'isValid' => $this->getIsValid(),
            ];
    }
}
