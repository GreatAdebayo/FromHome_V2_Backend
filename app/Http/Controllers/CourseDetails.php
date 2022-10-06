<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseNote;
use App\Models\CourseReview;
use App\Models\CourseSection;
use App\Models\Files;
use App\Models\Payment;
use App\Models\Progress;
use App\Models\User;
use App\Models\Videos;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Tymon\JWTAuth\Facades\JWTAuth;

class CourseDetails extends Controller
{
    function  decodeJWT($token)
    {
        $token = JWTAuth::getToken($token);
        $tokenDetails = JWTAuth::getPayload($token)->toArray();
        $user_id = $tokenDetails['sub'];
        return $user_id;
    }

    public function courseDetails(Request $request)
    {
        $res = array('details' => '', 'sections' => '',  'lname' => '', 'no' => '');
        $details = Course::where('course_code', $request->code)->first();
        $sections = CourseSection::where('course_id',  $details->id)->get();
        $tutor = User::where('id', $details->user_id)->first();
        $no_of_students = count(Payment::where('course_id', $details->id)
            ->where('state', 'paid')->get());
        $res['details'] =  $details;
        $res['sections'] =  $sections;
        $res['lname'] =  $tutor->lastname;
        $res['no'] =  $no_of_students;
        return $res;
    }

    public function courseAccess(Request $request)
    {
        $user_id = $this->decodeJWT($request->header('Authorization'));
        $course_id = Course::where('course_code', $request->code)->first()->id;
        return Payment::where('user_id', $user_id)->where('course_id', $course_id)
            ->where('state', 'paid')->first()->state;
    }

    public function coursePreview(Request $request)
    {
        $sec = array('sections' => '', 'title' => '', 'progress' => '');
        $res = array('access' => '', 'section' => '');
        $user_id = $this->decodeJWT($request->token);
        $course_id = Course::where('course_code', $request->code)->first()->id;
        if ($course_id) {
            $access = Payment::where('user_id', $user_id)->where('course_id', $course_id)
                ->where('state', 'paid')->first();
            if ($access) {
                $details = Course::where('course_code', $request->code)->first();
                $sections = CourseSection::where('course_id',  $details->id)->get();
                foreach ($sections as $section) {
                    $videos = Videos::where('course_sections_id', $section->id)->get();
                    $files = Files::where('course_sections_id', $section->id)->get();
                    $section->no_of_videos = $videos;
                    $section->no_of_files = $files;
                };
                $sec['sections'] =  $sections;
                $sec['title'] = $details->course_title;

                //getting progress
                $progress = Progress::where('user_id', $user_id)->where('course_code', $request->code)->get();
                $totalProgress = collect($progress)
                    ->reduce(function ($carry, $item) {
                        return $carry + $item["progress"];
                    }, 0);
                $sec['progress'] = $totalProgress;
                $res['section'] = $sec;
            } else {
                $res['access'] =  'noaccess';
            }

            return $res;
        }
    }

    public function progress(Request $request)
    {
        $user_id = $this->decodeJWT($request->header('Authorization'));
        $course_id = Course::where('course_code', $request->code)->first()->id;
        if ($course_id) {
            $details = Course::where('course_code', $request->code)->first();
            $sections = CourseSection::where('course_id',  $details->id)->get();
            //getting numbers of videos
            $totalVideos = collect($sections)
                ->reduce(function ($carry, $item) {
                    return $carry + $item["no_of_videos"];
                }, 0);
            //updating progress
            Progress::create([
                'user_id' => $user_id,
                'course_code' => $request->code,
                'video' => $request->video,
                'progress' => 100 / $totalVideos
            ]);
        }
    }

    public function courseReview(Request $request)
    {
        return CourseReview::where('course_code', $request->code)
            ->join('users', 'users.id', '=', 'course_reviews.user_id')
            ->select(
                'users.lastname',
                'course_reviews.review',
                'course_reviews.created_at'
            )
            ->get();
    }

   
}
