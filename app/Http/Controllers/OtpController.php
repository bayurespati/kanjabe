<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Seshac\Otp\Otp;
use App\Mail\OtpMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class OtpController extends Controller
{
    public function generate_otp(Request $request)
    {
        try {
            $user = User::where('email', $request->email)->first();

            if ($user == null)
                throw new \Exception('User not found');

            $otp = Otp::setValidity(5)->generate($user->email);

            if (!$otp->status)
                throw new \Exception($otp->message);

            $this->sendMail($user, $otp->token);

            return response()->json(['message' => 'OTP sent successful', 'user' => $user]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function verification(Request $request)
    {
        $user = User::where('email', $request->email)->first();
        $identifier = $user->email;
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
        $user = User::where('email', $request->email)->with('regional')->first();
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

    public function sendMail($user, $token)
    {
        Mail::to($user->email)->send(new OtpMail($user, $token));
    }
}
