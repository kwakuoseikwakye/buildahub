<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\AdsResource;
use App\Http\Resources\Api\ImageResource;
use App\Http\Resources\Api\ReviewsResource;
use App\Models\Ads;
use App\Models\AdsImage;
use App\Models\Favorites;
use App\Models\Plans;
use App\Models\Reviews;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Routing\Controllers\Middleware;

class AdsController extends Controller
{
    public function fetchAds(Request $request)
    {
        $premiumAds = Ads::with('conditions', 'categories', 'plans', 'cities.regions', 'images')
            ->where('plan_code', Plans::PREMIUM)
            ->orderByDesc('views')
            ->orderByDesc('created_at')
            ->get();

        $standardAds = Ads::with('conditions', 'categories', 'plans', 'cities.regions', 'images')
            ->where('plan_code', Plans::STANDARD)
            ->orderByDesc('created_at')
            ->orderByDesc('views')
            ->get();

        $basicAds = Ads::with('conditions', 'categories', 'plans', 'cities.regions', 'images')
            ->where('plan_code', Plans::BASIC)
            ->orderByDesc('created_at')
            ->orderByDesc('views')
            ->get();

        $ads = $premiumAds->merge($standardAds)->merge($basicAds);
        $data = AdsResource::collection($ads);

        return apiResponse('success', 'Request Successful', $data, 200);
    }

    public function fetchUserAds(Request $request)
    {
        $authUserDetails = extractUserToken($request);
        if (empty($authUserDetails)) {
            return apiResponse('error', 'Unauthorized - Token not provided or invalid', null, 401);
        }
        $ads = Ads::with('images', 'conditions', 'categories', 'plans', 'cities.regions')->where('user_id', $authUserDetails->user_id)->get();
        $data = AdsResource::collection($ads);

        return apiResponse('success', 'Request Successful', $data, 200);
    }

    public function getUserBookmarkAds(Request $request)
    {
        $authUserDetails = extractUserToken($request);
        if (empty($authUserDetails)) {
            return apiResponse('error', 'Unauthorized - Token not provided or invalid', null, 401);
        }
        $ads = Ads::with('images', 'conditions', 'categories', 'plans', 'cities.regions', 'favorites')
            ->whereHas('favorites', function ($query) use ($authUserDetails) {
                $query->where('user_id', $authUserDetails->user_id);
            })
            ->where('user_id', $authUserDetails->user_id)
            ->get();

        $data = AdsResource::collection($ads);
        return apiResponse('success', 'Request Successful', $data, 200);
    }

    public function fetchAdsCategory(Request $request, $categoryId)
    {
        $premiumAds = Ads::with('conditions', 'categories', 'plans', 'cities.regions', 'images')
            ->where('plan_code', Plans::PREMIUM)
            ->where('sub_category_id', $categoryId)
            ->orderByDesc('views')
            ->get();

        $standardAds = Ads::with('conditions', 'categories', 'plans', 'cities.regions', 'images')
            ->where('plan_code', Plans::STANDARD)
            ->where('sub_category_id', $categoryId)
            ->orderByDesc('views')
            ->get();

        $basicAds = Ads::with('conditions', 'categories', 'plans', 'cities.regions', 'images')
            ->where('plan_code', Plans::BASIC)
            ->where('sub_category_id', $categoryId)
            ->orderByDesc('views')
            ->get();

        $ads = $premiumAds->merge($standardAds)->merge($basicAds);

        $data = AdsResource::collection($ads);
        return apiResponse('success', 'Request Successful', $data, 200);
    }

    public function fetchTrendingAds(Request $request)
    {
        $premiumAds = Ads::with('conditions', 'categories', 'plans', 'cities.regions', 'images')
            ->where('plan_code', Plans::PREMIUM)
            ->orderByDesc('views')
            ->get();

        $standardAds = Ads::with('conditions', 'categories', 'plans', 'cities.regions', 'images')
            ->where('plan_code', Plans::STANDARD)
            ->orderByDesc('views')
            ->get();

        $basicAds = Ads::with('conditions', 'categories', 'plans', 'cities.regions', 'images')
            ->where('plan_code', Plans::BASIC)
            ->orderByDesc('views')
            ->get();

        $ads = $premiumAds->merge($standardAds)->merge($basicAds);
        $data = AdsResource::collection($ads);

        return apiResponse('success', 'Request Successful', $data, 200);
    }

    public function getSingleAd($modelId)
    {
        $data = Ads::with('conditions', 'categories', 'plans', 'cities.regions', 'images')
            ->where('model_id', $modelId)
            ->first();

        return $data;
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "item_name" => "required|string|max:50",
                "sub_category_id" => "required|numeric|min:0",
                "city_id" => "required|exists:cities,id",
                "amount" => "required|numeric|min:0|max:9999999999.99",
                "condition_code" => "required|string|max:30|exists:conditions,condition_code",
                "phone" => "required|string|max:15",
                "description" => "required|string|max:255",
                "plan_code" => "string|max:30|exists:plans,plan_code",
                'images' => 'required|array|min:2',
                'images.*' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            ]);

            if ($validator->fails()) {
                return apiResponse('error', "Adding failed. " . join(". ", $validator->errors()->all()), null, 422);
            }

            $authUserDetails = extractUserToken($request);
            if (empty($authUserDetails)) {
                return apiResponse('error', 'Unauthorized - Token not provided or invalid', null, 401);
            }

            $transactionResult = DB::transaction(function () use ($request, $authUserDetails) {
                $ads = new Ads();
                $ads->model_id = bin2hex(random_bytes(5));
                $ads->user_id = $authUserDetails->user_id;
                $ads->item_name = $request->item_name;
                $ads->sub_category_id = $request->sub_category_id;
                $ads->city_id = $request->city_id;
                $ads->condition_code = $request->condition_code;
                $ads->phone = $request->phone;
                $ads->description = $request->description;
                $ads->plan_code = $request->plan_code;
                $ads->amount = $request->amount;

                if ($ads->save()) {
                    foreach ($request->file('images') as $image) {
                        $path = $image->store('images', 'public');
                        $adImage = new AdsImage();
                        $adImage->ads_id = $ads->model_id;
                        $adImage->image = $path;
                        $adImage->save();
                    }
                } else {
                    return apiResponse('error', 'Failed to save ads', null, 500);
                }
            });
            if (!empty($transactionResult)) {
                throw new Exception($transactionResult);
            }
            return apiResponse('success', 'Ad created successfully', null, 201);
        } catch (\Throwable $e) {
            return internalServerErrorResponse("adding ads failed", $e);
        }
    }

    public function update(Request $request, $modelId)
    {
        try {
            $validator = Validator::make($request->all(), [
                "item_name" => "required|string|max:50",
                "sub_category_id" => "required|numeric|min:0",
                "city_id" => "required|exists:cities,id",
                "amount" => "required|numeric|min:0|max:9999999999.99",
                "condition_code" => "required|string|max:30|exists:conditions,condition_code",
                "phone" => "required|string|max:15",
                "description" => "required|string|max:100",
                "plan_code" => "string|max:30|exists:plans,plan_code",
                // 'images' => 'required|array|min:2',
                // 'images.*' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            ]);

            if ($validator->fails()) {
                return apiResponse('error', "Adding failed. " . join(". ", $validator->errors()->all()), null, 422);
            }

            $authUserDetails = extractUserToken($request);
            if (empty($authUserDetails)) {
                return apiResponse('error', 'Unauthorized - Token not provided or invalid', null, 401);
            }

            $transactionResult = DB::transaction(function () use ($request, $authUserDetails, $modelId) {
                Ads::where('model_id', $modelId)->update([
                    'user_id' => $authUserDetails->user_id,
                    'item_name' => $request->item_name,
                    'sub_category_id' => $request->sub_category_id,
                    'city_id' => $request->city_id,
                    'condition_code' => $request->condition_code,
                    'phone' => $request->phone,
                    'description' => $request->description,
                    'plan_code' => $request->plan_code,
                    'amount' => $request->amount,
                ]);

                // foreach ($request->file('images') as $image) {
                //     $path = $image->store('images', 'public');
                //     AdsImage::where('ads_id', $modelId)->update([
                //         'image' => $path
                //     ]);
                // }
            });
            if (!empty($transactionResult)) {
                throw new Exception($transactionResult);
            }
            $data = $this->getSingleAd($modelId);
            return apiResponse('success', 'Ad updated successfully', $data, 202);
        } catch (\Throwable $e) {
            return internalServerErrorResponse("updating ads failed", $e);
        }
    }

    public function addView(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "model_id" => "required|string|exists:ads,model_id",
            ]);

            if ($validator->fails()) {
                return apiResponse('error', "Adding failed. " . join(". ", $validator->errors()->all()), null, 422);
            }

            $ad = Ads::where('model_id', $request->model_id)->firstOrFail();
            $ad->increment('views');

            return apiResponse('success', 'Ad viewed successfully', null, 201);
        } catch (\Throwable $e) {
            return internalServerErrorResponse("adding views failed", $e);
        }
    }

    public function addReview(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "ads_id" => "required|string|max:50|exists:ads,model_id",
                "rating" => "required|numeric|max:5|min:1",
                "message" => "required|string|max:255"
            ]);

            if ($validator->fails()) {
                return apiResponse('error', "Adding failed. " . join(". ", $validator->errors()->all()), null, 422);
            }

            $authUserDetails = extractUserToken($request);
            $checkReviewDuplicate = Reviews::where('ads_id', $request->ads_id)->where('user_id', $authUserDetails->user_id)->exists();

            if ($checkReviewDuplicate) {
                return apiResponse('error', 'Review already added', null, 400);
            }

            Reviews::create([
                'user_id' => $authUserDetails->user_id,
                'ads_id' => $request->ads_id,
                'message' => $request->message,
                'rating' => $request->rating,
            ]);
            return apiResponse('success', 'Review added successfully', null, 201);
        } catch (\Throwable $e) {
            return internalServerErrorResponse("adding review failed", $e);
        }
    }

    public function getAdsReviews($modelId)
    {
        $reviews = Reviews::where('ads_id', $modelId)->get();

        return apiResponse('success', 'Request successful', ReviewsResource::collection($reviews), 200);
    }

    public function deleteAds($id)
    {
        $ad = Ads::where('model_id', $id)->exists();
        if (!$ad) {
            return apiResponse('error', 'Ad not found', null, 404);
        }
        try {
            $transactionResult = DB::transaction(function () use ($id) {
                Ads::where('model_id', $id)->delete();
                AdsImage::where('ads_id', $id)->delete();
            });
            if (!empty($transactionResult)) {
                throw new Exception($transactionResult);
            }
            return apiResponse('success', 'Ad deleted successfully', null, 200);
        } catch (\Throwable $e) {
            return internalServerErrorResponse(' deleting ads failed', $e);
        }
    }

    public function addBookmark(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "model_id" => "required|string|max:50|exists:ads,model_id"
            ]);

            if ($validator->fails()) {
                return apiResponse('error', "Adding failed. " . join(". ", $validator->errors()->all()), null, 422);
            }

            $authUserDetails = extractUserToken($request);
            if (empty($authUserDetails)) {
                return apiResponse('error', 'Unauthorized - Token not provided or invalid', null, 401);
            }

            // Favorites::updateOrCreate(
            //     ['user_id' => $authUserDetails->user_id, 'ads_id' => $request->model_id],
            //     ['user_id' => $authUserDetails->user_id, 'ads_id' => $request->model_id]
            // );

            Favorites::updateOrCreate(
                ['user_id' => $authUserDetails->user_id, 'ads_id' => $request->model_id],
                []  // No need to specify the same fields again in the second array
            );
            return apiResponse('success', 'Bookmarked successfully', null, 201);
        } catch (\Throwable $e) {
            return internalServerErrorResponse("adding favorite failed", $e);
        }
    }

    public function deleteBookmark(Request $request, $modelId)
    {
        $authUserDetails = extractUserToken($request);
        if (empty($authUserDetails)) {
            return apiResponse('error', 'Unauthorized - Token not provided or invalid', null, 401);
        }
        try {
            $fav = Favorites::where('ads_id', $modelId)->where('user_id', $authUserDetails->user_id)->delete();
            if ($fav) {
                return apiResponse('success', 'Favourite deleted successfully', null, 200);
            } else {
                return apiResponse('error', 'Favourite cannot be deleted', null, 400);
            }
        } catch (\Throwable $e) {
            return internalServerErrorResponse(' deleting fav failed', $e);
        }
    }
}
