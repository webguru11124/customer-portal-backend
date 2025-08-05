<?php

namespace App\Http\Controllers\API\V1;

use App\Actions\CompleteCreditCardTransactionSetupAction;
use App\Actions\CreateCreditCardTransactionSetupAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\TransactionSetupCompleteCreditCardRequest;
use App\Http\Requests\TransactionSetupCreateCreditCardRequest;
use App\Services\LogService;
use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Throwable;

class CreditCardTransactionSetupController extends Controller
{
    public function __construct(
        public CreateCreditCardTransactionSetupAction $createCreditCardTransactionSetupAction,
        public CompleteCreditCardTransactionSetupAction $completeCreditCardTransactionSetupAction,
        public LogService $logService
    ) {
    }

    /**
     * Create a credit card transaction setup.
     *
     * @param TransactionSetupCreateCreditCardRequest $request
     * @param string $slug
     *
     * @return JsonResponse|null
     */
    public function store(TransactionSetupCreateCreditCardRequest $request, string $slug): JsonResponse|null
    {
        $transactionSetupID = ($this->createCreditCardTransactionSetupAction)(
            slug: $slug,
            billing_name: $request->billing_name,
            billing_address_line_1: $request->billing_address_line_1,
            billing_address_line_2: $request->billing_address_line_2 ?? '',
            billing_city: $request->billing_city,
            billing_state: $request->billing_state,
            billing_zip: $request->billing_zip,
            auto_pay: (bool) $request->auto_pay,
        );

        return response()->json(['url' => $this->getRedirectUrl($transactionSetupID)]);
    }

    /**
     * @param TransactionSetupCompleteCreditCardRequest $request
     * @param string $transactionSetupId
     *
     * @return array<string, mixed>
     *
     * @throws \App\Exceptions\PaymentProfile\CreditCardAuthorizationException
     * @throws \App\Exceptions\PaymentProfile\PaymentProfileNotFoundException
     * @throws \App\Exceptions\PaymentProfile\PaymentProfilesNotFoundException
     */
    public function complete(TransactionSetupCompleteCreditCardRequest $request, string $transactionSetupId): array
    {
        try {
            return ($this->completeCreditCardTransactionSetupAction)(
                transactionSetupId: $transactionSetupId,
                hostedPaymentStatus: $request->get('HostedPaymentStatus'),
                paymentAccountId: $request->get('PaymentAccountID'),
            )->toOldDataArray();
        } catch (Throwable $exception) {
            throw new InternalServerErrorHttpException(previous: $exception);
        }
    }

    /**
     * Replace transaction setup id into the url.
     *
     * @param string $transactionSetupID
     *
     * @return string
     */
    protected function getRedirectUrl(string $transactionSetupID): string
    {
        $url = config('worldpay.transaction_setup_url');

        return Str::replace('{{TransactionSetupID}}', $transactionSetupID, $url);
    }
}
