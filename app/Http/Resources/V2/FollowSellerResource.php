<?php

namespace App\Http\Resources\V2;

use Illuminate\Http\Resources\Json\JsonResource;

class FollowSellerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $shop = $this->shop;

        return [
            'shop_id' => $shop?->id,
            'shop_slug' => $shop?->slug,
            'shop_name' => $shop?->name,
            'shop_url' => $shop?->slug,
            'shop_rating' => $shop?->rating,
            'shop_num_of_reviews' => $shop?->num_of_reviews,
            'shop_logo' => $shop ? uploaded_asset($shop->logo) : null,
        ];
    }
}
