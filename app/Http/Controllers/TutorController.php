<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseReview;
use App\Models\CourseSection;
use App\Models\Files;
use App\Models\Payment;
use App\Models\Suggestions;
use App\Models\User;
use App\Models\Videos;
use App\Models\Withdrawal_Initiated;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Owenoj\LaravelGetId3\GetId3;
use Cloudinary\Api\Upload\UploadApi;

class TutorController extends Controller
{


  function  decodeJWT($token)
  {
    $token = JWTAuth::getToken($token);
    $tokenDetails = JWTAuth::getPayload($token)->toArray();
    $user_id = $tokenDetails['sub'];
    return $user_id;
  }

  function generateRandomCode()
  {
    $chars = "abcdefghijkmnopqrstuvwxyz023456789";
    srand((float)microtime() * 1000000);
    $i = 0;
    $code = '';

    while ($i <= 7) {
      $num = rand() % 33;
      $tmp = substr($chars, $num, 1);
      $code = $code . $tmp;
      $i++;
    }

    return $code;
  }

  public function postCourse(Request $request)
  {
    $sectionName  = '';
    $user_id = $this->decodeJWT($request->token);
    $checkForPendingCourse = Course::where('user_id', $user_id)->where('status', 'pending')->first();
    if ($checkForPendingCourse) {
      return 'pending';
    } else {
      //Uploading course basic details
      $createCourse = Course::create([
        'user_id' => $user_id,
        'course_code' => $this->generateRandomCode(),
        'category' => $request->category,
        'course_title' => $request->title,
        'course_cost' => $request->cost,
        'description' => $request->desc,
        'status' => "pending"
      ]);

      $courseId = $createCourse->id;

      //Uploading course sections
      foreach ($request->sections as $value) {
        $sectionName = $value['section'];
        CourseSection::create([
          'course_id' => $courseId,
          'section_name' => $sectionName
        ]);
      }
      return $createCourse->course_code;
    }
  }

  public function getSections(Request $request)
  {
    $user_id = $this->decodeJWT($request->token);
    $getCourseId = Course::where('user_id', $user_id)->where('course_code', $request->code)
      ->first();
    if ($getCourseId) {
      $courseId = $getCourseId->id;
      $getCourseSections = CourseSection::where('course_id', $courseId)->get();
      if ($getCourseSections) {
        return $getCourseSections;
      } else {
        return 'noCourseFound';
      }
    } else {
      return 'noCourseFound';
    }
  }



  public function createdCourse(Request $request)
  {
    $user_id = $this->decodeJWT($request->token);
    $getCreatedCourse = Course::where('user_id', $user_id)->get();
    return $getCreatedCourse;
  }


  public function uploadVideos(Request $request)
  {
    $user_id = $this->decodeJWT($request->token);

    //checking if video is valid
    if ($request->file('video')) {
      if ($request->file('video')->isValid()) {
        $videoext = $request->video->extension();
        $arr = ['mp4',  'mov'];
        if (in_array($videoext, $arr)) {
          $videourl = $user_id . 'course_video' . $request->section . $this->generateRandomCode() . '.' . $videoext;
          $upload =  $request->video->storeAs('coursematerials',  $videourl, 'public');
          if ($upload) {
            $playTime = new GetId3(request()->file('video'));
            Videos::create([
              'course_sections_id' => $request->section,
              'video_name' => $request->videoName,
              'video_url' => $videourl,
              'play_time' => $playTime->getPlaytime()
            ]);
            $findSection = CourseSection::find($request->section);
            if ($findSection) {
              $findSection->no_of_videos = $findSection->no_of_videos + 1;
              $findSection->save();
            }
            return 'added';
          }
        } else {
          return 'videoFormatNotSUpported';
        }
      }
    } else {
      return 'novideo';
    }
  }


  public function uploadFiles(Request $request)
  {
    $user_id = $this->decodeJWT($request->token);
    //checking if file is valid
    if ($request->file('File')) {
      if ($request->file('File')->isValid()) {
        $fileext = $request->File->extension();
        $arr = ['pdf',  'zip', 'txt'];
        if (in_array($fileext, $arr)) {
          $fileurl = $user_id . 'course_file' . $request->section . $this->generateRandomCode() . '.' . $fileext;
          $upload =  $request->File->storeAs('coursematerials',  $fileurl, 'public');
          if ($upload) {
            Files::create([
              'course_sections_id' => $request->section,
              'file_url' => $fileurl,
            ]);
            $findSection = CourseSection::find($request->section);
            if ($findSection) {
              $findSection->no_of_files = $findSection->no_of_files + 1;
              $findSection->save();
            }
            return 'added';
          }
        } else {
          return 'fileFormatNotSUpported';
        }
      }
    } else {
      return 'nofile';
    }
  }





  public function saveVideos(Request $request)
  {
    $user_id = $this->decodeJWT($request->token);
    $getcourse = Course::where('course_code', $request->code)->where('user_id', $user_id)->first();
    if ($getcourse) {
      //Checking if all section has at least a video each
      $courseId = $getcourse->id;
      $checkSections = CourseSection::where('course_id', $courseId)->get();
      $filtered = $checkSections->filter(function ($item) {
        return data_get($item, 'no_of_videos') == 0;
      });
      if ($filtered->all()) {
        return 'videoIncomplete';
      } else {
        $getcourse->status = 'active';
        $getcourse->save();
        return 'videocomplete';
      }
    }
  }

  public function publishCourse(Request $request)
  {
    $user_id = $this->decodeJWT($request->token);
    $isVerified = User::find($user_id)->email_verified_at;
    if ($isVerified === null) {
      return 'notverified';
    } else {
      $getcourseId = Course::where('course_code', $request->code)->where('user_id', $user_id)->first();
      if ($getcourseId) {
        //Checking if all section has at least a video each
        $courseId = $getcourseId->id;
        $checkSections = CourseSection::where('course_id', $courseId)->get();
        $filtered = $checkSections->filter(function ($item) {
          return data_get($item, 'no_of_videos') == 0;
        });
        if ($filtered->all()) {
          return 'videoIncomplete';
        } else {
          if ($getcourseId->status == 'pending') {
            $getcourseId->status = 'active';
            $getcourseId->save();
            Suggestions::create([
              'name' => $getcourseId->course_title
            ]);
            return 'published';
          } else {
            return 'alreadypublished';
          }
        }
      }
    }
  }

  public function withdrawHistory(Request $request)
  {
    $user_id = $this->decodeJWT($request->header('Authorization'));
    return Withdrawal_Initiated::where('user_id', $user_id)->get();
  }

  public function tutorPreview(Request $request)
  {
    $res = array('sections' => '', 'title' => '', 'stats' => '');
    $user_id = $this->decodeJWT($request->token);
    $course = Course::where('course_code', $request->code)->where('user_id', $user_id)->first();
    $sections = CourseSection::where('course_id',  $course->id)->get();
    foreach ($sections as $section) {
      $videos = Videos::where('course_sections_id', $section->id)->get();
      $files = Files::where('course_sections_id', $section->id)->get();
      $section->no_of_videos = $videos;
      $section->no_of_files = $files;
    };
    $res['sections'] =  $sections;
    $res['title'] = $course->course_title;
    $stats = count(Payment::where('course_id', $course->id)->where('state', 'paid')->get());
    $res['stats'] = $stats;
    return $res;
  }
}
