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
            'city'        => $this->city,
            'address'     => $this->address,

            'contact' => [
                'phone' => $this->phone,
                'email' => $this->email,
            ],

            'star_rating' => $this->star_rating,

            'is_active'   => (bool) $this->is_active,

            'owner' => $this->whenLoaded('user', function () {
                return [
                    'id'   => $this->user->id,
                    'name' => $this->user->name,
                ];
            }),
        ];
    }
}
