<?php

namespace Tests\Data;

use App\Models\External\PaymentModel;
use App\Repositories\Mappers\PestRoutesPaymentToExternalModelMapper;
use Aptive\PestRoutesSDK\Resources\Payments\Payment;

/**
 * @extends AbstractTestPestRoutesData<Payment, PaymentModel>
 */
class PaymentData extends AbstractTestPestRoutesData
{
    protected static function getSignature(): array
    {
        return [
            'paymentID' => random_int(999999, 99999999),
            'officeID' => 197,
            'customerID' => 2282990,
            'date' => '2022-12-31 23:59:59',
            'paymentMethod' => random_int(0, 4),
            'amount' => random_int(1000, 99999) / 100,
            'appliedAmount' => random_int(1000, 99999) / 100,
            'unassignedAmount' => random_int(1000, 99999) / 100,
            'status' => random_int(0, 2),
            'invoiceIDs' => sprintf('%d,%d', random_int(100000, 9999999), random_int(100000, 9999999)),
            'paymentApplications' => null,
            'employeeID' => random_int(999999, 99999999),
            'officePayment' => '' . random_int(0, 1),
            'collectionPayment' => '' . random_int(0, 1),
            'writeOff' => '' . random_int(0, 1),
            'paymentOrigin' => random_int(0, 4),
            'originalPaymentID' => null,
            'lastFour' => sprintf('%d', random_int(1111, 9999)),
            'notes' => null,
            'batchOpened' => null,
            'batchClosed' => null,
            'paymentSource' => 'API',
        ];
    }

    /**
     * @inheritDoc
     */
    protected static function getRequiredEntityClass(): string
    {
        return Payment::class;
    }

    protected static function getMapperClass(): string
    {
        return PestRoutesPaymentToExternalModelMapper::class;
    }
}
