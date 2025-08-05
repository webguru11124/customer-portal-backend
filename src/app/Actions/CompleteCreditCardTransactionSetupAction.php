<?php

namespace App\Actions;

use App\DTO\CreatePaymentProfileDTO;
use App\Events\PaymentMethod\CcAdded;
use App\Exceptions\Account\AccountFrozenException;
use App\Exceptions\Account\AccountNotFoundException;
use App\Exceptions\PaymentProfile\CreditCardAuthorizationException;
use App\Exceptions\PaymentProfile\PaymentProfileNotFoundException;
use App\Exceptions\PaymentProfile\PaymentProfilesNotFoundException;
use App\Models\External\PaymentProfileModel;
use App\Models\TransactionSetup;
use App\Services\AccountService;
use App\Services\CreditCardService;
use App\Services\CustomerService;
use App\Services\PaymentProfileService;
use App\Services\TransactionSetupService;
use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use Aptive\PestRoutesSDK\Resources\PaymentProfiles\PaymentProfilePaymentMethod;

class CompleteCreditCardTransactionSetupAction
{
    protected const STATUS_ERROR = 'Error';

    public function __construct(
        public AccountService $accountService,
        public CustomerService $customerService,
        public CreditCardService $creditCardService,
        public PaymentProfileService $paymentProfileService,
        public TransactionSetupService $transactionSetupService,
    ) {
    }

    /**
     * Complete Credit Card Transaction Setup Action.
     *
     * @param string $transactionSetupId
     * @param string $hostedPaymentStatus
     * @param string|null $paymentAccountId
     * @return PaymentProfileModel
     * @throws CreditCardAuthorizationException
     * @throws PaymentProfileNotFoundException
     * @throws PaymentProfilesNotFoundException
     */
    public function __invoke(
        string $transactionSetupId,
        string $hostedPaymentStatus,
        string|null $paymentAccountId
    ): PaymentProfileModel {
        try {
            $transactionSetup = $this->transactionSetupService->findByTransactionSetupId($transactionSetupId);

            if ($hostedPaymentStatus == self::STATUS_ERROR || empty($paymentAccountId)) {
                throw new CreditCardAuthorizationException();
            }

            $dto = $this->createDTO($transactionSetup, $paymentAccountId);
            $this->creditCardService->createPaymentProfile($dto);
            $this->transactionSetupService->complete($transactionSetup);

            $paymentProfile = $this->getPaymentProfile($transactionSetup->account_number, $paymentAccountId);

            CcAdded::dispatch($transactionSetup->account_number);

            return $paymentProfile;
        } catch (CreditCardAuthorizationException $th) {
            $this->transactionSetupService->failAuthorization($transactionSetup);

            throw $th;
        }
    }

    /**
     * @param TransactionSetup $transactionSetup
     * @param string $paymentAccountId
     * @return CreatePaymentProfileDTO
     */
    protected function createDTO(TransactionSetup $transactionSetup, string $paymentAccountId): CreatePaymentProfileDTO
    {
        return CreatePaymentProfileDTO::from([
            'customerId' => $transactionSetup->account_number,
            'paymentMethod' => PaymentProfilePaymentMethod::AutoPayCC,
            'token' => $paymentAccountId,
            'billingName' => $transactionSetup->billing_name,
            'billingAddressLine1' => $transactionSetup->billing_address_line_1,
            'billingAddressLine2' => $transactionSetup->billing_address_line_2,
            'billingCity' => $transactionSetup->billing_city,
            'billingState' => $transactionSetup->billing_state,
            'billingZip' => $transactionSetup->billing_zip,
            'auto_pay' => $transactionSetup->auto_pay,
        ]);
    }

    /**
     * @param int $accountNumber
     * @param string $paymentAccountId
     *
     * @return PaymentProfileModel
     *
     * @throws AccountNotFoundException
     * @throws AccountFrozenException
     * @throws InternalServerErrorHttpException
     * @throws PaymentProfileNotFoundException
     * @throws PaymentProfilesNotFoundException
     */
    protected function getPaymentProfile(int $accountNumber, string $paymentAccountId): PaymentProfileModel
    {
        $account = $this->accountService->getAccountByAccountNumber($accountNumber);

        $paymentProfile = $this->paymentProfileService->getPaymentProfileByMerchantId(
            $account,
            $paymentAccountId
        );

        if (!$paymentProfile) {
            throw new PaymentProfileNotFoundException();
        }

        return $paymentProfile;
    }
}
