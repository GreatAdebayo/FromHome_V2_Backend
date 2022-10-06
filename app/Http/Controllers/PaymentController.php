<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\MyCourse;
use App\Models\Payment;
use App\Models\User;
use App\Models\Withdrawal;
use App\Models\Withdrawal_Initiated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class PaymentController extends Controller
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


    public function preparePayment(Request $request)
    {
        $res = array('alreadyPurchased' => '', 'payment' => '', 'notVerified' => '');
        $user_id = $this->decodeJWT($request->header('Authorization'));
        $isVerified = User::find($user_id);
        if ($isVerified->email_verified_at == null) {
            $res['notVerified'] =  $isVerified->email;
        } else {
            $transactionId = $this->generateRandomCode();
            foreach ($request->cart as $value) {
                $courseCode = Course::where('course_code', $value['code'])->first();
                $courseId = $courseCode->id;
                //check if already purchased
                if (Payment::where('user_id', $user_id)->where('course_id', $courseId)
                    ->where('state', 'paid')->first()
                ) {
                    $res['alreadyPurchased'] =  'alreadyPurchased';
                } else {
                    $paymentDetails = Payment::create([
                        'course_id' => $courseId,
                        'user_id' => $user_id,
                        'transaction_id' => $transactionId,
                        'amount' => $value['cost'],
                    ]);
                    $res['payment'] =  $paymentDetails->transaction_id;
                }
            }
        }

        return $res;
    }

    public function payment(Request $request)
    {
        $res = array('amount' => '', 'email' => '', 'state' => '');
        $user_id = $this->decodeJWT($request->token);
        $transactionDetails = Payment::where('transaction_id', $request->id)->where('user_id', $user_id)->get();
        if (count($transactionDetails)) {
            $email = User::where('id', $user_id)->first()->email;
            // checking if transaction state is on pending
            $filtered = $transactionDetails->filter(function ($item) {
                return data_get($item, 'state') == 'pending';
            });
            if ($filtered->all()) {
                // adding all amount together
                $totalAmountTobePaid = collect($transactionDetails)
                    ->reduce(function ($carry, $item) {
                        return $carry + $item["amount"];
                    }, 0);

                $res['amount'] =  $totalAmountTobePaid;
                $res['email'] =  $email;
            } else {
                $res['state'] =  'paid';
            }
        } else {
            $res['state'] =  'noTransaction';
        }
        return $res;
    }

    public function verifyPayment(Request $request)
    {
        if ($request->reference) {
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://api.paystack.co/transaction/verify/" . rawurlencode($request->reference),
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
                return 'error';
            } else {
                $result = json_decode($response);
                if ($result->data->status == 'success') {
                    $email = $result->data->customer->email;
                    $trans_id = $result->data->reference;
                    // Updating payment for buyer
                    Payment::where('transaction_id', $trans_id)->update(['state' => 'paid']);

                    // Sending money to respective tutors
                    $payment = Payment::where('transaction_id', $trans_id)->get();
                    foreach ($payment as $value) {
                        MyCourse::create([
                            'user_id' => $value->user_id,
                            'course_id' => $value->course_id,
                            'type' => 'purchased'
                        ]);
                        $cost = $value->amount;
                        $courseDetails = Course::where('id', $value->course_id)->get();
                        foreach ($courseDetails as $course) {
                            $useDetails = User::where('id', $course->user_id)->get();
                            foreach ($useDetails as $details) {
                                $details->earnings = $details->earnings + $cost;
                                $details->save();
                            }
                        }
                    }

                    return 'success';
                } else {
                    return 'failed';
                }
            }
        }
    }

    public function receipt(Request $request)
    {
        $user_id = $this->decodeJWT($request->token);
        return Payment::where('payments.user_id', $user_id)->where('transaction_id', $request->id)
            ->Join('courses', 'courses.id', '=', 'payments.course_id')->get();
    }


    public function withdraw(Request $request)
    {
        $user_id = $this->decodeJWT($request->header('Authorization'));
        $details = User::find($user_id);
        if ($details->bank_code && $details->acc_no && $details->pin) {
            if ($details->pin == $request->pin) {
                if ($details->earnings >= $request->amount) {
                    $bankCode = $details->bank_code;
                    $accNumber = $details->acc_no;
                    $accName = $details->bank_acc_name;

                    // create withdrawal receipient
                    $url = "https://api.paystack.co/transferrecipient";
                    $fields = [
                        'type' => "nuban",
                        'name' => $accName,
                        'account_number' => $accNumber,
                        'bank_code' => $bankCode,
                        'currency' => "NGN"
                    ];
                    $fields_string = http_build_query($fields);
                    //open connection
                    $ch = curl_init();

                    //set the url, number of POST vars, POST data
                    curl_setopt($ch, CURLOPT_URL, $url);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                        "Authorization: Bearer sk_test_9bc7c7d17ceb43c609ea9b0f40e4f48b5dde8527",
                        "Cache-Control: no-cache",
                    ));

                    //So that curl_exec returns the contents of the cURL; rather than echoing it
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

                    //execute post
                    $result = curl_exec($ch);
                    $info = json_decode($result);
                    $recipient_name = $info->data->name;
                    $recipient_code = $info->data->recipient_code;
                    $type = $info->data->type;
                    $account_number = $info->data->details->account_number;
                    $bank_code = $info->data->details->bank_code;
                    $bank_name = $info->data->details->bank_name;
                    $currency = $info->data->currency;
                    $createdAt = $info->data->createdAt;
                    if ($info->status) {
                        $createRecipient = Withdrawal::create([
                            'user_id' => $user_id,
                            'recipient_name' => $recipient_name,
                            'recipient_code' => $recipient_code,
                            'type' => $type,
                            'account_number' => $account_number,
                            'bank_code' => $bank_code,
                            'bank_name' => $bank_name,
                            'currency' => $currency,
                            'created_at' => $createdAt
                        ]);

                        if ($createRecipient) {

                            // Initiating withdrawal
                            $recipient_code = $createRecipient->recipient_code;
                            $url = "https://api.paystack.co/transfer";
                            $fields = [
                                "source" => "balance",
                                "reason" => "Tutor Withdrawal",
                                "amount" => $request->amount * 100,
                                "recipient" => $recipient_code
                            ];
                            $fields_string = http_build_query($fields);
                            //open connection
                            $ch = curl_init();

                            //set the url, number of POST vars, POST data
                            curl_setopt($ch, CURLOPT_URL, $url);
                            curl_setopt($ch, CURLOPT_POST, true);
                            curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
                            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                                "Authorization: Bearer sk_test_9bc7c7d17ceb43c609ea9b0f40e4f48b5dde8527",
                                "Cache-Control: no-cache",
                            ));

                            //So that curl_exec returns the contents of the cURL; rather than echoing it
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

                            //execute post
                            $result = curl_exec($ch);
                            $initiate = json_decode($result);
                            $status = $initiate->status;
                            $message = $initiate->data->status;
                            $reference =  $initiate->data->reference;
                            $amount =  $initiate->data->amount;
                            $reason =  $initiate->data->reason;
                            $transfer_code =  $initiate->data->transfer_code;
                            $created_At =  $initiate->data->createdAt;
                            if ($status) {
                                $initiateWithdrawal = Withdrawal_Initiated::create([
                                    'user_id' => $user_id,
                                    'reference' => $reference,
                                    'amount_in_kobo' => $amount,
                                    'reason' => $reason,
                                    'status' => $message,
                                    'transfer_code' => $transfer_code,
                                    'created_at' => $created_At
                                ]);

                                if ($initiateWithdrawal) {
                                    $details->earnings = $details->earnings - $amount / 100;
                                    $details->save();
                                    return 'withdrawSuccesful';
                                } else {
                                    return 'notSuccessful';
                                }


                                // Listening to event with webhook to be done when it's online.

                            }
                        }
                    } else {
                        return 'errorWithdraw';
                    }
                } else {
                    return 'insufficientFunds';
                }
            } else {

                return 'wrongPin';
            }
        } else {
            return 'noBankDetails';
        }
    }
}
