<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\Request;

class TrendingCourse extends Controller
{
    public function trendingCourses(){
        return Course::where('status', 'active')->get();
     }
}
