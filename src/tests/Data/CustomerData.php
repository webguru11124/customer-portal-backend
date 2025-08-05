<?php

namespace Tests\Data;

use App\Models\External\CustomerModel;
use App\Repositories\Mappers\PestRoutesCustomerToExternalModelMapper;
use Aptive\PestRoutesSDK\Resources\Customers\Customer;

/**
 * @extends AbstractTestPestRoutesData<Customer, CustomerModel>
 */
class CustomerData extends AbstractTestPestRoutesData
{
    protected static function getSignature(): array
    {
        return [
            'customerID' => random_int(10000, 99999),
            'billToAccountID' => '2550130',
            'officeID' => '1',
            'fname' => 'Liza',
            'lname' => 'Test QA 2',
            'companyName' => '',
            'spouse' => null,
            'commercialAccount' => '0',
            'status' => '1',
            'statusText' => 'Active',
            'email' => 'liz.test+22@yandex.by',
            'phone1' => '4555555555',
            'ext1' => '',
            'phone2' => '',
            'ext2' => '',
            'address' => '56783',
            'city' => 'Atlanta',
            'state' => 'ID',
            'zip' => '30044',
            'billingCompanyName' => '',
            'billingFName' => 'Liza',
            'billingLName' => 'Test QA 2',
            'billingCountryID' => 'US',
            'billingAddress' => '56783',
            'billingCity' => 'Atlanta',
            'billingState' => 'ID',
            'billingZip' => '30044',
            'billingPhone' => '4555555555',
            'billingEmail' => 'liz.test+22@yandex.by',
            'lat' => '30.886574',
            'lng' => '-97.581592',
            'squareFeet' => '0',
            'addedByID' => '423777',
            'dateAdded' => '2022-04-13 06:06:47',
            'dateCancelled' => '0000-00-00 00:00:00',
            'dateUpdated' => '2022-05-20 00:41:01',
            'sourceID' => '1108',
            'source' => '*SALES REP',
            'aPay' => 'CC',
            'preferredTechID' => '0',
            'paidInFull' => '0',
            'subscriptionIDs' => '2622547',
            'subscriptions' => [],
            'balance' => '399.00',
            'balanceAge' => '114',
            'responsibleBalance' => '399.00',
            'responsibleBalanceAge' => '114',
            'customerLink' => '2406241',
            'masterAccount' => '0',
            'preferredBillingDate' => '-1',
            'paymentHoldDate' => '0000-00-00 00:00:00',
            'mostRecentCreditCardLastFour' => null,
            'mostRecentCreditCardExpirationDate' => null,
            'regionID' => '0',
            'mapCode' => '',
            'mapPage' => '',
            'specialScheduling' => '',
            'taxRate' => '0.000000',
            'smsReminders' => '0',
            'phoneReminders' => '0',
            'emailReminders' => '1',
            'customerSource' => '*SALES REP',
            'customerSourceID' => '1108',
            'maxMonthlyCharge' => '0.00',
            'county' => '',
            'useStructures' => '0',
            'isMultiUnit' => '0',
            'autoPayPaymentProfileID' => '4566218',
            'divisionID' => '0',
            'appointmentIDs' => '22375878,22376064,22376248,22383209',
            'ticketIDs' => '21918005',
            'paymentIDs' => null,
            'unitIDs' => [],
            'additionalContacts' => [],
            'portalLogin' => null,
            'portalLoginExpires' => null,
        ];
    }

    protected static function getRequiredEntityClass(): string
    {
        return Customer::class;
    }

    protected static function getMapperClass(): string
    {
        return PestRoutesCustomerToExternalModelMapper::class;
    }
}
