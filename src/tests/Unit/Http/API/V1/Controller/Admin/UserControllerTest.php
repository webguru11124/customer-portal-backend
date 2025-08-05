<?php

declare(strict_types=1);

namespace Tests\Unit\Http\API\V1\Controller\Admin;

use App\Models\Account;
use App\Models\User;
use Aptive\Component\Http\HttpStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use Tests\Traits\HasHttpResponses;

final class UserControllerTest extends TestCase
{
    use HasHttpResponses;
    use RefreshDatabase;

    private const UPDATED_DATES = [
        '2023-03-25 12:00:00',
        '2023-03-26 12:00:00',
        '2023-03-27 12:00:00',
        '2023-03-28 12:00:00',
    ];
    private const TEST_DATE = '2023-03-26 10:00:00';
    private const TEST_API_KEY = '1234567';

    private function getAdminUsersRoute(array $params = []): string
    {
        return route('api.admin.users.list', $params);
    }

    public function test_account_is_returned_with_user_if_with_account_flag_true()
    {
        $user = User::factory()->create();
        $user->accounts()->save(Account::factory()->make());

        $retrieved = $this->callAdminUsersEndpoint(['with_account' => true]);

        $this->assertTrue(isset($retrieved[0]['accounts']));
    }

    public function test_it_returns_unauthorized_when_accessing_users_admin_without_a_key()
    {
        $this->getJson($this->getAdminUsersRoute())
            ->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function test_it_returns_unauthorized_when_accessing_users_admin_with_an_incorrect_key()
    {
        $this->setApiKeyConfig();

        $this->withHeader('Authorization', 'Bearer 11111111')
            ->getJson($this->getAdminUsersRoute())
            ->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function test_it_returns_unauthorized_when_deleting_users_without_a_key()
    {
        $url = route('api.admin.users.delete', random_int(1, 99999));

        $this->delete($url)
            ->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function test_it_returns_unauthorized_when_deleting_users_with_an_incorrect_key()
    {
        $this->setApiKeyConfig();
        $url = route('api.admin.users.delete', random_int(1, 99999));

        $this->withHeader('Authorization', 'Bearer 11111111')
            ->delete($url)
            ->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function test_account_is_not_returned_with_user_if_account_flag_false()
    {
        $user = User::factory()->create();
        $user->accounts()->save(Account::factory()->make());

        $retrieved = $this->callAdminUsersEndpoint(['with_account' => false]);

        $this->assertNotTrue(isset($retrieved[0]['accounts']));
    }

    public function test_user_is_returned_when_updated_after_or_equal_requested_created_after_date()
    {
        $this->buildTestUsers(false);

        $retrieved = $this->callAdminUsersEndpoint(['created_after' => self::TEST_DATE]);

        $retrieved->assertJsonCount(3);
    }

    public function test_user_is_returned_when_updated_before_or_equal_requested_created_before_date()
    {
        $this->buildTestUsers(false);

        $retrieved = $this->callAdminUsersEndpoint(['created_before' => self::TEST_DATE]);

        $retrieved->assertJsonCount(1);
    }

    public function test_user_is_returned_when_updated_after_or_equal_requested_updated_after_date()
    {
        $this->buildTestUsers(true);

        $retrieved = $this->callAdminUsersEndpoint(['updated_after' => self::TEST_DATE]);

        $retrieved->assertJsonCount(3);
    }

    public function test_user_is_returned_when_updated_before_or_equal_requested_updated_before_date()
    {
        $this->buildTestUsers(true);

        $retrieved = $this->callAdminUsersEndpoint(['updated_before' => self::TEST_DATE]);

        $retrieved->assertJsonCount(1);
    }

    public function test_user_is_deleted_when_good_id_is_passed()
    {
        $users = $this->buildTestUsers();

        $id = $users[1]->id;

        $url = route('api.admin.users.delete', $id);

        $response = $this->prepApiCall()->delete($url);

        $response->assertStatus(Response::HTTP_NO_CONTENT);
    }

    public function test_404_response_when_bad_id_is_passed()
    {
        $url = route('api.admin.users.delete', 0);

        $response = $this->prepApiCall()->delete($url);

        $response->assertStatus(HttpStatus::NOT_FOUND);
    }

    public function test_all_users_are_retrieved_when_no_parameters_passed()
    {
        $users = $this->buildTestUsers();
        $retrieved = $this->callAdminUsersEndpoint();
        $createdCount = $users->count();
        $retrieved->assertJsonCount($createdCount);
    }

    public function test_it_filters_users_if_email_search_parameter_is_set()
    {
        $users = $this->buildTestUsers();
        $user = $users[0];
        $retrieved = $this->callAdminUsersEndpoint(['email_search' => $user->email]);
        $retrieved->assertJsonCount(1);
        $this->assertTrue($user->email == $retrieved[0]['email']);
    }

    public function test_it_filters_users_if_email_search_parameter_is_set_with_wildcard()
    {
        $users = $this->buildTestUsers();
        $user = $users[0];
        $emailParameter = '*' . substr($user->email, 1, strpos($user->email, '@') + 1) . '*';
        $retrieved = $this->callAdminUsersEndpoint(['email_search' => $emailParameter]);
        $retrieved->assertJsonCount(1);
        $this->assertTrue($user->email == $retrieved[0]['email']);
    }

    public function test_it_returns_users_after_id_if_after_id_parameter_is_passed()
    {
        $users = $this->buildTestUsers();
        $user = $users[2];
        $retrieved = $this->callAdminUsersEndpoint(['after_id' => $user->id]);
        $retrieved->assertJsonCount(1);
        $this->assertGreaterThan($user->id, $retrieved[0]['id']);
    }

    public function test_it_returns_limited_number_of_users_if_limit_parameter_is_passed()
    {
        $this->buildTestUsers();
        $retrieved = $this->callAdminUsersEndpoint(['limit' => 2]);
        $retrieved->assertJsonCount(2);
    }

    private function buildTestUsers(bool $useUpdatedDate = true)
    {
        $updatedDates = self::UPDATED_DATES;
        $users = User::factory(count($updatedDates))->create();

        $users->each(function ($user, $key) use ($updatedDates, $useUpdatedDate) {
            if ($useUpdatedDate) {
                $user->updated_at = $updatedDates[$key];
            } else {
                $user->created_at = $updatedDates[$key];
            }
            $user->save();
        });

        return $users;
    }

    private function prepApiCall($permission = '*')
    {
        $this->setApiKeyConfig($permission);

        return $this->withHeader('Authorization', 'Bearer ' . self::TEST_API_KEY);
    }

    private function callAdminUsersEndpoint(array $params = [], $permission = '*')
    {
        return $this->prepApiCall($permission)->getJson($this->getAdminUsersRoute($params));
    }

    private function setApiKeyConfig($permission = '*')
    {
        Config::set('keyauthentication.apiKeys', [self::TEST_API_KEY => ['permissions' => $permission]]);
    }

    public function test_it_allow_user_list_with_user_list_permission()
    {
        $this->callAdminUsersEndpoint([], 'users.list')
            ->assertStatus(HttpStatus::OK);
    }

    public function test_it_user_list_unauthorized_with_no_user_list_permission()
    {
        $this->callAdminUsersEndpoint([], 'bad.permission')
            ->assertStatus(HttpStatus::UNAUTHORIZED);
    }

    public function test_it_allow_user_delete_with_user_delete_permission()
    {
        $users = $this->buildTestUsers();
        $id = $users[1]->id;
        $url = route('api.admin.users.delete', $id);
        $this->prepApiCall('users.delete')->delete($url)
            ->assertStatus(Response::HTTP_NO_CONTENT);
    }

    public function test_user_delete_unauthorized_with_no_user_delete_permission()
    {
        $users = $this->buildTestUsers();
        $id = $users[1]->id;
        $url = route('api.admin.users.delete', $id);
        $this->prepApiCall('bad.permission')->delete($url)
            ->assertStatus(HttpStatus::UNAUTHORIZED);
    }
}
