<?php

namespace App\Http\Controllers;

use App\Mail\OrderShipped;
use App\Models\User;
use App\Models\VerificationCode;
use Carbon\Carbon;
use Faker\Provider\en_NG\PhoneNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tymon\JWTAuth\Facades\JWTAuth;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;


class RegisterController extends Controller
{

  // Decode JWT
  function  decodeJWT($token)
  {
    $token = JWTAuth::getToken($token);
    $tokenDetails = JWTAuth::getPayload($token)->toArray();
    $user_id = $tokenDetails['sub'];
    return $user_id;
  }

  public function register(Request $request)
  {
    $res = array('email' => '', 'error' => '');
    $this->validate($request, [
      'firstname' => ['required', 'string', 'max:255'],
      'lastname' => ['required', 'string', 'max:255'],
      'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
      'password' => ['required', 'string', 'min:8', 'required_with:passwordRepeat', 'same:passwordRepeat'],
      'passwordRepeat' => ['required', 'string', 'min:8'],
    ]);

    // --Creating Account--
    User::create([
      'firstname' => $request->firstname,
      'lastname' => $request->lastname,
      'email' => $request->email,
      'password' => Hash::make($request->password)
    ]);



    // --Sending verification code--
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
          $res['email'] = $checkEmail->email;
        }
      } catch (Exception $e) {
        $res['error'] = $e;
      }
    }
    return $res;
  }


  // Password reset
  public function checkEmail(Request $request)
  {
    $res = array('email' => '', 'error' => '', 'notfound' => '');
    $checkEmail = User::where('email', $request->email)->first();
    if ($checkEmail) {
      $code = mt_rand(1111, 9999);
      VerificationCode::create([
        'user_id' => $checkEmail->id,
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
          $res['email'] = $checkEmail->email;
        }
      } catch (Exception $e) {
        $res['error'] = $e;
      }
      // return  $checkEmail->email;
    } else {
      $res['notfound'] = 'notfound';
    }
    return $res;
  }


  //verify code for password reset
  public function passwordVerifyCode(Request $request)
  {
    $id = User::where('email', $request->email)->first()->id;
    $time = now();
    $checkCode = VerificationCode::where('user_id', $id)->where('code', $request->code)->where('created_at', '>', $time)->first();
    if ($checkCode) {
      $updatedPasssword = User::find($id);
      $updatedPasssword->password = Hash::make($request->password);
      $updatedPasssword->save();
      return 'PasswordChanged';
    } else {
      return 'Invalid or Expired Code';
    }
  }




  // Updating Profile
  public function updateProfile(Request $request)
  {
    $user_id = $this->decodeJWT($request->token);
    $update = User::find($user_id);
    if ($update->phone) {
      return 'phoneexists';
    } else {
      $this->validate($request, [
        'phone' => ['required', 'unique:users']
      ]);
      $update->phone = $request->phone;
      $update->save();
      return 'updated';
    }
  }


  // Updating Bank details
  public function updateBank(Request $request)
  {
    $user_id = $this->decodeJWT($request->token);
    $res = array('exists' => '', 'name' => '', 'invalid' => '', 'error' => '');

    // Verify Bank details on paystack
    $curl = curl_init();
    curl_setopt_array($curl, array(
      CURLOPT_URL => "https://api.paystack.co/bank/resolve?account_number=" . rawurlencode($request->acc_no) . "&bank_code="
        . rawurlencode($request->bankCode),
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "GET",
      CURLOPT_HTTPHEADER => array(
        "Authorization: Bearer sk_test_9bc7c7d17ceb43c609ea9b0f40e4f48b5dde8527",
        "Cache-Control: no-cache",
      ),
    ));
    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);
    if ($err) {
      $res['error'] =  $err;
      return $res;
    } else {
      $result = json_decode($response);
      $verify = $result->status;
      if ($verify) {
        $update = User::find($user_id);
        if ($update->bank_code || $update->acc_no || $update->bank_acc_name) {
          $res['exists'] =  'bankexists';
          return $res;
        } else {
          $this->validate($request, [
            'acc_no' => ['required', 'string', 'unique:users']
          ]);
          $update->bank_code = $request->bankCode;
          $update->acc_no = $request->acc_no;
          $update->pin = $request->pin;
          $update->bank_acc_name = $result->data->account_name;
          $update->save();
          $res['name'] = $result->data->account_name;
          return $res;
        }
      } else {
        $res['invalid'] = 'invalid';
        return $res;
      }
    }
  }
}
