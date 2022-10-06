<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MyCourse extends Model
{
    use HasFactory;


    protected $fillable = [
        'user_id',
        'course_id',
        'type',     
    ];





    public function user(){
        return  $this->belongsTo(User::class);
    }
}
