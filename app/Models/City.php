<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;


class City extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'name'
    ];

    public function hotels()
    {
        return $this->hasMany(Hotel::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('images');
    }
}
