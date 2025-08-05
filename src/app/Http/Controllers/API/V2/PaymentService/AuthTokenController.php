<?php

namespace App\Http\Controllers\API\V2\PaymentService;

use App\DTO\Payment\TokenexAuthKeysRequestDTO;
use App\Helpers\ConfigHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\V2\RetrieveAuthTokenRequest;
use App\Repositories\AptivePayment\AptivePaymentRepository;
use Illuminate\Http\JsonResponse;

class AuthTokenController extends Controller
{
    public function __construct(
        private AptivePaymentRepository $paymentRepo,
        private ConfigHelper $config
    ) {
    }

    public function retrieveToken(RetrieveAuthTokenRequest $request): JsonResponse
    {
        $authTokenDTO = new TokenexAuthKeysRequestDTO(
            $this->config->getPaymentServiceTokenScheme(),
            [$request->origin],
            $request->timestamp
        );

        $response = $this->paymentRepo->getTokenexAuthKeys($authTokenDTO);

        return response()->json(['authentication_key' => $response->authenticationKey]);
    }
}
