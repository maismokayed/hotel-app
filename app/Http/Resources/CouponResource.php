<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CouponResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
         return [
            'id'             => $this->id,
            'code'           => $this->code,
            'discount_type'  => $this->discount_type,
            'discount_value' => $this->discount_value,
            'used_count'     => $this->used_count,
            'max_uses'       => $this->max_uses,
            'expires_at'     => $this->expires_at?->format('Y-m-d H:i'),
            'is_active'      => $this->is_active,
            'created_at'     => $this->created_at->format('Y-m-d H:i'),
        ];
    }
}
