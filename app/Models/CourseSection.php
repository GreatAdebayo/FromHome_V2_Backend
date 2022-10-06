<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseSection extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'section_name',
        'no_of_videos',
        'no_of_files'
        

    ];


    public function course(){
        return  $this->belongsTo(Course::class);
    }

    public function files(){
        return  $this->hasMany(Files::class);
     }
}
