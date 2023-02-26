<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Seshac\Otp\Otp;
use Illuminate\Support\Facades\DB;

class OtpController extends Controller
{
    public function generate_otp(Request $request)
    {
        try {
            $user = User::where('phone', $request->phone)->first();

            if ($user == null)
                throw new \Exception('User not found');

            $otp = Otp::setValidity(3)->generate($user->nik);

            if (!$otp->status)
                throw new \Exception($otp->message);

            $this->sendMessage($user, $otp->token);

            return response()->json(['message' => 'Otp send successful', 'user' => $user]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function verification(Request $request)
    {
        $user = User::where('phone', $request->phone)->first();
        $identifier = $user->nik;
        $token = $request->token;

        try {
            $verify = Otp::validate($identifier, $token);

            if (!$verify->status)
                throw new \Exception($verify->message);

            if ($verify->status)
                DB::table('otps')->where('identifier', $identifier)->update(['expired' => 1]);

            // Success Response
            return response()->json(['message' => 'Verification OTP successful']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }


    public function change_password(Request $request)
    {
        $user = User::where('phone', $request->phone)->with('regional')->first();
        try {
            // Update Data User
            $user->password = bcrypt($request->password);
            $do_update = $user->save();
            if (!$do_update)
                throw new \Exception('Failed update data user');

            // Success Response
            return response()->json(['message' => 'Change passsword  successful']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function sendMessage($user = null, $token_otp = null)
    {
        $curl = curl_init();
        $number = $user->phone[0] == 0 ? '62' . substr($user->phone, 0) : '62' . $user->phone;
        $key_template = env('TEMPLATE_WA_QONTAK');
        $token = env('TOKEN_QONTAK');

        curl_setopt_array($curl, [
            CURLOPT_URL => "https://chat-service.qontak.com/api/open/v1/broadcasts/whatsapp/direct",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "{\n  
                \"to_number\": \"$number\",\n  
                \"to_name\": \"$user->name\",\n  
                \"message_template_id\": \"$key_template\",\n  
                \"channel_integration_id\": \"e91a7153-6c72-41c6-ae7f-363ed43cf398\",\n 
                \"language\": {\n    \"code\": \"id\"\n  },\n  
                \"parameters\": {\n    
                    \"body\": [\n      
                            {\n \"key\": \"1\",\n \"value\": \"kdoe\",\n \"value_text\": \"$token_otp\"\n }\n    
                    ]\n 
                }\n
            }",
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer " . $token,
                "Content-Type: application/json"
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            return "cURL Error #:" . $err;
        } else {
            return $response;
        }
    }
}
