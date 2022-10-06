<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseNote;
use App\Models\CourseReview;
use App\Models\MyCourse;
use App\Models\Payment;
use App\Models\Progress;
use App\Models\User;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class StudentController extends Controller
{
    function  decodeJWT($token)
    {
        $token = JWTAuth::getToken($token);
        $tokenDetails = JWTAuth::getPayload($token)->toArray();
        $user_id = $tokenDetails['sub'];
        return $user_id;
    }

    public function purhcasedCourse(Request $request)
    {
        $user_id = $this->decodeJWT($request->header('Authorization'));
        $mycourse =  MyCourse::where('my_courses.user_id', $user_id)->where('type', 'purchased')
            ->Join('courses', 'courses.id', '=', 'my_courses.course_id')
            ->get();
        foreach ($mycourse as $course) {
            $course_code = $course->course_code;
            $progress = Progress::where('user_id', $user_id)->where('course_code', $course_code)->get();
            $totalProgress = collect($progress)
                ->reduce(function ($carry, $item) {
                    return $carry + $item["progress"];
                }, 0);
            $course->progress = $totalProgress;
        }
        return $mycourse;
    }

    public function wishlist(Request $request)
    {
        $user_id = $this->decodeJWT($request->header('Authorization'));
        return MyCourse::where('my_courses.user_id', $user_id)->where('type', 'wishlist')
            ->Join('courses', 'courses.id', '=', 'my_courses.course_id')->get();
    }

    public function addwishlist(Request $request)
    {
        $user_id = $this->decodeJWT($request->header('Authorization'));
        $course_id = Course::where('course_code', $request->code)->first()->id;
        if (MyCourse::where('user_id', $user_id)->where('course_id', $course_id)->where('type', 'wishlist')->first()) {
            return 'alreadyAdded';
        } else {
            MyCourse::create([
                'user_id' => $user_id,
                'course_id' => $course_id,
                'type' => 'wishlist'
            ]);
            return 'added';
        }
    }

    public function transactions(Request $request)
    {
        $user_id = $this->decodeJWT($request->header('Authorization'));
        return Payment::where('payments.user_id', $user_id)
            ->Join('courses', 'courses.id', '=', 'payments.course_id')->get();
    }

    public function saveNote(Request $request)
    {
        $user_id = $this->decodeJWT($request->header('Authorization'));
        $checkNote = CourseNote::where('user_id', $user_id)->where('course_code', $request->code)->first();
        if ($checkNote) {
            $checkNote->note = $checkNote->note . $request->note;
            $checkNote->save();
            return 'saved';
        } else {
            CourseNote::create([
                'user_id' => $user_id,
                'course_code' => $request->code,
                'note' => $request->note
            ]);
            return 'saved';
        }
    }

    public function getNote(Request $request)
    {
        $user_id = $this->decodeJWT($request->header('Authorization'));
        $note = CourseNote::where('user_id', $user_id)->where('course_code', $request->code)->first();
        if ($note) {
            return $note->note;
        }
    }

    public function clearNote(Request $request)
    {
        $user_id = $this->decodeJWT($request->header('Authorization'));
        $checkNote = CourseNote::where('user_id', $user_id)->where('course_code', $request->code)->first();
        if ($checkNote) {
            $checkNote->note = '';
            $checkNote->save();
        }
    }

    public function sendReview(Request $request)
    {
        $user_id = $this->decodeJWT($request->header('Authorization'));
        CourseReview::create([
            'user_id' => $user_id,
            'course_code' => $request->code,
            'review' => $request->review
        ]);
        $studentName = User::find($user_id)->lastname;
        $course = Course::where('course_code', $request->code)->first();
        $tutor  = User::find($course->user_id);
        //sending review to tutor email
        $template = file_get_contents(resource_path('views/review.blade.php'));
        $template = str_replace('{{review}}', $request->review, $template);
        $template = str_replace('{{student}}', $studentName, $template);
        $template = str_replace('{{tutor}}',  $tutor->lastname, $template);
        $template = str_replace('{{courseTitle}}', $course->course_title, $template);
        require '../vendor/autoload.php';
        $mail = new PHPMailer(true);
        try {
            //Server settings
            $mail->SMTPDebug = SMTP::DEBUG_SERVER;
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = config('credentials.email');
            $mail->Password   = config('credentials.password');
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = 465;
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );
            $mail->SMTPDebug = 0;
            //Recipients
            $mail->setFrom(config('credentials.email'), 'FromHome');
            $mail->addAddress($tutor->email);
            $mail->addAddress($tutor->email);

            $mail->isHTML(true);
            $mail->Subject = 'Course Review';
            $mail->Body    = $template;
            $mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

            $mail->send();
        } catch (Exception $e) {
            //   $res['error'] = $e;
            return $e;
        }
        return 'sent';
    }
}
