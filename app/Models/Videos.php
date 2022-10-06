<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Videos extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_sections_id',
        'video_name',
        'video_url',
        'play_time'
    ];

    public function section(){
     return  $this->belongsTo(CourseSection::class);
    }
}
