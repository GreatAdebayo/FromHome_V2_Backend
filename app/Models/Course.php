<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'course_code',
        'category',
        'course_title',
        'course_cost',
        'description',
        'status'     
    ];

    public function user(){
        return  $this->belongsTo(User::class);
    }

    public function section(){
        return  $this->hasMany(CourseSection::class);
     }
}
