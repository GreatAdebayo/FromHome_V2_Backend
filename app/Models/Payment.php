<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'user_id',
        'transaction_id',
        'amount',
        'state'     
    ];


    public function user(){
        return  $this->belongsTo(User::class);
    }

  
}
