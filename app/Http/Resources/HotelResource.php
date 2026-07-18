<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;


class HotelResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return  [
            'id' => $this->id,

            'name' => [
                'ar' => $this->name_ar,
                'en' => $this->name_en,
            ],

            'description' => [
                'ar' => $this->description_ar,
                'en' => $this->description_en,
            ],

            'address' => [
                'ar' => $this->address_ar,
                'en' => $this->address_en,
            ],

            'city' => $this->whenLoaded(
                'city',
                fn() =>
                $this->city ? [
                    'id'   => $this->city->id,
                    'name' => [
                        'ar' => $this->city->name_ar,
                        'en' => $this->city->name_en,
                    ],
                    'image_url' => $this->city->getFirstMediaUrl('images') ?: null,
                ] : null
            ),
            'contact' => [
                'phone' => $this->phone,
                'email' => $this->email,
            ],

            'star_rating' => $this->star_rating,

            'cover_image' => $this->getFirstMediaUrl('images') ?: null,
            'is_active'   => (bool) $this->is_active,
            'services' => ServiceResource::collection($this->whenLoaded('services')),

            'image' => $this->getFirstMediaUrl('images'),

            'owner' => $this->whenLoaded('user', fn() => new UserResource($this->user)),
        ];
    }
}
