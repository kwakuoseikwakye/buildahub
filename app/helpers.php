<?php

use Illuminate\Support\Facades\Log;

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

function validationResponse(string $message)
{
      return response()->json([
            "status" => "error",
            "message" => "Internal Server Error " . $message
      ], 422);
}
