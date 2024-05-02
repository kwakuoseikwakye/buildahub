<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\CategoryResource;
use App\Models\Categories;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function fetchCategories()
    {
        $data = CategoryResource::collection(Categories::get()); 
        return apiResponse('success', 'Request Successful', $data, 200);
    }
}
