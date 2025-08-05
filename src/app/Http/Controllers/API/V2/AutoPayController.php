<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V2;

use App\Http\Controllers\Controller;
use App\Http\Responses\GetAutoPayResponse;
use App\Services\CustomerService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class AutoPayController extends Controller
{
    public function __construct(
        private readonly CustomerService $customerService
    ) {
    }

    public function getAutoPayData(Request $request, int $accountNumber): Response
    {
        $account = $request->user()->getAccountByAccountNumber($accountNumber);
        $customerAutoPayData = $this->customerService->getCustomerAutoPayData($account, true);

        return GetAutoPayResponse::make($request, $customerAutoPayData);
    }
}
