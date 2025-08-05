<?php

namespace App\Http\Controllers\API\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class UserController extends Controller
{
    private const DATE_PARAM_MAPPING = [
        'updated_before' => 'updated_at',
        'updated_after' => 'updated_at',
        'created_before' => 'created_at',
        'created_after' => 'created_at',
    ];
    private const DATE_COMPARISON_MAPPING = [
        'updated_before' => '<=',
        'updated_after' => '>=',
        'created_before' => '<=',
        'created_after' => '>=',
    ];

    /**
     * @return Collection<int, User>
     */
    public function index(Request $request): Collection
    {
        $withAccount = (bool) $request->input('with_account', false);
        $dates = $request->only(array_keys(self::DATE_PARAM_MAPPING));
        $emailSearch = $request->email_search;
        $afterId = $request->after_id;
        $limit = $request->limit;

        if ($withAccount) {
            $userQuery = User::with('accounts');
        } else {
            $userQuery = User::select('*');
        }

        if (count($dates) > 0) {
            $this->addDateFiltersToQuery($userQuery, $dates);
        }

        if ($emailSearch) {
            $this->addEmailSearchToQuery($userQuery, $emailSearch);
        }

        if ($limit) {
            $userQuery->limit($limit);
        }

        if ($afterId) {
            $userQuery->where('id', '>', $afterId);
        }

        return $userQuery->get();
    }

    /**
     * @param int $id
     * @return Response
     */
    public function destroy(int $id): Response
    {
        $user = User::find($id);

        if (!$user) {
            return response('', Response::HTTP_NOT_FOUND);
        }
        $user->delete();

        return response('', Response::HTTP_NO_CONTENT);
    }

    /**
     * @param Builder<User> $userQuery
     * @param array<string, string> $dates
     */
    private function addDateFiltersToQuery(Builder $userQuery, array $dates): void
    {
        foreach (self::DATE_PARAM_MAPPING as $parameter => $field) {
            if (isset($dates[$parameter])) {
                $userQuery->where(
                    $field,
                    self::DATE_COMPARISON_MAPPING[$parameter],
                    $dates[$parameter]
                );
            }
        }
    }

    /**
     * @param Builder<User> $userQuery
     * @param string $emailSearch
     */
    private function addEmailSearchToQuery(Builder $userQuery, string $emailSearch): void
    {
        $emailString = str_replace('*', '%', $emailSearch);

        $userQuery->where('email', 'like', $emailString);
    }
}
