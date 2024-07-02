<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ArtisanServices;
use App\Models\ServiceImage;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ServiceController extends Controller
{
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "service_category_id" => "required|numeric|min:0",
                "city_id" => "required|exists:cities,id",
                "amount" => "required|numeric|min:0|max:9999999999.99",
                "phone" => "required|string|max:15",
                "description" => "required|string|max:100",
                "plan_code" => "string|max:30|exists:plans,plan_code",
                'images' => 'required|array|min:1',
                'images.*' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            ]);

            if ($validator->fails()) {
                return apiResponse('error', "Adding failed. " . join(". ", $validator->errors()->all()), null, 422);
            }

            $authUserDetails = extractUserToken($request);

            $transactionResult = DB::transaction(function () use ($request, $authUserDetails) {
                $service = new ArtisanServices();
                $service->model_id = bin2hex(random_bytes(5));
                $service->user_id = $authUserDetails->user_id;
                $service->service_category_id = $request->service_category_id;
                $service->city_id = $request->city_id;
                $service->phone = $request->phone;
                $service->description = $request->description;
                $service->plan_code = $request->plan_code;
                $service->amount = $request->amount;

                if ($service->save()) {
                    foreach ($request->file('images') as $image) {
                        $path = $image->store('images', 'public');
                        $serviceImage = new ServiceImage();
                        $serviceImage->services_id = $service->model_id;
                        $serviceImage->image = $path;
                        $serviceImage->save();
                    }
                } else {
                    return apiResponse('error', 'Failed to save service', null, 500);
                }
            });
            if (!empty($transactionResult)) {
                throw new Exception($transactionResult);
            }
            return apiResponse('success', 'Service created successfully', null, 201);
        } catch (\Throwable $e) {
            return internalServerErrorResponse("adding service failed", $e);
        }
    }
}
