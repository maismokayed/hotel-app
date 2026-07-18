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
            'id'   => $this->id,
            'user' => $this->user ? [
                'id'        => $this->user->id,
                'full_name' => $this->user->full_name,
                'email'     => $this->user->email,
            ] : null,

            'room' => $this->room ? [
                'id'              => $this->room->id,
                'room_number'     => $this->room->room_number,

                'type' => [
                    'value' => $this->room->type->value,
                    'label' => $this->room->type->label(),
                ],

                'price_per_night' => $this->room->price_per_night,

                'hotel' => $this->room->hotel ? [
                    'id'   => $this->room->hotel->id,
                    'name' => [
                        'ar' => $this->room->hotel->name_ar,
                        'en' => $this->room->hotel->name_en,
                    ],
                    'image_url' => $this->room->hotel->getFirstMediaUrl('images'),
                ] : null,
            ] : null,

            'check_in_date'    => optional($this->check_in_date)->format('Y-m-d H:i'),
            'check_out_date'   => optional($this->check_out_date)->format('Y-m-d H:i'),
            'status'           => $this->status,
            'total_price'      => $this->total_price,
            'discount_amount'  => $this->discount_amount,
            'final_price'      => $this->final_price,
            'number_of_guests' => $this->number_of_guests,
            'coupon_id'        => $this->coupon_id,
            'created_at'       => optional($this->created_at)->format('Y-m-d H:i'),
        ];
    }
}
