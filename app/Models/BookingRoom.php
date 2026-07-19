<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class BookingRoom extends Pivot
{
    protected $table = 'booking_room';

    public $incrementing = true;

    protected $fillable = [
        'booking_id',
        'room_id',
    ];
}
