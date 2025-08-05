<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\ConfigHelper;
use App\Interfaces\Repository\CustomerRepository;
use App\Interfaces\Repository\OfficeRepository;
use App\Models\Account;
use App\Models\External\CustomerModel;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * @final
 */
class UserService
{
    public function __construct(
        private CustomerRepository $customerRepository,
        private OfficeRepository $officeRepository
    ) {
    }

    public function findUserByEmailAndExtId(
        string $email,
        string $externalId,
        string $idName = User::AUTH0COLUMN,
    ): User|null {
        return User::where('email', $email)
            ->where($idName, $externalId)
            ->first();
    }

    public function createOrUpdateUserWithExternalId(
        string $externalId,
        string $email,
        string $idName = User::AUTH0COLUMN
    ): User|null {
        $customers = $this->customerRepository
            ->office(ConfigHelper::getGlobalOfficeId())
            ->searchActiveCustomersByEmail(
                $email,
                $this->officeRepository->getAllOfficeIds()
            );

        if ($customers->isEmpty()) {
            return null;
        }

        $user = User::updateOrCreate(
            [
                'email' => $email,
            ],
            [
                'first_name' => $customers->first()?->firstName,
                'last_name' => $customers->first()?->lastName,
                $idName => $externalId,
            ]
        );

        $this->updateUserAccounts($user);

        return $user;
    }

    public function syncUserAccounts(User $user): void
    {
        $key = 'ASC_' . $user->id;

        if (Cache::has($key)) {
            return;
        }

        $this->updateUserAccounts($user);
        Cache::put($key, true, ConfigHelper::getAccountSyncCountdown());
    }

    public function updateUserAccounts(User $user): void
    {
        $accounts = array_column($user->accounts->all(), null, 'id');
        $customers = $this->customerRepository
            ->office(ConfigHelper::getGlobalOfficeId())
            ->searchActiveCustomersByEmail(
                $user->email,
                $this->officeRepository->getAllOfficeIds()
            );

        /** @var CustomerModel $customer */
        foreach ($customers as $customer) {
            $account = Account::where([
                ['account_number', '=', $customer->id],
            ])->first();

            if ($account) {
                if ($account->office_id !== $customer->officeId) {
                    $account->update(['office_id' => $customer->officeId]);
                }

                if (array_key_exists($account->id, $accounts)) {
                    unset($accounts[$account->id]);
                }
            } else {
                $account = new Account([
                    'account_number' => $customer->id,
                    'office_id' => $customer->officeId,
                ]);
            }

            $user->accounts()->save($account);
        }

        foreach ($accounts as $account) {
            $account->delete();
        }
        $user->load('accounts');
    }
}
