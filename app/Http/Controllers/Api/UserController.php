<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserController extends Controller
{
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function destroy()
    {
        try {
            $user = extractUserToken($this->request);
            $user->delete();
            return apiResponse('success', 'User deleted successfully', null, 200);
        } catch (\Exception $e) {
            return internalServerErrorResponse(' deleting user account', $e);
        }
    }
}
