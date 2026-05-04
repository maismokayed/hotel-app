<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
      return [
            'id'          => $this->id,
            'user'        => [
                'id'        => $this->user->id,
                'full_name' => $this->user->full_name,
            ],
            'hotel_id'    => $this->hotel_id,
            'booking_id'  => $this->booking_id,
            'comment'     => $this->comment,
            'rating'      => $this->rating,
            'review_date' => $this->review_date->format('Y-m-d'),
            'created_at'  => $this->created_at->format('Y-m-d H:i'),
        ];
    }
}
