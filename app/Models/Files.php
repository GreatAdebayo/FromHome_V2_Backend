<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Files extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_sections_id',
        'file_url',
    ];

    public function section(){
     return  $this->belongsTo(CourseSection::class);
    }
}
