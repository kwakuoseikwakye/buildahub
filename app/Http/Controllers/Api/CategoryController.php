<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Categories;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function fetchCategories()
    {
        $data = Categories::with('subCategories')->get(); 
        return apiResponse('success', 'Request Successful', $data, 200);
    }
}
