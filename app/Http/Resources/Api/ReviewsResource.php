<?php

namespace App\Http\Resources\Api;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $authUsername = User::where('user_id', $this->user_id)->first();
        return [
            'ads_id' => $this->ads_id,
            'message' => $this->message,
            'rating' => $this->rating,
            'user' => $authUsername
        ];
    }
}
