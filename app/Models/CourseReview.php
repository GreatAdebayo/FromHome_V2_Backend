<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseReview extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'course_code',
        'review',     
    ];



    public function user(){
        return  $this->belongsTo(User::class);
    }
}
