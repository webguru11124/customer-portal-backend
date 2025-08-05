<?php

declare(strict_types=1);

namespace Tests\Data;

use App\Models\External\PaymentProfileModel;
use App\Repositories\Mappers\PestRoutesPaymentProfileToExternalModelMapper;
use Aptive\PestRoutesSDK\Resources\PaymentProfiles\PaymentProfile;

/**
 * @extends AbstractTestPestRoutesData<PaymentProfile, PaymentProfileModel>
 */
final class PaymentProfileData extends AbstractTestPestRoutesData
{
    protected static function getSignature(): array
    {
        return [
            'paymentProfileID' => random_int(827367, PHP_INT_MAX),
            'customerID' => random_int(7367, PHP_INT_MAX),
            'billToAccountID' => random_int(82767, PHP_INT_MAX),
            'officeID' => random_int(1, 199),
            'createdBy' => '9977997',
            'description' => '',
            'dateCreated' => '2022-03-31T10:11:12Z',
            'dateUpdated' => '2022-03-31T10:11:12Z',
            'status' => '1',
            'statusNotes' => '',
            'billingName' => '',
            'billingAddress1' => '',
            'billingAddress2' => '',
            'billingCountryID' => '',
            'billingCity' => '',
            'billingState' => '',
            'billingZip' => '',
            'billingPhone' => '',
            'billingEmail' => '',
            'paymentMethod' => '1',
            'gateway' => 'stripe',
            'merchantID' => '',
            'merchantToken' => '',
            'lastFour' => '1111',
            'expMonth' => '',
            'expYear' => '',
            'cardType' => 'Visa',
            'bankName' => '',
            'accountNumber' => '',
            'routingNumber' => '',
            'checkType' => '1',
            'accountType' => '1',
            'failedAttempts' => '0',
            'sentFailureDate' => '2022-03-31T10:11:12Z',
            'lastAttemptDate' => '2022-03-31T10:11:12Z',
            'paymentHoldDate' => '2022-03-31T10:11:12Z',
            'retryPoints' => '0',
            'initialTransactionID' => '19987679',
            'lastDeclineType' => '',
        ];
    }

    protected static function getRequiredEntityClass(): string
    {
        return PaymentProfile::class;
    }

    protected static function getMapperClass(): string
    {
        return PestRoutesPaymentProfileToExternalModelMapper::class;
    }
}
