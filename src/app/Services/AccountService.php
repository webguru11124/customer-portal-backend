<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\Account\AccountNotFoundException;
use App\Models\Account;

class AccountService
{
    /**
     * @throws AccountNotFoundException
     */
    public function getAccountByAccountNumber(int $accountNumber): Account
    {
        $account = Account::firstWhere('account_number', $accountNumber);

        if (empty($account)) {
            throw new AccountNotFoundException(__('exceptions.account_not_found', ['id' => $accountNumber]));
        }

        return $account;
    }
}
