<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Progress extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'course_code',
        'video',
        'progress'
           
    ];


    
    public function user(){
        return  $this->belongsTo(User::class);
    }
}
