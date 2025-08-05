<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Tymon\JWTAuth\Contracts\JWTSubject;

/**
 * Methods in this class partially override Authenticatable trait
 * because there are no password and remember token fields in database.
 *
 * @property Collection<Account> $accounts
 */
class User extends Model implements AuthenticatableContract, AuthorizableContract, JWTSubject
{
    use Authenticatable;
    use Authorizable;
    use HasFactory;
    use Notifiable;

    public const AUTH0COLUMN = 'external_id';
    public const FUSIONCOLUMN = 'fusionauth_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'external_id',
        'fusionauth_id',
    ];

    /**
     * Accounts for user.
     *
     * @return HasMany<Account>
     */
    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    public function hasAccountNumber(int $accountNumber): bool
    {
        return $this
            ->accounts
            ->where('account_number', $accountNumber)
            ->isNotEmpty();
    }

    /**
     * @param int $accountNumber
     * @return Account
     * @throws \Illuminate\Support\ItemNotFoundException
     */
    public function getAccountByAccountNumber(int $accountNumber): Account
    {
        return $this
            ->accounts
            ->where('account_number', $accountNumber)
            ->firstOrFail();
    }

    /**
     * Implements Authenticatable interface.
     */
    public function getAuthPassword(): string
    {
        return '';
    }

    /**
     * Implements Authenticatable interface.
     */
    public function getRememberToken(): string
    {
        return '';
    }

    /**
     * Implements Authenticatable interface.
     */
    public function setRememberToken($value): void
    {
    }

    /**
     * Implements Authenticatable interface.
     */
    public function getRememberTokenName(): string
    {
        return '';
    }

    public static function boot()
    {
        parent::boot();

        static::deleting(function ($user) {
            $user->accounts()->delete();
        });
    }

    /**
     * Implements JWTSubject interface.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return 'cp_user';
    }

    /**
     * Implements JWTSubject interface.
     *
     * @return array<string, string>
     */
    public function getJWTCustomClaims()
    {
        return [
            'iss' => \strtolower(config('auth.fusionauth.url')),
            'email' => $this->email,
        ];
    }
}
