<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Enums\RoomType;
use App\Enums\RoomStatus;

class Room extends Model
{
     protected $fillable = [
        'hotel_id',
        'room_number',
        'type',
        'capacity',
        'price_per_night',
        'status',
    ];
     protected $casts = [
        'type' => RoomType::class,
        'status' => RoomStatus::class,

    ];

    /*
    | Relationships
    */

    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }
}
