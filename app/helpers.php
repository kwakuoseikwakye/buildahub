<?php

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

if (!function_exists('apiResponse')) {
      function apiResponse($status, $message, $data = null, $statusCode)
      {
            return response()->json([
                  'status' => $status,
                  'message' => $message,
                  'data' => $data
            ], $statusCode);
      }
}

if (!function_exists('internalServerErrorResponse')) {
      function internalServerErrorResponse(string $message, $e)
      {
            Log::error($message, [
                  "errMsg" => $e->getMessage(),
                  "trace" => $e->getTrace(),
            ]);
            return response()->json([
                  "status" => "error",
                  "message" => "Internal Server Error " . $message
            ], 500);
      }
}

if (!function_exists('validationResponse')) {
      function validationResponse(string $message)
      {
            return response()->json([
                  "status" => "error",
                  "message" => "Internal Server Error " . $message
            ], 422);
      }
}

if (!function_exists('extractUserToken')) {
      function extractUserToken(Request $request)
      {
            $token = $request->bearerToken();
            $user = User::where('remember_token', $token)->first();
            if (empty($user)) {
                  return null;
            } else {
                  return $user;
            }
            return null;
      }
}
