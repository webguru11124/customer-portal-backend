<?php

namespace Tests\Unit\Helpers;

use App\Exceptions\Admin\ApiKeyMissingException;
use App\Helpers\ApiKey;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class ApiKeyTest extends TestCase
{
    private const API_KEYS = [
        'AllowedToListUsers' => [
            'permissions' => 'users.list',
        ],
        'AllowedToDoEverything' => [
            'permissions' => '*',
        ],
        'AllowedToViewOffices' => [
            'permissions' => 'config.offices',
        ],
    ];

    private const POSSIBLE_ROUTE_NAMES = [
        'users.list' => 'api.admin.users.list',
        'users.delete' => 'api.admin.users.delete',
        'config.offices' =>'api.admin.config.offices',
    ];

    public function test_verify_throws_exception_on_invalid_config()
    {
        Config::set('keyauthentication.apiKeys');

        $this->expectException(ApiKeyMissingException::class);

        $apiKeyHelper = new ApiKey();
        $apiKeyHelper->validateKeyPermission('ValidAPIKey1', self::POSSIBLE_ROUTE_NAMES['users.list']);
    }

    /**
     * @dataProvider provideKeyData
     */
    public function test_verify_key_verifies_key($key, $urlName, $result)
    {
        Config::set('keyauthentication.apiKeys', self::API_KEYS);
        $apiKeyHelper = new ApiKey();
        $this->assertEquals($result, $apiKeyHelper->validateKeyPermission($key, $urlName));
    }

    public function provideKeyData(): array
    {
        return [
            [
                'key' => 'AllowedToListUsers',
                'urlName' => self::POSSIBLE_ROUTE_NAMES['users.list'],
                'result' => true,
            ],
            [
                'key' => 'AllowedToListUsers',
                'urlName' => self::POSSIBLE_ROUTE_NAMES['users.delete'],
                'result' => false,
            ],
            [
                'key' => 'AllowedToListUsers',
                'urlName' => self::POSSIBLE_ROUTE_NAMES['config.offices'],
                'result' => false,
            ],
            [
                'key' => 'AllowedToDoEverything',
                'urlName' => self::POSSIBLE_ROUTE_NAMES['users.list'],
                'result' => true,
            ],
            [
                'key' => 'AllowedToDoEverything',
                'urlName' => self::POSSIBLE_ROUTE_NAMES['users.delete'],
                'result' => true,
            ],
            [
                'key' => 'AllowedToDoEverything',
                'urlName' => self::POSSIBLE_ROUTE_NAMES['config.offices'],
                'result' => true,
            ],
            [
                'key' => 'AllowedToViewOffices',
                'urlName' => self::POSSIBLE_ROUTE_NAMES['users.list'],
                'result' => false,
            ],
            [
                'key' => 'AllowedToViewOffices',
                'urlName' => self::POSSIBLE_ROUTE_NAMES['users.delete'],
                'result' => false,
            ],
            [
                'key' => 'AllowedToViewOffices',
                'urlName' => self::POSSIBLE_ROUTE_NAMES['config.offices'],
                'result' => true,
            ],
        ];
    }
}
