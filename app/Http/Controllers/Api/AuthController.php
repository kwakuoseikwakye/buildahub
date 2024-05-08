<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Arkesel\Arkesel as Sms;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function signUp(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "othernames" => "required|string|max:50",
                "surname" => "required|string|max:50",
                "phone" => "required|numeric|unique:users,phone",
                "password" => "required|string|min:8",
                "email" => "required|email|unique:users,email",
            ]);

            if ($validator->fails()) {
                return apiResponse('error', "Registration failed. " . join(". ", $validator->errors()->all()), null, 422);
            }

            $validatedData = $validator->validated() + [
                "user_type" => "user",
                "verified" => 0,
                "user_id" => strtoupper(bin2hex(random_bytes(6)))
            ];

            User::create($validatedData);
            $this->sendOtp($request);

            $user = User::where("email", $request->input('email'))->first();
            $token = $user->createToken('auth_token')->plainTextToken;

            User::where("email", $request->input('email'))->update([
                'remember_token' => $token
            ]);

            $data =  ["auth_token" => $token, "token_type" => "Bearer", "user" => $user];
            return apiResponse('success', 'Registration successful', $data, 201);
        } catch (\Throwable $e) {
            return internalServerErrorResponse("registering user failed", $e);
        }
    }

    public function sendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "phone" => "required|numeric",
        ]);

        if ($validator->fails()) {
            return apiResponse('error', "Sending otp failed. " . join(". ", $validator->errors()->all()), null, 422);
        }

        Cache::forget('otp_' . $request->input('phone'));

        $authenticatedUser = User::where("phone", $request->input('phone'))
            ->first();

        if (empty($authenticatedUser)) {
            return apiResponse('error', 'Unknown user', null, 404);
        }

        $otp = rand(100000, 999999);
        Cache::put('otp_' . $request->input('phone'), $otp, now()->addMinutes(6));

        //TODO::Send email notification with OTP to the user.
        $msg = <<<MSG
            Your One-Time-Password is {$otp} if you did not initiate this request kindly ignore this message
            MSG;

        $sms = new Sms(env('ARKESEL_SMS_ID'), env('ARKESEL_SMS_API_KEY'));
        $sms->send($request->input('phone'), $msg);

        return apiResponse('success', 'OTP sent successfully.', $otp, 200);
    }

    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "phone" => "required|numeric",
            "otp" => "required|numeric",
        ]);

        if ($validator->fails()) {
            return apiResponse('error', "Sending otp failed. " . join(". ", $validator->errors()->all()), null, 422);
        }

        $inputOtp = $request->input('otp');
        $phone = $request->input('phone');

        $cachedOtp = Cache::get('otp_' . $phone);

        if ($cachedOtp && $cachedOtp == $inputOtp) {
            User::where('phone', $request->input('phone'))->update(['verified' => 1]);
            Cache::forget('otp_' . $request->input('phone'));

            return apiResponse('success', 'OTP verification successful.', null, 200);
        } else {
            return apiResponse('error', 'OTP is invalid or expired.', null, 418);
        }
    }

    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "password" => "required",
                "email" => "required",
            ], [
                "email.required" => "Email not supplied",
                "password.required" => "Password not supplied",
            ]);

            if ($validator->fails()) {
                return apiResponse('error', "Login failed. " . join(". ", $validator->errors()->all()), null, 422);
            }

            if (!Auth::attempt($request->only(["email", "password"]))) {
                return apiResponse('error', "Login failed. Invalid credentials", null, 418);
            }

            $user = User::where("email", $request->input('email'))->first();

            // No admin can login via the mobile
            if (!in_array(strtolower($user->user_type), User::ALLOWED_USER_TYPES)) {
                return apiResponse('error', "You cannot log in using the mobile client", null, 418);
            }

            $token = $user->createToken('auth_token')->plainTextToken;
            User::where("email", $request->input('email'))->update([
                'remember_token' => $token
            ]);
            $data =  ["auth_token" => $token, 'token_type' => 'Bearer', "user" => $user];

            return apiResponse('success', "Login successful", $data, 200);
        } catch (\Throwable $e) {
            return internalServerErrorResponse("login failed", $e);
        }
    }

    public function changePassword(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                "old_password" => "required|min:8|string",
                "new_password" => "required|min:8|string",
            ],
            [
                "old_password.required" => "Old password is required",
                "old_password.min" => "Your old password must be at least 8 characters long",

                "new_password.min" => "Your new password must be at least 8 characters long",
                "new_password.required" => "Current password is required",
            ]
        );

        if ($validator->fails()) {
            return apiResponse('error', "Reset failed. " . join(". ", $validator->errors()->all()), null, 422);
        }

        $authUserDetails = extractUserToken($request);
        if (empty($authUserDetails)) {
            return apiResponse('error', 'Unauthorized - Token not provided or invalid', null, 401);
        }

        if (!Hash::check($request->input('old_password'), $authUserDetails->password)) {
            return apiResponse('error', 'Invalid old password', null, 418);
        }

        $password = Hash::make($request->input('new_password'));

        try {
            $authUserDetails->update([
                'password' => $password,
                'modifydate' => date("Y-m-d H:i:s"),
            ]);
            return apiResponse('success', 'Password updated successfully', null, 200);
        } catch (\Throwable $e) {
            return internalServerErrorResponse("Password reset failed", $e);
        }
    }

    public function passwordReset(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                "password" => "required|min:8",
                "phone" => "required|numeric|exists:users,phone",
            ],
            [
                "password.required" => "You have to supply your new password",
                "password.min" => "Your new password must be at least 8 characters long",

                "phone.required" => "No phone number supplied",
                "phone.exists" => "Unknown phone number supplied",
                "phone.numeric" => "The phone number you supplied is invalid",
            ] 
        );


        if ($validator->fails()) {
            return apiResponse('error', "Reset failed. " . join(". ", $validator->errors()->all()), null, 422);
        }

        $authenticatedUser = User::where("phone", $request->input('phone'))
            ->first();

        if (empty($authenticatedUser)) {
            return apiResponse('error', 'Unknown user', null, 418);
        }

        $password = Hash::make($request->input('password'));

        try {
            $authenticatedUser->update([
                'password' => $password,
                'modifydate' => date("Y-m-d H:i:s"),
            ]);

            $msg = <<<MSG
            Your password has been reset. If you did not initiate this action, please contact support.
            MSG;

            $sms = new Sms(env('ARKESEL_SMS_ID'), env('ARKESEL_SMS_API_KEY'));
            $sms->send($request->input('phone'), $msg);

            return apiResponse('success', 'Your password has been reset', null, 200);
        } catch (\Throwable $e) {
            return internalServerErrorResponse("Password reset failed", $e);
        }
    }
}
