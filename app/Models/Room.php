<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Enums\RoomType;
use App\Enums\RoomStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Room extends Model
{
    use HasFactory;

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
    public function bookings()
    {
        return $this->belongsToMany(Booking::class, 'booking_room')
            ->using(BookingRoom::class)
            ->withTimestamps();
    }
}
