<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\Request;

class LatestCourses extends Controller
{
    public function latestCourses(){
       return Course::where('status', 'active')->get();
    }
}
