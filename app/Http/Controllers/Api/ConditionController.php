<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Condition;
use Illuminate\Http\Request;

class ConditionController extends Controller
{
    public function fetchConditions()
    {
        $data = Condition::all(); 
        return apiResponse('success', 'Request Successful', $data, 200);
    }
}
