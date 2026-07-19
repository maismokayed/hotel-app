<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use App\Models\Booking;
use App\Models\Room;


class Hotel extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia;

    protected $fillable = [
        'name_ar',
        'name_en',
        'description_ar',
        'description_en',
        'city_id',
        'address_ar',
        'address_en',
        'phone',
        'email',
        'star_rating',
        'is_active',
        'user_id',
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('images');
    }
    public function city()
    {
        return $this->belongsTo(City::class);
    }
    public function rooms()
    {
        return $this->hasMany(Room::class);
    }
    public function services()
    {
        return $this->belongsToMany(Service::class);
    }
    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }
}
