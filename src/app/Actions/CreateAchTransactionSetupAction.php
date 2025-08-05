<?php

namespace App\Actions;

use App\DTO\CreatePaymentProfileDTO;
use App\Enums\PestRoutes\PaymentProfile\AccountType;
use App\Enums\PestRoutes\PaymentProfile\CheckType;
use App\Events\PaymentMethod\AchAdded;
use App\Exceptions\Account\AccountFrozenException;
use App\Exceptions\Account\AccountNotFoundException;
use App\Exceptions\PaymentProfile\PaymentProfileIsEmptyException;
use App\Interfaces\Repository\TransactionSetupRepository;
use App\Services\AccountService;
use App\Services\CreditCardService;
use App\Services\TransactionSetupService;
use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use Aptive\PestRoutesSDK\Resources\PaymentProfiles\PaymentProfilePaymentMethod;

class CreateAchTransactionSetupAction
{
    public function __construct(
        public TransactionSetupService $transactionSetupService,
        public AccountService $accountService,
        public CreditCardService $creditCardService,
        public TransactionSetupRepository $transactionSetupRepository,
    ) {
    }

    /**
     * Create Credit Card Transaction Setup Action.
     *
     * @param int $customerId
     * @param string $billing_name
     * @param string $billing_address_line_1
     * @param string $billing_address_line_2
     * @param string $billing_city
     * @param string $billing_state
     * @param string $billing_zip
     * @param string $bank_name
     * @param string $account_number
     * @param string $routing_number
     * @param CheckType $check_type
     * @param AccountType|null $account_type
     * @param bool $auto_pay
     * @return void
     * @throws AccountFrozenException
     * @throws AccountNotFoundException
     * @throws PaymentProfileIsEmptyException
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(
        int $customerId,
        string $billing_name,
        string $billing_address_line_1,
        string $billing_address_line_2,
        string $billing_city,
        string $billing_state,
        string $billing_zip,
        string $bank_name,
        string $account_number,
        string $routing_number,
        CheckType $check_type,
        AccountType|null $account_type,
        bool $auto_pay
    ): void {
        $dto = new CreatePaymentProfileDTO(
            token: null,
            customerId: $customerId,
            paymentMethod: PaymentProfilePaymentMethod::AutoPayACH,
            billingName: $billing_name,
            billingAddressLine1: $billing_address_line_1,
            billingAddressLine2: $billing_address_line_2,
            billingCity: $billing_city,
            billingState: $billing_state,
            billingZip: $billing_zip,
            bankName: $bank_name,
            accountNumber: $account_number,
            routingNumber: $routing_number,
            checkType: $check_type,
            accountType: $account_type,
            auto_pay: $auto_pay,
        );

        $this->creditCardService->createPaymentProfile($dto);

        AchAdded::dispatch($customerId);
    }
}
