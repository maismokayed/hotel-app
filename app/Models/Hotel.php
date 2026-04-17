<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Hotel extends Model
{
     use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'city',
        'address',
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
}
