<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $office_id
 * @property int $account_number
 */
class Account extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_number',
        'office_id',
    ];

    protected $hidden = [
        'id',
        'user_id',
        'created_at',
        'updated_at',
    ];
}
