<?php

use App\Http\Controllers\Api\AdsController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ConditionController;
use App\Http\Controllers\Api\PlanController;
use App\Http\Controllers\Api\RegionController;
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

    Route::get("regions", [RegionController::class, "fetchRegions"]);
    Route::get("plans", [PlanController::class, "fetchPlans"]);
    Route::get("conditions", [ConditionController::class, "fetchConditions"]);
    Route::get("categories", [CategoryController::class, "fetchCategories"]);

    Route::prefix("ads")->group(function () {
        Route::get("/{categoryid}/category", [AdsController::class, "fetchAdsCategory"]); 
        Route::get("/", [AdsController::class, "fetchAds"]);
        Route::get("/user", [AdsController::class, "fetchUserAds"]);
        Route::get("/trending", [AdsController::class, "fetchTrendingAds"]);
        Route::post("/", [AdsController::class, "store"]);
        Route::post("/view", [AdsController::class, "addView"]);
        Route::delete("/{model_id}", [AdsController::class, "deleteAds"]);
        Route::patch("/{model_id}", [AdsController::class, "update"]);
    });
});
