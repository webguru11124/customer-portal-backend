<?php

declare(strict_types=1);

namespace Tests\Traits;

use Aptive\PestRoutesSDK\Resources\Customers\Customer as PestRoutesCustomer;
use Faker\Factory;
use Faker\Generator;

trait GetPestRoutesCustomer
{
    private static ?Generator $faker;

    protected static function faker(): Generator
    {
        if (!isset(self::$faker)) {
            self::$faker = Factory::create();
        }

        return self::$faker;
    }

    protected static function getPestRoutesCustomer($id, $email, $address, $city, $state, $zip): PestRoutesCustomer
    {
        $data = new class () {
            public function __get(string $name)
            {
                return isset($this->$name) ? $this->$name : null;
            }
        };

        $data->customerID = $id;
        $data->officeID = 1;
        $data->fname = self::faker()->firstName();
        $data->lname = self::faker()->lastName();
        $data->commercialAccount = '0';
        $data->status = 1;
        $data->email = $email;
        $data->address = $address;
        $data->city = $city;
        $data->state = $state;
        $data->zip = $zip;
        $data->billToAccountID = $id;
        $data->billingFName = $data->fname;
        $data->billingLName = $data->lname;
        $data->billingCountryID = 'US';
        $data->dateAdded = '01-01-1980';
        $data->dateCancelled = '01-01-1960';
        $data->dateUpdated = '01-01-1960';
        $data->aPay = 'No';
        $data->paidInFull = '0';
        $data->subscriptionIDs = '';
        $data->smsReminders = '0';
        $data->phoneReminders = '0';
        $data->emailReminders = '0';
        $data->useStructures = '0';
        $data->isMultiUnit = '0';
        $data->appointmentIDs = '';
        $data->ticketIDs = '';
        $data->paymentIDs = '';
        $data->unitIDs = [];

        return PestRoutesCustomer::fromApiObject($data);
    }
}
