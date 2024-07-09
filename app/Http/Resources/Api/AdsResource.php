<?php

namespace App\Http\Resources\Api;

use App\Models\Favorites;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        $authUserDetails = extractUserToken($request);
        $bookmarked = Favorites::where('ads_id', $this->model_id)->where('user_id', $authUserDetails->user_id)->exists();
        return [
            'model_id' => $this->model_id,
            'user_id' => $this->user_id,
            'auth_user' => $authUserDetails->user_id,
            'item_name' => $this->item_name,
            'amount' => $this->amount,
            'bookmarked' => ($bookmarked) ? true : false,
            'sub_category_id' => $this->sub_category_id,
            'city_id' => $this->city_id,
            'condition_code' => $this->condition_code,
            'phone' => $this->phone,
            'description' => $this->description,
            'plan_code' => $this->plan_code,
            'image_code' => $this->image_code,
            'views' => $this->views,
            'images' => ImageResource::collection($this->images),
            'conditions' => [
                'condition_name' => $this->conditions->condition_name,
                'condition_code' => $this->conditions->condition_code,
            ],
            'categories' => [
                'id' => $this->categories->id,
                'category_name' => $this->categories->category_name,
                'image' => $this->categories->image,
            ],
            'plans' => $this->plans,
            'cities' => [
                'id' => $this->cities->id,
                'city_name' => $this->cities->city_name,
                'region_code' => $this->cities->region_code,
                'regions' => [
                    'code' => $this->cities->regions->code,
                    'name' => $this->cities->regions->name,
                ]
            ]
        ];
    }
}
