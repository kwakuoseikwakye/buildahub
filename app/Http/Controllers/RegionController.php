<?php

namespace App\Http\Controllers;

use App\Models\Regions;
use Illuminate\Http\Request;

class RegionController extends Controller
{
    public function fetchRegions()
    {
        $data = Regions::with('cities')->get(); 
        return apiResponse('success', 'Request Successful', $data, 200);
    }
}
