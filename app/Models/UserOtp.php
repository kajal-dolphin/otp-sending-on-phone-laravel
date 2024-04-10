<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Exception;
use Twilio\Rest\Client;

class UserOtp extends Model
{
    use HasFactory;

    /**
     * Write code on Method
     *
     * @return response()
     */
    protected $fillable = ['user_id', 'otp', 'expire_at'];

    /**
     * Write code on Method
     *
     * @return response()
     */
    public function sendSMS($receiverNumber)
    {
        $message = "Login OTP is ".$this->otp;

        try {

            $twilio_sid = env('TWILIO_SID');
            $twilio_token = env('TWILIO_TOKEN');
            $twilio_phone_number = env('TWILIO_FROM');

            $to = $receiverNumber;

            $url = "https://api.twilio.com/2010-04-01/Accounts/$twilio_sid/Messages.json";

            $data = [
                'To' => $to,
                'From' => $twilio_phone_number,
                'Body' => $message,
            ];

            $post = http_build_query($data);
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERPWD, "$twilio_sid:$twilio_token");

            $response = curl_exec($ch);

            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

        } catch (Exception $e) {
            info("Error: ". $e->getMessage());
        }
    }
}
