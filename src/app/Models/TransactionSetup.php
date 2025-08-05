<?php

namespace App\Models;

use App\Enums\Models\TransactionSetupStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TransactionSetup extends Expirable
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'slug',
        'account_number',
        'transaction_setup_id',
        'status',
        'billing_name',
        'billing_address_line_1',
        'billing_address_line_2',
        'billing_city',
        'billing_state',
        'billing_zip',
        'auto_pay',
    ];

    protected $casts = [
        'account_number' => 'integer',
        'status' => TransactionSetupStatus::class,
        'auto_pay' => 'boolean',
    ];

    /**
     * Check the model already has a slug.
     *
     * @return bool
     */
    public function hasSlug(): bool
    {
        return $this->slug !== null;
    }

    public function complete(): void
    {
        $this->status = TransactionSetupStatus::COMPLETE;
    }
}
