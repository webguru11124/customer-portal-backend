<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTO\InitiateTransactionSetupDTO;
use App\Exceptions\Entity\EntityNotFoundException;
use App\Interfaces\Repository\CustomerRepository;
use App\Models\Account;
use App\Models\External\CustomerModel;
use App\Models\TransactionSetup;
use App\Services\TransactionSetupService;

class CreateTransactionSetupAction
{
    public function __construct(
        protected CustomerRepository $customerRepository,
        protected TransactionSetupService $transactionSetupService
    ) {
    }

    /**
     * @throws EntityNotFoundException
     */
    public function __invoke(Account $account): TransactionSetup
    {
        /** @var CustomerModel $customer */
        $customer = $this->customerRepository
            ->office($account->office_id)
            ->find($account->account_number);

        $initiateTransactionSetupDTO = new InitiateTransactionSetupDTO(
            accountNumber: $customer->id,
            email: $customer->email,
            phoneNumber: $customer->getFirstPhone(),
        );

        return $this->transactionSetupService->initiate($initiateTransactionSetupDTO);
    }
}
