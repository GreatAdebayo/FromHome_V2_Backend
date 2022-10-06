<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\VerificationCode;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class VerifyCode extends Controller
{



    public function verifycode(Request $request)
    {
        // --Verifying  code--
        $checkUserExists = User::where('email',  $request->email)->first();
        if ($checkUserExists) {
            $checkIfVerified = $checkUserExists->email_verified_at;
            if ($checkIfVerified == null) {
                $id = $checkUserExists->id;
                $time = now();
                $checkCode = VerificationCode::where('user_id', $id)->where('code', $request->code)->where('created_at', '>', $time)->first();
                if ($checkCode) {
                    $updateVerificationStatus = User::find($id);
                    $updateVerificationStatus->email_verified_at = now();
                    $updateVerificationStatus->save();
                    //sending welcome message to email
                    $template = file_get_contents(resource_path('views/welcome.blade.php'));
                    $template = str_replace('{{lastname}}', $checkUserExists->lastname, $template);
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
                        $mail->addAddress($request->email);
                        $mail->addAddress($request->email);

                        $mail->isHTML(true);
                        $mail->Subject = 'Welcome to Fromhome';
                        $mail->Body    = $template;
                        $mail->AltBody = 'This is the body in plain text for non-HTML mail clients';


                        if ($mail->send()) {
                            return 'Verified';
                        }
                    } catch (Exception $e) {
                        return $e;
                    }
                } else {
                    return 'Invalid or Expired Code';
                }
            } else {
                return 'Already Verified';
            }
        } else {
            return 'User not Found';
        }
    }


    // --Resending Verification code--
    public function resendcode(Request $request)
    {
        $res = array('sent' => '', 'error' => '');
        $checkEmail = User::where('email', $request->email)->first();
        if ($checkEmail) {
            $user_id = $checkEmail->id;
            $code = mt_rand(1111, 9999);
            VerificationCode::create([
                'user_id' => $user_id,
                'code' => $code,
                'created_at' => Carbon::parse(now())->addMinutes(2)
            ]);
            //sending code to email
            $template = file_get_contents(resource_path('views/verify.blade.php'));
            $template = str_replace('{{code}}', $code, $template);
            $template = str_replace('{{lastname}}', $checkEmail->lastname, $template);
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
                $mail->addAddress($checkEmail->email);
                $mail->addAddress($checkEmail->email);

                $mail->isHTML(true);
                $mail->Subject = 'Email verification code';
                $mail->Body    = $template;
                $mail->AltBody = 'This is the body in plain text for non-HTML mail clients';


                if ($mail->send()) {
                    $res['sent'] = 'codesent';
                }
            } catch (Exception $e) {
                $res['error'] = $e;
            }
        } else {
            return 'Email not Found';
        }
        return $res;
    }
}
