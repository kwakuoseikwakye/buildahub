<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class checkUserToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $authUserDetails = extractUserToken($request);
        if (empty($authUserDetails)) { 
            return apiResponse('error', 'Unauthorized - Token not provided or invalid', null, 401);
        }

        return $next($request);
    }
}
