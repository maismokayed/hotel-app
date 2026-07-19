<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'   => $this->id,

            'user' => $this->user ? [
                'id'        => $this->user->id,
                'full_name' => $this->user->full_name,
                'email'     => $this->user->email,
            ] : null,

            'hotel' => $this->hotel ? [
                'id'   => $this->hotel->id,
                'name' => [
                    'ar' => $this->hotel->name_ar,
                    'en' => $this->hotel->name_en,
                ],
                'image_url' => $this->hotel->getFirstMediaUrl('images'),
            ] : null,

            'rooms' => $this->whenLoaded('rooms', function () {
                return $this->rooms->map(fn($room) => [
                    'id'              => $room->id,
                    'room_number'     => $room->room_number,
                    'type' => [
                        'value' => $room->type->value,
                        'label' => $room->type->label(),
                    ],
                    'price_per_night' => $room->price_per_night,
                ]);
            }),

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
