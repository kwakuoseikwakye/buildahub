<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix("v1")->group(function () {
    Route::post("login", [AuthController::class, "login"]);
    Route::post("signup", [AuthController::class, "signUp"]);
    Route::post("change-password", [AuthController::class, "changePassword"]);
    Route::post("password-reset", [AuthController::class, "passwordReset"]);
    Route::post("verify-otp", [AuthController::class, "verifyOtp"]);
    Route::post("send-otp", [AuthController::class, "sendOtp"]);
});
