<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Suggestions;
use Illuminate\Http\Request;

class SearchCourse extends Controller
{
    public function searchCourse(Request $request)
    {
        $search = $request->name;
        $result = Course::where('status', 'active')
        ->where('course_title', 'Like', "%$search%")
        ->orWhere('category', 'Like', "%$search%")->get();

        if (count($result) > 0) {
            return $result;
        } else {
            return [];
        }
    }
    public function searchCategory(Request $request)
    {
        $search = $request->search;
        $result = Course::where('status', 'active')
        ->where('category', $search)->get();
        if (count($result) > 0) {
            return $result;
        } else {
            return [];
        }
    }

    public function suggestions(){
        return Suggestions::get();
    }
}
