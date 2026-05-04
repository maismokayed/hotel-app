<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'user'            => [
                'id'        => $this->user->id,
                'full_name' => $this->user->full_name,
                'email'     => $this->user->email,
            ],
           'room' => [
    'id'              => $this->room->id,
    'room_number'     => $this->room->room_number,
    'type'            => $this->room->type,
    'price_per_night' => $this->room->price_per_night,
],
            'check_in_date'   => $this->check_in_date->format('Y-m-d H:i'),
            'check_out_date'  => $this->check_out_date->format('Y-m-d H:i'),
            'status'          => $this->status,
            'total_price'     => $this->total_price,
            'discount_amount' => $this->discount_amount,
            'final_price'     => $this->final_price,
            'number_of_guests'=> $this->number_of_guests,
            'coupon_id'       => $this->coupon_id,
            'created_at'      => $this->created_at->format('Y-m-d H:i'),
        ];
    }
}
