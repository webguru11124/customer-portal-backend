<?php

declare(strict_types=1);

namespace App\Actions\PaymentProfile;

use App\DTO\CreateTransactionSetupDTO;
use App\Enums\Models\TransactionSetupStatus;
use App\Interfaces\Repository\CustomerRepository;
use App\Interfaces\Repository\TransactionSetupRepository;
use App\Models\Account;
use App\Models\External\CustomerModel;
use App\Models\TransactionSetup;
use Illuminate\Support\Str;

/**
 * @final
 */
class InitializeCreditCardPaymentProfileAction
{
    public function __construct(
        private readonly CustomerRepository $customerRepository,
        private readonly TransactionSetupRepository $transactionSetupRepository
    ) {
    }

    /**
     * @return string URL to redirect customer to
     */
    public function __invoke(
        string $billingName,
        string $address1,
        string|null $address2,
        string $city,
        string $state,
        string $zip,
        bool $autoPay,
        Account $account
    ): string {
        /** @var CustomerModel $customer */
        $customer = $this->customerRepository
            ->office($account->office_id)
            ->find($account->account_number);

        $dto = $this->buildDto($billingName, $address1, $address2, $city, $state, $zip, $autoPay, $customer);

        $transactionSetupId = $this->transactionSetupRepository->create(
            $dto
        );

        $this->saveBillingData(
            $account->account_number,
            $transactionSetupId,
            $dto
        );

        return $this->getRedirectUri($transactionSetupId);
    }

    private function buildDto(
        string $billingName,
        string $address1,
        string|null $address2,
        string $city,
        string $state,
        string $zip,
        bool $autoPay,
        CustomerModel $customer
    ): CreateTransactionSetupDTO {
        return new CreateTransactionSetupDTO(
            slug: Str::random(CreateTransactionSetupDTO::SLUG_LENGTH),
            officeId: $customer->officeId,
            email: $customer->email,
            phone_number: $customer->getFirstPhone(),
            billing_name: $billingName,
            billing_address_line_1: $address1,
            billing_address_line_2: $address2,
            billing_city: $city,
            billing_state: $state,
            billing_zip: $zip,
            auto_pay: $autoPay
        );
    }

    private function saveBillingData(
        int $accountNumber,
        string $transactionSetupId,
        CreateTransactionSetupDTO $dto
    ): void {
        TransactionSetup::create([
            'slug' => $dto->slug,
            'account_number' => $accountNumber,
            'transaction_setup_id' => $transactionSetupId,
            'status' => TransactionSetupStatus::GENERATED,
            'billing_name' => $dto->billing_name,
            'billing_address_line_1' => $dto->billing_address_line_1,
            'billing_address_line_2' => $dto->billing_address_line_2,
            'billing_city' => $dto->billing_city,
            'billing_state' => $dto->billing_state,
            'billing_zip' => $dto->billing_zip,
            'auto_pay' =>  $dto->auto_pay,
        ]);
    }

    private function getRedirectUri(string $paymentProfileSetupId): string
    {
        $url = config('worldpay.transaction_setup_url');

        return Str::replace('{{TransactionSetupID}}', $paymentProfileSetupId, $url);
    }
}
