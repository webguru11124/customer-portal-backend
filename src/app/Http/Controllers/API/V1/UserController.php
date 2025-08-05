<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\GetAccountsResponse;
use App\Services\CustomerService;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class UserController extends Controller
{
    public function __construct(
        private CustomerService $customerService,
        private readonly UserService $userService,
    ) {
    }

    public function accounts(Request $request): Response
    {
        $user = $request->user();
        $this->userService->updateUserAccounts($user);

        $customers = $this->customerService->getActiveCustomersCollectionForUser($user);

        return GetAccountsResponse::make($request, $customers);
    }
}
