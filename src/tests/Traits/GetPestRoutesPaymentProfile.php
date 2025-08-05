<?php

namespace Tests\Traits;

use Aptive\PestRoutesSDK\Resources\PaymentProfiles\PaymentProfile;
use Aptive\PestRoutesSDK\Resources\PaymentProfiles\PaymentProfilePaymentMethod;
use Aptive\PestRoutesSDK\Resources\PaymentProfiles\PaymentProfileStatus;

trait GetPestRoutesPaymentProfile
{
    private function getPestRoutesPaymentProfile(
        ?int $paymentProfileId = null,
        ?int $accountNumber = null,
        ?string $billingName = null
    ): PaymentProfile {
        return new PaymentProfile(
            id: $paymentProfileId ?? random_int(1, 100),
            customerId: $accountNumber ?? random_int(1, 100),
            officeId: random_int(1, 100),
            createdBy: random_int(1, 100),
            description: null,
            dateCreated: new \DateTimeImmutable(),
            dateUpdated: new \DateTimeImmutable(),
            status: PaymentProfileStatus::Valid,
            statusNotes: null,
            billingName: $billingName,
            primaryBillingAddress: null,
            secondaryBillingAddress: null,
            billingCountryCode: null,
            billingCity: null,
            billingState: null,
            billingZip: null,
            billingPhone: null,
            billingEmail: null,
            paymentMethod: PaymentProfilePaymentMethod::AutoPayCC,
            gateway: 'A',
            merchantId: null,
            merchantToken: null,
            cardLastFour: null,
            cardExpirationMonth: '12',
            cardExpirationYear: '24',
            cardType: null,
            bankName: null,
            accountNumber: null,
            routingNumber: null,
            checkType: null,
            accountType: null,
            failedAttempts: 0,
            sentFailureDate: null,
            lastAttemptDate: null,
            paymentHoldDate: null,
            retryPoints: 0,
            initialTransactionId: 'B',
            lastDeclineType: 'C'
        );
    }

    private function getPestRoutesPaymentProfiles($profilesData = [])
    {
        if (empty($profilesData)) {
            $profile = $this->getPestRoutesPaymentProfile();

            return [$profile->id => $profile];
        }
        $profiles = [];

        foreach ($profilesData as $profileData) {
            $profile = $this->getPestRoutesPaymentProfile(
                $profileData['paymentProfileId'] ?? null,
                $profileData['accountNumber'] ?? null,
                $profileData['billingName'] ?? null
            );
            $profiles[$profile->id] = $profile;
        }

        return $profiles;
    }
}
