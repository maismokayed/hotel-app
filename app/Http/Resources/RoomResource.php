<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoomResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'room_number' => $this->room_number,

            'type' => [
                'value' => $this->type->value,
                'label' => $this->type->label(),
            ],

            'status' => $this->status,

            'capacity'        => $this->capacity,
            'price_per_night' => $this->price_per_night,


            'cover_image' => $this->getFirstMediaUrl('images') ?: null,


            'images' => $this->getMedia('images')->map(fn($media) => [
                'id'  => $media->id,
                'url' => $media->getUrl(),
            ])->values(),

            'hotel' => $this->whenLoaded('hotel', fn() => [
                'id'   => $this->hotel->id,
                'name' => [
                    'ar' => $this->hotel->name_ar,
                    'en' => $this->hotel->name_en,
                ],
            ]),
        ];
    }
}
