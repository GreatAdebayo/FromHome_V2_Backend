<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\Request;

class RelatedCourses extends Controller
{
    public function relatedCourses(Request $request){
      $course= Course::where('course_code', $request->code)->first();
      return Course::where('category', $course->category)
      ->where('course_code', '!=', $request->code)->get();
     }
}
