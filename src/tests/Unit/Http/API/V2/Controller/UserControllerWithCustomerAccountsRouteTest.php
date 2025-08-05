<?php

declare(strict_types=1);

namespace Tests\Unit\Http\API\V2\Controller;

final class UserControllerWithCustomerAccountsRouteTest extends UserControllerTest
{
    protected string $userAccountsRouteName = 'api.v2.customer.accounts';
    protected string $userAccountsRouteURL = '/api/v2/customer/accounts';
}
