<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Withdrawal_Initiated extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'reference',
        'amount_in_kobo',
        'reason',
        'status',
        'transfer_code',
        'created_at',

    ];

   
}
