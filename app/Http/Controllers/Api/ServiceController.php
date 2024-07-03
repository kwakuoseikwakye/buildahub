<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\ServicesResource;
use App\Models\ArtisanServices;
use App\Models\Plans;
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

    public function fetchServices()
    {
        $premiumAds = ArtisanServices::with('categories', 'plans', 'cities.regions', 'images')
            ->where('plan_code', Plans::PREMIUM)
            ->orderByDesc('views')
            ->orderByDesc('created_at')
            ->get();

        $standardAds = ArtisanServices::with('categories', 'plans', 'cities.regions', 'images')
            ->where('plan_code', Plans::STANDARD)
            ->orderByDesc('created_at')
            ->orderByDesc('views')
            ->get();

        $basicAds = ArtisanServices::with('categories', 'plans', 'cities.regions', 'images')
            ->where('plan_code', Plans::BASIC)
            ->orderByDesc('created_at')
            ->orderByDesc('views')
            ->get();

        $ads = $premiumAds->merge($standardAds)->merge($basicAds);
        $data = ServicesResource::collection($ads);

        return apiResponse('success', 'Request Successful', $data, 200);
    }

    public function getSingleService($modelId)
    {
        $data = ArtisanServices::with('categories', 'plans', 'cities.regions', 'images')
            ->where('model_id', $modelId)
            ->first();

        return $data;
    }

    public function store()
    {
        try {
            $validator = Validator::make($this->request->all(), [
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

            $authUserDetails = extractUserToken($this->request);

            $transactionResult = DB::transaction(function () use ($authUserDetails) {
                $service = new ArtisanServices();
                $service->model_id = bin2hex(random_bytes(5));
                $service->user_id = $authUserDetails->user_id;
                $service->service_category_id = $this->request->service_category_id;
                $service->city_id = $this->request->city_id;
                $service->phone = $this->request->phone;
                $service->description = $this->request->description;
                $service->plan_code = $this->request->plan_code;
                $service->amount = $this->request->amount;

                if ($service->save()) {
                    foreach ($this->request->file('images') as $image) {
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

    public function update($modelId)
    {
        try {
            $validator = Validator::make($this->request->all(), [
                "service_category_id" => "required|numeric|min:0",
                "city_id" => "required|exists:cities,id",
                "amount" => "required|numeric|min:0|max:9999999999.99",
                "phone" => "required|string|max:15",
                "description" => "required|string|max:100",
                "plan_code" => "string|max:30|exists:plans,plan_code",
            ]);

            if ($validator->fails()) {
                return apiResponse('error', "Updating failed. " . join(". ", $validator->errors()->all()), null, 422);
            }

            $authUserDetails = extractUserToken($this->request);

            $transactionResult = DB::transaction(function () use ($authUserDetails, $modelId) {
                ArtisanServices::where('model_id', $modelId)->update([
                    'user_id' => $authUserDetails->user_id,
                    'service_category_id' => $this->request->service_category_id,
                    'city_id' => $this->request->city_id,
                    'phone' => $this->request->phone,
                    'description' => $this->request->description,
                    'plan_code' => $this->request->plan_code,
                    'amount' => $this->request->amount,
                ]);
            });
            if (!empty($transactionResult)) {
                throw new Exception($transactionResult);
            }
            $data = $this->getSingleService($modelId);
            return apiResponse('success', 'Service updated successfully', $data, 202);
        } catch (\Throwable $e) {
            return internalServerErrorResponse("updating service failed", $e);
        }
    }
}
