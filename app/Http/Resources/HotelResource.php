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
            'id'          => $this->id,
            'name'        => $this->name,
            'description' => $this->description,
            'city' => $this->whenLoaded(
                'city',
                fn() =>
                $this->city ? [
                    'id'        => $this->city->id,
                    'name'      => $this->city->name,
                    'image_url' => $this->city->getFirstMediaUrl('images') ?: null,
                ] : null
            ),
            'address'     => $this->address,

            'contact' => [
                'phone' => $this->phone,
                'email' => $this->email,
            ],

            'star_rating' => $this->star_rating,

            'is_active'   => (bool) $this->is_active,
            'services' => ServiceResource::collection($this->whenLoaded('services')),

            'owner' => $this->whenLoaded('user', fn() => new UserResource($this->user)),
        ];
    }
}
