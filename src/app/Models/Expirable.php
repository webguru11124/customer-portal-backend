<?php

namespace App\Models;

use App\Enums\Models\TransactionSetupStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * @property TransactionSetupStatus $status
 */
class Expirable extends Model
{
    protected $casts = [
        'status' => TransactionSetupStatus::class,
    ];

    protected $fillable = [
        'status',
        'created_at',
    ];

    public function isExpired(): bool
    {
        $lifetime = config('aptive.transaction_setup_lifetime');

        return Carbon::now()->diffInSeconds(Carbon::parse($this['created_at'])) > $lifetime;
    }

    public function setStatusExpired(): void
    {
        $this->status = TransactionSetupStatus::EXPIRED;
        $this->save();
    }
}
