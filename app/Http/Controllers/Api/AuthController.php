<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Arkesel\Arkesel as Sms;
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
                "verified" => 0,
                "user_id" => strtoupper(bin2hex(random_bytes(6)))
            ];
            User::create($validatedData);
            $this->sendOtp($request);
            return apiResponse('success', 'Registration successful', null, 201);
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

        Cache::forget('otp_' . $request->phone);

        $authenticatedUser = User::where("phone", $request->phone)
            ->first();

        if (empty($authenticatedUser)) {
            return apiResponse('error', 'Unknown user', null, 404);
        }

        $otp = rand(100000, 999999);
        Cache::put('otp_' . $request->phone, $otp, now()->addMinutes(6));

        $msg = <<<MSG
            Your OTP code is {$otp}
            MSG;

        $sms = new Sms(env('ARKESEL_SMS_ID'), env('ARKESEL_SMS_API_KEY'));
        $sms->send($request->phone, $msg);

        return apiResponse('success', 'OTP sent successfully.', null, 200);
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
            User::where('phone', $request->phone)->update(['verified' => 1]);
            Cache::forget('otp_' . $request->phone);

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

            $user = User::where("email", $request->email)->first();

            // No admin can login via the mobile
            if (!in_array(strtolower($user->user_type), User::ALLOWED_USER_TYPES)) {
                return apiResponse('error', "You cannot log in using the mobile client", null, 418);
            }

            $token = $user->createToken('auth_token')->plainTextToken;
            User::where("email", $request->email)->update([
                'remember_token' => $token
            ]);
            $data =  ["auth_token" => $token, 'token_type' => 'Bearer', "user" => $user];

            return apiResponse('success', "Login successful", $data, 200);
        } catch (\Throwable $e) {
            return internalServerErrorResponse("login failed", $e);
        }
    }
}
