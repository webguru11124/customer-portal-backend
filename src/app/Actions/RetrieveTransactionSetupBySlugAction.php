<?php

namespace App\Actions;

use App\Exceptions\TransactionSetup\TransactionSetupException;
use App\Exceptions\TransactionSetup\TransactionSetupExpiredException;
use App\Helpers\FormatHelper;
use App\Interfaces\Repository\CustomerRepository;
use App\Models\External\CustomerModel;
use App\Services\AccountService;
use App\Services\TransactionSetupService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Throwable;

class RetrieveTransactionSetupBySlugAction
{
    public function __construct(
        private AccountService $accountService,
        private TransactionSetupService $transactionSetupService,
        private CustomerRepository $customerRepository
    ) {
    }

    /**
     * Send Customer Notification Action.
     *
     * @param string $slug
     * @return array<string, mixed>
     * @throws TransactionSetupException|TransactionSetupExpiredException
     */
    public function __invoke(string $slug): array
    {
        try {
            $transactionSetup = $this->transactionSetupService->findBySlug($slug);

            if ($transactionSetup->isExpired()) {
                $transactionSetup->setStatusExpired();

                throw new TransactionSetupExpiredException();
            }

            $account = $this->accountService->getAccountByAccountNumber($transactionSetup->account_number);

            /** @var CustomerModel $customer */
            $customer = $this->customerRepository
                ->office($account->office_id)
                ->withRelated(['subscriptions'])
                ->find($account->account_number);

            // This is a temporary solution in order to avoid breaking the response structure
            // The response should be refactored anyway in accordance with JsonApi response
            $responseArray = $transactionSetup->toArray();
            $responseArray['customer'] = [
                'officeID' => $customer->officeId,
                'email' => $customer->email,
                'dueDate' => $customer->getDueDate(),
                'id' => $customer->id,
                'name' => $customer->getFullName(),
                'first_name' => $customer->firstName,
                'last_name' => $customer->lastName,
                'phone_number' => $customer->getFirstPhone(),
                'status_name' => $customer->status->name,
                'office_id' => $customer->officeId,
                'is_phone_number_valid' => FormatHelper::isValidPhone((string) $customer->getFirstPhone()),
                'is_email_valid' => FormatHelper::isValidEmail((string) $customer->email),
                'auto_pay' => (bool) $customer->autoPay->numericValue(),
                'payment_profile_id' => $customer->autoPayPaymentProfileId,
                'balance_cents' => $customer->getBalanceCents(),
            ];

            return $responseArray;
        } catch (ModelNotFoundException|TransactionSetupExpiredException $th) {
            throw $th;
        } catch (Throwable $th) {
            throw new TransactionSetupException(previous: $th);
        }
    }
}
