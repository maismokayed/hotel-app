<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Booking extends Model
{
       use HasFactory;
protected $fillable = [
        'user_id',
        'room_id',
        'coupon_id',
        'check_in_date',
        'check_out_date',
        'status',
        'total_price',
        'discount_amount',
        'final_price',
        'number_of_guests',
    ];

    protected $casts = [
        'check_in_date'  => 'datetime',
        'check_out_date' => 'datetime',
        'total_price'    => 'decimal:2',
        'discount_amount'=> 'decimal:2',
        'final_price'    => 'decimal:2',
    ];

    // العلاقات
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    public function review()
    {
        return $this->hasOne(Review::class);
    }
}
