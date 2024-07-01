<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Plans;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    public function fetchPlans()
    {
        $data = Plans::all(); 
        return apiResponse('success', 'Request Successful', $data, 200);
    }
}
