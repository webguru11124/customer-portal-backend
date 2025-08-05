<?php

namespace App\Actions;

use App\DTO\CreateTransactionSetupDTO;
use App\Enums\Models\TransactionSetupStatus;
use App\Interfaces\Repository\CustomerRepository;
use App\Interfaces\Repository\TransactionSetupRepository;
use App\Models\External\CustomerModel;
use App\Services\AccountService;
use App\Services\TransactionSetupService;

class CreateCreditCardTransactionSetupAction
{
    public function __construct(
        public TransactionSetupService $transactionSetupService,
        public AccountService $accountService,
        public CustomerRepository $customerRepository,
        public TransactionSetupRepository $transactionSetupRepository,
    ) {
    }

    /**
     * Create Credit Card Transaction Setup Action.
     *
     * @param string $slug
     * @param string $billing_name
     * @param string $billing_address_line_1
     * @param string $billing_address_line_2
     * @param string $billing_city
     * @param string $billing_state
     * @param string $billing_zip
     * @param bool $auto_pay,
     * @return string
     */
    public function __invoke(
        string $slug,
        string $billing_name,
        string $billing_address_line_1,
        string $billing_address_line_2,
        string $billing_city,
        string $billing_state,
        string $billing_zip,
        bool $auto_pay,
    ): string {
        $transactionSetup = $this->transactionSetupService->findBySlug($slug);
        $account = $this->accountService->getAccountByAccountNumber($transactionSetup->account_number);

        /** @var CustomerModel $customer */
        $customer = $this->customerRepository
            ->office($account->office_id)
            ->find($account->account_number);

        $createTransactionSetupDTO = new CreateTransactionSetupDTO(
            slug: $slug,
            officeId: $customer->officeId,
            email: $customer->email,
            phone_number: $customer->getFirstPhone(),
            billing_name: $billing_name,
            billing_address_line_1: $billing_address_line_1,
            billing_address_line_2: $billing_address_line_2,
            billing_city: $billing_city,
            billing_state: $billing_state,
            billing_zip: $billing_zip,
            auto_pay: $auto_pay,
        );

        $transactionSetupId = $this->transactionSetupRepository->create($createTransactionSetupDTO);

        $transactionSetup->update([
            'account_number' => $customer->id,
            'transaction_setup_id' => $transactionSetupId,
            'status' => TransactionSetupStatus::GENERATED,
            'billing_name' => $createTransactionSetupDTO->billing_name,
            'billing_address_line_1' => $createTransactionSetupDTO->billing_address_line_1,
            'billing_address_line_2' => $createTransactionSetupDTO->billing_address_line_2,
            'billing_city' => $createTransactionSetupDTO->billing_city,
            'billing_state' => $createTransactionSetupDTO->billing_state,
            'billing_zip' => $createTransactionSetupDTO->billing_zip,
            'auto_pay' =>  $createTransactionSetupDTO->auto_pay,
        ]);

        return $transactionSetupId;
    }
}
