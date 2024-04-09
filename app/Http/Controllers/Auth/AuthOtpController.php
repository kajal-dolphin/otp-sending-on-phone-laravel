<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\UserOtp;
use Illuminate\Support\Facades\Log;

class AuthOtpController extends Controller
{
    /**
     * Write code on Method
     *
     * @return response()
     */
    public function login()
    {
        return view('auth.otpLogin');
    }

    /**
     * Write code on Method
     *
     * @return response()
     */

    public function generate(Request $request)
    {
        try {
            $show_otp_on_login = 0;

            $send_otp = 0;
            $phone = $request->mobile_no;
            $otp = rand(1000, 9999);

            $user = User::where('mobile_no', $phone)->first();
            $userOtp = UserOtp::where('user_id', $user->id)->latest()->first();

            $now = now();

            if ($userOtp) {
                UserOtp::where('user_id', $user->id)->update([
                    'user_id' => $user->id,
                    'otp' => $userOtp->otp,
                    'expire_at' => $now->addMinutes(10)
                ]);
            } else {
                UserOtp::create([
                    'user_id' => $user->id,
                    'otp' => $otp,
                    'expire_at' => $now->addMinutes(10)
                ]);
            }

            $sms_msg = "Your OTP to register/access ONLINEGGS is " . $otp;
            $this->generateOtp($phone, $sms_msg);

            $status = 1;

            //used for non SMS gateway implemented
            if ($show_otp_on_login == 1) {

                $message = "OTP " . $otp . " sent to your mobile no. ";
            } else {
                $message = "OTP sent to your mobile no. ";
            }


            return response()->json(array('status' => $status, 'message' => $message));
        } catch (\Exception $e) {
            Log::info($e->getMessage());
        }
    }

    public function generateOtp($mobile, $msg, $lang = 'EN')
    {
        try {
            $apiKey = urlencode('NjI2ODQxNWE0MTMwNmQzNDQ2MzE2MjY5NTkzMzRlMzI=');
            $numbers = array($mobile);
            $sender = urlencode('TXTLCL');
            $message = rawurlencode($msg);
            dd($message);

            $numbers = implode(',', $numbers);

            // Prepare data for POST request
            $data = array('apikey' => $apiKey, 'numbers' => $numbers, "sender" => $sender, "message" => $message);

            // Send the POST request with cURL
            $ch = curl_init('https://api.textlocal.in/send/');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            curl_close($ch);
            dd($response);
            // Process your response here
            echo $response;
        } catch (\Exception $e) {
            Log::info($e->getMessage());
        }
    }
    // public function generate(Request $request)
    // {
    //     try {
    //         $request->validate([
    //             'mobile_no' => 'required'
    //         ]);
    //         /* Generate An OTP */
    //         $userOtp = $this->generateOtp($request->mobile_no);
    //         $userOtp->sendSMS($request->mobile_no);

    //         return redirect()->route('otp.verification', ['user_id' => $userOtp->user_id])
    //             ->with('success',  "OTP has been sent on Your Mobile Number.");
    //     } catch (\Exception $e) {
    //        Log::info($e->getMessage());
    //     }
    //     /* Validate Data */

    // }

    /**
     * Write code on Method
     *
     * @return response()
     */
    // public function generateOtp($mobile_no)
    // {
    //     $user = User::where('mobile_no', $mobile_no)->first();

    //     /* User Does not Have Any Existing OTP */
    //     $userOtp = UserOtp::where('user_id', $user->id)->latest()->first();

    //     $now = now();

    //     if ($userOtp && $now->isBefore($userOtp->expire_at)) {
    //         return $userOtp;
    //     }

    //     /* Create a New OTP */
    //     return UserOtp::create([
    //         'user_id' => $user->id,
    //         'otp' => rand(123456, 999999),
    //         'expire_at' => $now->addMinutes(10)
    //     ]);
    // }

    /**
     * Write code on Method
     *
     * @return response()
     */
    public function verification($user_id)
    {
        return view('auth.otpVerification')->with([
            'user_id' => $user_id
        ]);
    }

    /**
     * Write code on Method
     *
     * @return response()
     */
    public function loginWithOtp(Request $request)
    {
        /* Validation */
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'otp' => 'required'
        ]);

        /* Validation Logic */
        $userOtp   = UserOtp::where('user_id', $request->user_id)->where('otp', $request->otp)->first();

        $now = now();
        if (!$userOtp) {
            return redirect()->back()->with('error', 'Your OTP is not correct');
        } else if ($userOtp && $now->isAfter($userOtp->expire_at)) {
            return redirect()->route('otp.login')->with('error', 'Your OTP has been expired');
        }

        $user = User::whereId($request->user_id)->first();

        if ($user) {

            $userOtp->update([
                'expire_at' => now()
            ]);

            Auth::login($user);

            return redirect('/home');
        }

        return redirect()->route('otp.login')->with('error', 'Your Otp is not correct');
    }
}
