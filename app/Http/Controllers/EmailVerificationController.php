<?php

namespace App\Http\Controllers;

use App\Models\EmailVerification;
use App\Models\User;
use App\Models\Otp;
use App\Notifications\LoginNotification;
use App\trait\apiResponse;
//use Ichtrojan\Otp\Models\Otp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class EmailVerificationController extends Controller
{
    use apiResponse;
    public function email_verification(Request $request)
    {
        try {


        $validator = Validator::make($request->all(), [
            'token' => 'required|max:6|min:6|Exists:otps',
        //    'email' => 'required|string|email|max:100|Exists:users',

        ]);
        if ($validator->fails())
        {

            return response()->json($validator->errors()->toJson(), 400);
        }
        $credentials = $request->only(['email', 'password']);


        $token = Auth::guard('user-api')->attempt($credentials);  //generate token

        if (!$token)
            return $this->returnError('E001', 'بيانات الدخول غير صحيحة');

        $user = Auth::guard('user-api')->user();
        $user ->api_token = $token;
        $user ->update(['email_verified_at'=>now()]);
        $user->notify(new LoginNotification());
        //return token
        return $this->returnData('user', $user,'successfully');  //return json response

    } catch (\Exception $ex) {
return $this->returnError($ex->getCode(), $ex->getMessage());
}
       // $user = Otp::where('email',$request->email)->first();
      //  return $this->successMessage('200','Verified');
    }

    public function verifyEmail(Request $request)
    {
        $data = $request->validate([
            'token' => 'required|string'
        ]);

        $record = EmailVerification::where('token',$data['token'])
            ->where('expires_at','>',now())
            ->first();

        if (!$record) {
            return $this->returnError('VER001','Invalid token');
        }

        $user = User::where('email',$record->email)->first();

        $user->update([
            'email_verified_at' => now()
        ]);

        $record->delete();

        return $this->successMessage('Email verified successfully');
    }
}
