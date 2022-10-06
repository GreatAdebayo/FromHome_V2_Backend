<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Files;
use App\Models\Suggestions;
use App\Models\Videos;
use Owenoj\LaravelGetId3\GetId3;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class EditCourse extends Controller
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


    public function editBasicDetails(Request $request)
    {
        $user_id = $this->decodeJWT($request->header('Authorization'));
        $course = Course::where('course_code', $request->course_code)->where('user_id', $user_id)->first();
        if ($course) {
            $course->course_title = $request->course_title;
            $course->description = $request->description;
            $course->course_cost = $request->course_cost;
            $course->status = 'active';
            $course->save();
            Suggestions::create([
                'name' => $request->course_title
            ]);
            return 'updated';
        }
    }

    public function sectionDetails(Request $request)
    {
        $res = array('videos' => '', 'files' => '');
        $section_videos = Videos::where('course_sections_id', $request->id)->get();
        $section_files  = Files::where('course_sections_id', $request->id)->get();
        $res['videos'] = $section_videos;
        $res['files'] = $section_files;
        return $res;
    }

    public function deleteVideo(Request $request)
    {
        Videos::find($request->id)->delete();
        $findSection = CourseSection::find($request->section);
        if ($findSection) {
            $findSection->no_of_videos = $findSection->no_of_videos - 1;
            $findSection->save();
        }
        return 'videodeleted';
    }

    public function deleteFile(Request $request)
    {
        Files::find($request->id)->delete();
        $findSection = CourseSection::find($request->section);
        if ($findSection) {
            $findSection->no_of_files = $findSection->no_of_files - 1;
            $findSection->save();
        }
        return 'filedeleted';
    }

    public function addSection(Request $request)
    {
        $courseId = Course::where('course_code', $request->code)->first()->id;
        return CourseSection::create([
            'course_id' => $courseId,
            'section_name' => $request->sectionName
        ]);
    }

    public function editSectionName(Request $request)
    {
        $section = CourseSection::find($request->id);
        $section->section_name = $request->newName;
        $section->save();
        return $section->section_name;
    }


    public function deleteSection(Request $request)
    {
        CourseSection::find($request->id)->delete();
        return 'sectionDeleted';
    }

    public function addNewVideo(Request $request)
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

    public function addNewFile(Request $request)
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


}
