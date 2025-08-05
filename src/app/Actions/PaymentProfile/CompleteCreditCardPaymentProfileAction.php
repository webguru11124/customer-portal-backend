<?php

declare(strict_types=1);

namespace App\Actions\PaymentProfile;

use App\DTO\CreatePaymentProfileDTO;
use App\Events\PaymentMethod\CcAdded;
use App\Exceptions\PaymentProfile\CreditCardAuthorizationException;
use App\Exceptions\PaymentProfile\PaymentProfileNotFoundException;
use App\Exceptions\PaymentProfile\TransactionSetupAlreadyCompleteException;
use App\Exceptions\TransactionSetup\TransactionSetupNotFoundException;
use App\Models\Account;
use App\Models\TransactionSetup;
use App\Services\CreditCardService;
use App\Services\PaymentProfileService;
use App\Services\TransactionSetupService;
use Aptive\PestRoutesSDK\Resources\PaymentProfiles\PaymentProfilePaymentMethod;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * @final
 */
class CompleteCreditCardPaymentProfileAction
{
    private const PAYMENT_STATUS_COMPLETE = 'Complete';

    public function __construct(
        private readonly CreditCardService $creditCardService,
        private readonly PaymentProfileService $paymentProfileService,
        private readonly TransactionSetupService $transactionSetupService
    ) {
    }

    /**
     * @param Account $account
     * @param string|null $paymentAccountId
     * @param string $paymentStatus
     * @param string $transactionSetupId
     * @return  int ID of payment profile that has been created
     * @throws CreditCardAuthorizationException when payment status is not `Complete` or payment account id is empty
     * @throws PaymentProfileNotFoundException when there is no payment profile with matching merchant id
     * @throws TransactionSetupNotFoundException
     * @throws TransactionSetupAlreadyCompleteException where the transaction setup has already been completed earlier
     */
    public function __invoke(
        Account $account,
        string|null $paymentAccountId,
        string $paymentStatus,
        string $transactionSetupId
    ): int {
        if ($paymentStatus !== self::PAYMENT_STATUS_COMPLETE || empty($paymentAccountId)) {
            throw new CreditCardAuthorizationException();
        }

        if($this->transactionSetupService->transactionSetupIdIsComplete($account->account_number, $transactionSetupId)) {
            throw new TransactionSetupAlreadyCompleteException();
        }

        try {
            $transactionSetup = $this->transactionSetupService->findGeneratedByAccountNumberAndSetupId(
                $account->account_number,
                $transactionSetupId
            );
        } catch (ModelNotFoundException $exception) {
            throw new TransactionSetupNotFoundException(previous: $exception);
        }

        $paymentProfileDto = $this->createPaymentProfileDto($transactionSetup, $paymentAccountId);
        $this->creditCardService->createPaymentProfile($paymentProfileDto);
        $paymentProfile = $this->paymentProfileService->getPaymentProfileByMerchantId($account, $paymentAccountId);

        if ($paymentProfile === null) {
            throw new PaymentProfileNotFoundException();
        }

        $transactionSetup->complete();
        $transactionSetup->save();

        CcAdded::dispatch($transactionSetup->account_number);

        return $paymentProfile->id;
    }

    private function createPaymentProfileDto(
        TransactionSetup $transactionSetup,
        string $paymentAccountId
    ): CreatePaymentProfileDTO {
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
}
