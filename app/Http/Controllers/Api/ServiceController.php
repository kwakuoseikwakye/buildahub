<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\ServicesResource;
use App\Models\ArtisanServices;
use App\Models\Payment;
use App\Models\Plans;
use App\Models\ServiceCategory;
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

    public function fetchServicesCategories()
    {
        $services = ServiceCategory::get();
        return apiResponse('success', 'Request successful', $services, 200);
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
                "service_category_id" => "required|numeric|exists:service_categories,id",
                "city_id" => "required|exists:cities,id",
                "amount" => "required|numeric|min:1",
                "phone" => "required|string|max:15",
                "description" => "required|string|max:255",
                "title" => "required|string|max:255",
                "plan_code" => "string|max:30|exists:plans,plan_code",
                'images' => 'required|array|min:1',
                'images.*' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            ]);

            if ($validator->fails()) {
                return apiResponse('error', "Adding failed. " . join(". ", $validator->errors()->all()), null, 422);
            }

            $authUserDetails = extractUserToken($this->request);

            DB::beginTransaction();
            $service = ArtisanServices::create([
                'service_category_id' => $this->request->service_category_id,
                'city_id' => $this->request->city_id,
                'amount' => $this->request->amount,
                'phone' => $this->request->phone,
                'description' => $this->request->description,
                'plan_code' => $this->request->plan_code,
                'user_id' => $authUserDetails->user_id,
                'title' => $this->request->title,
                'model_id' => bin2hex(random_bytes(5))
            ]);

            foreach ($this->request->file('images') as $image) {
                $path = $image->store('images', 'public');
                $serviceImage = new ServiceImage();
                $serviceImage->services_id = $service->model_id;
                $serviceImage->image = $path;
                $serviceImage->save();
            }

            $this->initiatePayment($this->request->plan_code, $service->model_id, $this->request->payment_method, $authUserDetails);
            
            DB::commit();
            return apiResponse('success', 'Service created successfully', $service, 201);
        } catch (\Throwable $e) {
            DB::rollBack();
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

    private function initiatePayment($planCode, $modelId, $paymentMethod, $user)
    {
        try {
            $planAmount =  Plans::where('plan_code', $planCode)->first();
            if (!empty($planAmount)) {
                $amt = $planAmount->amount;
            } else {
                return apiResponse('error', "Plan not found", null, 404);
            }

            if ($amt < 1) {
                return apiResponse('error', "Amount cannot be less than 1", null, 400);
            }
            $amount = match (true) {
                is_numeric($amt) && ($number = (int)($amt * 100)) >= 0 && $number <= 999999999999 =>
                str_pad($number, 12, '0', STR_PAD_LEFT),
                is_string($amt) && strlen($amt) === 12 && ctype_digit($amt) =>
                $amt,
                default => '',
            };

            $transactionId = '';
            for ($i = 0; $i < 12; $i++) {
                $transactionId .= random_int(0, 9);
            }

            $username = env("API_USER");
            $key = env("API_KEY");
            $url = "https://buildahub.net";

            Payment::create([
                "transaction_id" => $transactionId,
                "amount_paid" => $amt,
                "order_id" => $modelId,
                "userid" => $user->user_id,
                "status" => Payment::PENDING,
                "payment_mode" => "online",
            ]);

            $credentials = base64_encode($username . ':' . $key);
            $payload = json_encode([
                "merchant_id" => "TTM-00009286",
                "transaction_id" => $transactionId,
                "desc" => "Payment Using Checkout Page",
                "amount" => $amount,
                "redirect_url" => $url,
                "email" => $user->email,
                'payment_method' => $paymentMethod,
                'currency' => 'GHS'
            ]);

            $curl = curl_init("https://checkout-test.theteller.net/initiate"); //TODO:MUST CHANGE TO LIVE URL
            curl_setopt_array($curl, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_HTTPHEADER => [
                    "Authorization: Basic " . $credentials,
                    "Cache-Control: no-cache",
                    "Content-Type: application/json",
                ],
                CURLOPT_POSTFIELDS => $payload,
            ]);

            $response = curl_exec($curl);
            $err = curl_error($curl);

            curl_close($curl);

            if ($err) {
                return apiResponse('error', $err, null, 400);
            }

            return apiResponse('success', 'Payment link generated successfully', json_decode($response, true), 200);
        } catch (\Exception $e) {
            return internalServerErrorResponse('generating payment link failed', $e);
        }
    }
}
