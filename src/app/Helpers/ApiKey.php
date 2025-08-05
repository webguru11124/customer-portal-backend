<?php

namespace App\Helpers;

use App\Exceptions\Admin\ApiKeyMissingException;

class ApiKey
{
    private const BASE_ROUTE_NAME = 'api.admin.';
    /**
     * @var array<string, array{applicationName:string}>
     */
    private array $validKeys;

    public function __construct()
    {
        $this->validKeys = config('keyauthentication.apiKeys', []) ?? [];
    }

    public function validateKeyPermission(string $key, string $routeName): bool
    {
        $keyInfo = $this->getKeyInfo($key);

        if ($keyInfo === null || !isset($keyInfo['permissions'])) {
            return false;
        }
        $permissions = trim($keyInfo['permissions']);
        //The key has permission to everything
        if ($permissions == '*') {
            return true;
        }
        $permissionsArray = explode(',', $permissions);
        $endpoint = str_replace(self::BASE_ROUTE_NAME, '', $routeName);

        return array_search($endpoint, $permissionsArray) !== false;
    }

    /**
     * @return array<string>
     */
    private function getKeyInfo(string $key): array|null
    {
        if (empty($this->validKeys)) {
            throw new ApiKeyMissingException();
        }

        return $this->validKeys[$key] ?? null;
    }
}
