<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Review extends Model
{
        use HasFactory;

    protected $fillable = [
        'user_id',
        'hotel_id',
        'booking_id',
        'comment',
        'rating',
        'review_date',
    ];

    protected $casts = [
        'review_date' => 'date',
        'rating'      => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}
